<?php
declare(strict_types=1);
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license BSD-3-Clause See LICENSE.md
 */
namespace SAccounts;

use Ds\Set;
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
     * @var int
     */
    protected $chartId;

    /**
     * Accountant constructor.
     * 
     * If $chartId is null, then Accountant can only create a new chart.  The
     * new Chart will then be the Chart that is used by the Accountant. 
     *
     * @param Adapter      $dbAdapter
     * @param int|null $chartId
     */
    public function __construct(Adapter $dbAdapter, int $chartId = null)
    {
        $this->dbAdapter = $dbAdapter;
        $this->chartId = $chartId;
    }

    /**
     * Create a new Chart from a definition and store it in the database
     *
     * @param string $chartName This needs to be unique
     * @param ChartDefinition $def
     *
     * @return int  The Chart Id
     */
    public function createChart(string $chartName, ChartDefinition $def): int
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
    public function fetchChart(): Chart
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
            ->accounts(function(int $chartId, Adapter $dbAdapter) {
                return $dbAdapter
                    ->query(
                        "call sa_sp_get_tree('{$chartId}')",
                        Adapter::QUERY_MODE_EXECUTE
                    )
                    ->toArray();
            })
            ->chartName(function(int $chartId, Adapter $dbAdapter) {
                return $dbAdapter->query(
                        "select name from sa_coa where id = {$chartId}",
                        Adapter::QUERY_MODE_EXECUTE)
                    ->current()
                    ->offsetGet('name');
            })
            ->rootAc(function($accounts) {return array_shift($accounts);})
            ->root(function($rootAc, $accounts) {
                $root = new Node(
                    new Account(
                        new Nominal($rootAc['nominal']),
                        AccountType::{$rootAc['type']}(),
                        $rootAc['name'],
                        (int) $rootAc['acDr'],
                        (int) $rootAc['acCr']
                    )
                );

                return $this->buildTreeFromDb(
                    $root, $accounts, $rootAc['destid']
                );
            })
            ->chart(function(string $chartName, Node $root, int $chartId) {
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
     * @return int Transaction Id
     * @throws AccountsException
     */
    public function writeTransaction(SplitTransaction $txn, \DateTime $dateTime = null): int
    {
        if (is_null($this->chartId)) {
            throw new AccountsException(self::ERR1);
        }

        return (int) FFor::create(
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
            ->write(function(StatementInterface $stmnt, $dateTime, SplitTransaction $txn, array $txns, int $chartId) {
                return $stmnt->execute(
                        [
                            $this->chartId,
                            $txn->getNote(),
                            $dateTime,
                            is_null($txn->getSrc()) ? null : $txn->getSrc(),
                            is_null($txn->getRef()) ? null: $txn->getRef(),
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
                                        return $entry->getAmount();
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
                    )->current()['txnId'];
            })
            ->fyield('write');
        }

    /**
     * Fetch a journal transaction identified by its journal id
     *
     * @param int $jrnId
     *
     * @return SplitTransaction
     */
    public function fetchTransaction(int $jrnId): SplitTransaction
    {
        return FFor::create(
            [
                'jrnId' => $jrnId,
                'dbAdapter' => $this->dbAdapter
            ]
        )
            ->journal(function(int $jrnId, Adapter $dbAdapter) {
                $journal = $dbAdapter->query('select * from sa_journal where id = ?')
                    ->execute([$jrnId])
                    ->getResource()->fetchAll(\PDO::FETCH_ASSOC);
                return array_pop($journal);
            })
            ->entries(function(int $jrnId, Adapter $dbAdapter) {
                return $dbAdapter->query('select * from sa_journal_entry where jrnId = ?')
                    ->execute([$jrnId])
                    ->getResource()->fetchAll(\PDO::FETCH_ASSOC);
            })
            ->txn(function(array $journal, array $entries, int $jrnId) {
                $txn = (new SplitTransaction(
                    $journal['note'],
                    $journal['src'],
                    (int) $journal['ref'],
                    new \DateTime($journal['date'])
                ))->setId($jrnId);

                foreach ($entries as $entry) {
                    $txn->addEntry(
                        new Entry(
                            new Nominal($entry['nominal']),
                            (int) ($entry['acDr'] == 0 ? $entry['acCr'] : $entry['acDr']),
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
    public function fetchAccountJournals(Nominal $nominal): Set
    {
        return FFor::create(
            [
                'dbAdapter' => $this->dbAdapter,
                'nominal' => $nominal,
                'chartId' => $this->chartId
            ]
        )
            ->sql(function(Adapter $dbAdapter) {return new Sql($dbAdapter);})
            ->select(function(Sql $sql, Nominal $nominal, int $chartId) {
                return $sql->select(['j' => 'sa_journal'])
                    ->join(
                        ['e' => 'sa_journal_entry'],
                        'j.id = e.jrnId',
                        ['nominal', 'acDr', 'acCr']
                    )
                    ->columns(['id', 'note', 'date', 'src', 'ref'])
                    ->where(
                        [
                            'e.nominal' => $nominal->get(),
                            'j.chartId' => $chartId
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
                $jrnId = -1;
                foreach ($entries as $entry) {
                    $entry['id'] = (int) $entry['id'];
                    if ($entry['id'] != $jrnId) {
                        $jrnId = $entry['id'];
                        $txn = new SplitTransaction(
                            $entry['note'],
                            $entry['src'],
                            (int) $entry['ref'],
                            new \DateTime($entry['date'])
                        );
                        $txn->setId($jrnId);
                        $transactions[] = $txn;
                    }
                    $txn->addEntry(
                        new Entry(
                            new Nominal($entry['nominal']),
                            (int) (empty($entry['acDr']) ? $entry['acCr'] : $entry['acDr']),
                            empty($entry['acDr']) ? AccountType::CR() : AccountType::DR()
                        )
                    );
                }

                return new Set($transactions);
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
     * @param string   $name
     * @param Nominal|null $prntNominal
     *
     * @return Accountant
     *
     * @throws DbException
     */
    public function addAccount(
        Nominal $nominal,
        AccountType $type,
        string $name,
        Nominal $prntNominal = null
    ): Accountant
    {
        try {
            $this->dbAdapter->query('call sa_sp_add_ledger(?, ?, ?, ?, ?)')
                ->execute(
                    [
                        $this->chartId,
                        $nominal(),
                        $type->getKey(),
                        $name,
                        is_null($prntNominal) ? '' : $prntNominal()
                    ]
                );

            return $this;
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
     * @return Accountant
     *
     * @throws DbException
     */
    public function delAccount(Nominal $nominal): Accountant
    {
        try {
            $this->dbAdapter->query('call sa_sp_del_ledger(?, ?)')
                ->execute(
                    [
                        $this->chartId,
                        $nominal()
                    ]
                );

            return $this;
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
    protected function buildTreeFromDb(Node $node, array $accounts, $origId): Node
    {
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
                    $childAccount['name'],
                    (int) $childAccount['acDr'],
                    (int) $childAccount['acCr']
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
                return $attributes->getNamedItem('name')->nodeValue;
            })
            ->type(function (\DOMNamedNodeMap $attributes, $accountTypes) {
                return new AccountType(
                    $accountTypes[strtoupper($attributes->getNamedItem('type')->nodeValue)]
                );
            })
            ->fyield('nominal', 'type', 'name');

        $tree->setValue(new Account($nominal, $type, $name, 0, 0));

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
     * @return int  New Chart Id
     */
    protected function storeChart(Chart $chart): int
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
                    "select sa_fu_add_chart('{$chart->getName()}')",
                    Adapter::QUERY_MODE_EXECUTE
                )
                    ->current()
                    ->getArrayCopy();

                return (int) \array_pop($res);
            })
            ->build(function(Node $root, int $chartId, Adapter $dbAdapter) {
                $root->accept(new NodeSaver($chartId, $this->dbAdapter));
            })
            ->fyield('chartId');
    }
}