<?php
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Monad\Set;
use PDOException;
use Zend\Db\Adapter\Adapter;
use Assembler\FFor;
use Tree\Node\Node;
use SAccounts\Visitor\NodeSaver;
use SAccounts\Transaction\SplitTransaction;
use SAccounts\Transaction\Entry;
use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\Exception\InvalidQueryException;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

/**
 * Main API interface to Simple Accounts
 */
class Accountant
{
    /**@+
     * Error strings
     */
    const ERR1 = 'Chart id not set';
    /**@-*/

    /**
     * @var Adapter 
     */
    protected $dbAdapter;
    /**
     * @var IntType 
     */
    protected $chartId;

    /**
     * Accountant constructor.
     * 
     * If $chartId is null, then Accountant can only create a new chart.  The
     * new Chart will then be the Chart that is used by the Accountant. 
     *
     * @param Adapter      $dbAdapter
     * @param IntType|null $chartId
     */
    public function __construct(Adapter $dbAdapter, IntType $chartId = null)
    {
        $this->dbAdapter = $dbAdapter;
        $this->chartId = $chartId;
    }

    /**
     * Create a new Chart from a definition and store it in the database
     *
     * @param StringType $chartName This needs to be unique
     * @param ChartDefinition $def
     *
     * @return IntType  The Chart Id
     */
    public function createChart(StringType $chartName, ChartDefinition $def)
    {
        $this->chartId = FFor::create(['def' => $def, 'chartName' => $chartName])
            ->root(function ($def) {
                return (new \DOMXPath($def->getDefinition()))->query('/chart/account')->item(0);
            })
            ->tree(function () {
                return new Node();
            })
            ->chart(function ($tree, $chartName) {
                return new Chart($chartName, $tree);
            })
            ->build(function ($root, $tree) {
                $this->buildTreeFromXml($tree, $root, AccountType::toArray());
            })
            ->store(function (Chart $chart, Node $tree) {
                return $this->storeChart($chart->setRootNode($tree));
            })
            ->fyield('store');

        return $this->chartId;
    }

    /**
     * Fetch a chart from storage
     *
     * @return Chart
     *
     * @throws AccountsException
     */
    public function fetchChart()
    {
        if (is_null($this->chartId)) {
            throw new AccountsException(self::ERR1);
        }

        return FFor::create(
                [
                    'chartId' => $this->chartId,
                    'dbAdapter' => $this->dbAdapter
                ]
            )
            ->accounts(function(IntType $chartId, Adapter $dbAdapter) {
                return $dbAdapter
                    ->query(
                        "call sa_sp_get_tree('{$chartId}')",
                        Adapter::QUERY_MODE_EXECUTE
                    )
                    ->toArray();
            })
            ->chartName(function(IntType $chartId, Adapter $dbAdapter) {
                return new StringType($dbAdapter->query(
                        "select name from sa_coa where id = {$chartId}",
                        Adapter::QUERY_MODE_EXECUTE)
                    ->current()
                    ->offsetGet('name'));
            })
            ->rootAc(function($accounts) {return array_shift($accounts);})
            ->root(function($rootAc, $accounts) {
                $root = new Node(
                    new Account(
                        new Nominal($rootAc['nominal']),
                        AccountType::{$rootAc['type']}(),
                        new StringType($rootAc['name']),
                        new IntType($rootAc['acDr']),
                        new IntType($rootAc['acCr'])
                    )
                );

                return $this->buildTreeFromDb(
                    $root, $accounts, $rootAc['destid']
                );
            })
            ->chart(function(StringType $chartName, Node $root, IntType $chartId) {
                return new Chart($chartName, $root, $chartId);
            })
            ->fyield('chart');
        }

    /**
     * Write a Transaction to the Journal and update the Chart
     *
     * @param SplitTransaction $txn
     * @param \DateTime|null $dateTime
     *
     * @return IntType Transaction Id
     * @throws AccountsException
     */
    public function writeTransaction(SplitTransaction $txn, \DateTime $dateTime = null)
    {
        if (is_null($this->chartId)) {
            throw new AccountsException(self::ERR1);
        }

        return FFor::create(
            [
                'txn' => $txn,
                'dateTime' => is_null($dateTime) ? $dateTime : $dateTime->format('Y-m-d h:m:s'),
                'txns' => $txn->getEntries()->toArray(),
                'dbAdapter' => $this->dbAdapter,
                'chartId' => $this->chartId
            ]
        )
            ->stmnt(function(Adapter $dbAdapter) {
                return $dbAdapter->query(
                    "select sa_fu_add_txn(?, ?, ?, ?, ?, ?, ?, ?) as txnId",
                    Adapter::QUERY_MODE_PREPARE
                );
            })
            ->write(function(StatementInterface $stmnt, $dateTime, SplitTransaction $txn, array $txns, IntType $chartId) {
                return new IntType(
                    $stmnt->execute(
                        [
                            $this->chartId->get(),
                            $txn->getNote()->get(),
                            $dateTime,
                            is_null($txn->getSrc()) ? null : $txn->getSrc()->get(),
                            is_null($txn->getRef()) ? null: $txn->getRef()->get(),
                            implode(
                                ',',
                                array_map(
                                    function(Entry $entry) {
                                        return $entry->getId()->get();
                                    },
                                    $txns
                                )
                            ),
                            implode(
                                ',',
                                array_map(
                                    function(Entry $entry) {
                                        return $entry->getAmount()->get();
                                    },
                                    $txns
                                )
                            ),
                            implode(
                                ',',
                                array_map(
                                    function(Entry $entry) {
                                        return $entry->getType()->getKey();
                                    },
                                    $txns
                                )
                            )
                        ]
                    )->current()['txnId']
                );
            })
            ->fyield('write');
        }

    /**
     * Fetch a journal transaction identified by its journal id
     *
     * @param IntType $jrnId
     *
     * @return SplitTransaction
     */
    public function fetchTransaction(IntType $jrnId)
    {
        return FFor::create(
            [
                'jrnId' => $jrnId,
                'dbAdapter' => $this->dbAdapter
            ]
        )
            ->journal(function(IntType $jrnId, Adapter $dbAdapter) {
                $journal = $dbAdapter->query('select * from sa_journal where id = ?')
                    ->execute([$jrnId])
                    ->getResource()->fetchAll(\PDO::FETCH_ASSOC);
                return array_pop($journal);
            })
            ->entries(function(IntType $jrnId, Adapter $dbAdapter) {
                return $dbAdapter->query('select * from sa_journal_entry where jrnId = ?')
                    ->execute([$jrnId()])
                    ->getResource()->fetchAll(\PDO::FETCH_ASSOC);
            })
            ->txn(function(array $journal, array $entries, IntType $jrnId) {
                $txn = (new SplitTransaction(
                    new StringType($journal['note']),
                    new StringType($journal['src']),
                    new IntType($journal['ref']),
                    new \DateTime($journal['date'])
                ))->setId($jrnId);

                foreach ($entries as $entry) {
                    $txn->addEntry(
                        new Entry(
                            new Nominal($entry['nominal']),
                            new IntType($entry['acDr'] == 0 ? $entry['acCr'] : $entry['acDr']),
                            ($entry['acDr'] == 0 ? AccountType::CR() : AccountType::DR())
                        )
                    );
                }

                return $txn;
            })
            ->fyield('txn');
    }

    /**
     * Fetch journal entries for an account
     *
     * The returned Set is a Set of SplitTransactions with only the entries for
     * the required Account.  They will therefore be unbalanced.
     *
     * @param Nominal $nominal
     *
     * @return Set
     */
    public function fetchAccountJournals(Nominal $nominal)
    {
        return FFor::create(
            [
                'dbAdapter' => $this->dbAdapter,
                'nominal' => $nominal,
                'chartId' => $this->chartId
            ]
        )
            ->sql(function(Adapter $dbAdapter) {return new Sql($dbAdapter);})
            ->select(function(Sql $sql, Nominal $nominal, IntType $chartId) {
                return $sql->select(['j' => 'sa_journal'])
                    ->join(
                        ['e' => 'sa_journal_entry'],
                        'j.id = e.jrnId',
                        ['nominal', 'acDr', 'acCr']
                    )
                    ->columns(['id', 'note', 'date', 'src', 'ref'])
                    ->where(
                        [
                            'e.nominal' => $nominal(),
                            'j.chartId' => $chartId()
                        ]
                    );
            })
            ->entries(function(Adapter $dbAdapter, Sql $sql, Select $select) {
                return $dbAdapter->query($sql->buildSqlString($select))
                    ->execute()
                    ->getResource()
                    ->fetchAll(\PDO::FETCH_ASSOC);
            })
            ->build(function(array $entries) {
                $transactions = [];
                $jrnId = new IntType(-1);
                foreach ($entries as $entry) {
                    if ($entry['id'] != $jrnId()) {
                        $jrnId = new IntType($entry['id']);
                        $txn = new SplitTransaction(
                            new StringType($entry['note']),
                            new StringType($entry['src']),
                            new IntType($entry['ref']),
                            new \DateTime($entry['date'])
                        );
                        $txn->setId($jrnId);
                        $transactions[] = $txn;
                    }
                    $txn->addEntry(
                        new Entry(
                            new Nominal($entry['nominal']),
                            new IntType(empty($entry['acDr']) ? $entry['acCr'] : $entry['acDr']),
                            empty($entry['acDr']) ? AccountType::CR() : AccountType::DR()
                        )
                    );
                }

                return new Set($transactions, SplitTransaction::class);
            })
            ->fyield('build');
    }


    /**
     * Add an account (ledger) to the chart
     *
     * Exceptions thrown if parent doesn't exist or you try to add a second
     * root account
     *
     * @param Nominal      $nominal
     * @param AccountType  $type
     * @param StringType   $name
     * @param Nominal|null $prntNominal
     *
     * @throws DbException
     */
    public function addAccount(
        Nominal $nominal,
        AccountType $type,
        StringType $name,
        Nominal $prntNominal = null
    ) {
        try {
            $this->dbAdapter->query('call sa_sp_add_ledger(?, ?, ?, ?, ?)')
                ->execute(
                    [
                        $this->chartId->get(),
                        $nominal(),
                        $type->getKey(),
                        $name(),
                        is_null($prntNominal) ? '' : $prntNominal()
                    ]
                );
        } catch (InvalidQueryException $e) {
            throw new DbException($e);
        } catch (PDOException $e) {
            throw new DbException($e);
        }
    }

    /**
     * Delete an account (ledger) and all its child accounts
     * Exception thrown if the account has non zero debit or credit amounts
     *
     * @param Nominal $nominal
     *
     * @throws DbException
     */
    public function delAccount(Nominal $nominal)
    {
        try {
            $this->dbAdapter->query('call sa_sp_del_ledger(?, ?)')
                ->execute(
                    [
                        $this->chartId->get(),
                        $nominal()
                    ]
                );
        } catch (InvalidQueryException $e) {
            throw new DbException($e);
        }
    }


    /**
     * Build chart tree from database records
     *
     * @param Node  $node
     * @param array $accounts
     * @param int   $origId
     *
     * @return Node
     */
    protected function buildTreeFromDb(
        Node $node,
        array $accounts,
        $origId
    ) {
        $childAccounts = array_filter(
            $accounts,
            function ($account) use ($origId) {
                return $account['origid'] == $origId;
            }
        );

        foreach ($childAccounts as $childAccount) {
            $childNode = new Node(
                new Account(
                    new Nominal($childAccount['nominal']),
                    AccountType::{$childAccount['type']}(),
                    new StringType($childAccount['name']),
                    new IntType($childAccount['acDr']),
                    new IntType($childAccount['acCr'])
                )
            );
            $childNode = $this->buildTreeFromDb($childNode, $accounts, $childAccount['destid']);
            $node->addChild($childNode);
        }

        return $node;
    }

    /**
     * Recursively build chart of account tree from XML
     *
     * @param Node $tree
     * @param \DOMNode $node
     * @param array $accountTypes
     */
    protected function buildTreeFromXml(Node $tree, \DOMNode $node, array $accountTypes)
    {
        //create current node
        list($nominal, $type, $name) = FFor::create(
            [
                'attributes' => $node->attributes,
                'accountTypes' => $accountTypes
            ]
        )
            ->nominal(function (\DOMNamedNodeMap $attributes) {
                return new Nominal($attributes->getNamedItem('nominal')->nodeValue);
            })
            ->name(function (\DOMNamedNodeMap $attributes) {
                return new StringType($attributes->getNamedItem('name')->nodeValue);
            })
            ->type(function (\DOMNamedNodeMap $attributes, $accountTypes) {
                return new AccountType(
                    $accountTypes[strtoupper($attributes->getNamedItem('type')->nodeValue)]
                );
            })
            ->fyield('nominal', 'type', 'name');

        $tree->setValue(new Account($nominal, $type, $name, new IntType(0), new IntType(0)));

        //recurse through sub accounts
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement) {
                $childTree = new Node();
                $tree->addChild($childTree);
                $this->buildTreeFromXml($childTree, $childNode, $accountTypes);
            }
        }
    }

    /**
     * Store chart definition
     *
     * @param Chart $chart
     *
     * @return IntType  New Chart Id
     */
    protected function storeChart(Chart $chart)
    {
        return FFor::create(
            [
                'chart' => $chart,
                'root' => $chart->getTree(),
                'dbAdapter' => $this->dbAdapter
            ]
        )
            ->chartId(function(Chart $chart, Adapter $dbAdapter) {
                $res = $dbAdapter->query(
                    "select sa_fu_add_chart('{$chart->getName()->get()}')",
                    Adapter::QUERY_MODE_EXECUTE
                )
                    ->current()
                    ->getArrayCopy();

                return new IntType(array_pop($res));
            })
            ->build(function(Node $root, IntType $chartId, Adapter $dbAdapter) {
                $root->accept(new NodeSaver($chartId, $this->dbAdapter));
            })
            ->fyield('chartId');
    }
}