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
use PDOException;
use Zend\Db\Adapter\Adapter;
use Assembler\FFor;
use Tree\Node\Node;
use SAccounts\Visitor\NodeSaver;
use SAccounts\Transaction\SplitTransaction;
use SAccounts\Transaction\Entry;
use Zend\Db\Adapter\Exception\InvalidQueryException;

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
        /* @var IntType $chartId */
        $chartId = FFor::create(['def' => $def, 'chartName' => $chartName])
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

        $this->chartId = $chartId;
        
        return $chartId;
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

        $chartId = $this->chartId->get();
        $accounts = $this->dbAdapter
            ->query(
                "call sa_sp_get_tree('{$chartId}')",
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
        $chartName = $this->dbAdapter
            ->query(
                "select name from sa_coa where id = {$chartId}",
                Adapter::QUERY_MODE_EXECUTE)
            ->current()
            ->offsetGet('name');
        $rootAc = array_shift($accounts);
        $root = new Node(
            new Account(
                new Nominal($rootAc['nominal']),
                AccountType::$rootAc['type'](),
                new StringType($rootAc['name']),
                new IntType($rootAc['acDr']),
                new IntType($rootAc['acCr'])
            )
        );

        $root = $this->buildTreeFromDb(
            $root, $accounts, $rootAc['destid']
        );

        return new Chart(new StringType($chartName), $root, $this->chartId);
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

        $txns = $txn->getEntries()->toArray();
        $stmnt = $this->dbAdapter->query(
            "select sa_fu_add_txn(?, ?, ?, ?, ?, ?, ?, ?) as txnId",
            Adapter::QUERY_MODE_PREPARE
        );

        return new IntType(
            $stmnt->execute(
                [
                    $this->chartId->get(),
                    $txn->getNote()->get(),
                    is_null($dateTime) ? $dateTime : $dateTime->format('Y-m-d h:m:s'),
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
        $journal = $this->dbAdapter->query('select * from sa_journal where id = ?')
            ->execute([$jrnId])
            ->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        $journal = array_pop($journal);
        $entries = $this->dbAdapter->query('select * from sa_journal_entry where jrnId = ?')
            ->execute([$jrnId()])
            ->getResource()->fetchAll(\PDO::FETCH_ASSOC);

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
                    AccountType::$childAccount['type'](),
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
        $name = $chart->getName()->get();
        $res = $this->dbAdapter->query(
            "select sa_fu_add_chart('{$name}')",
            Adapter::QUERY_MODE_EXECUTE
        )->current()->getArrayCopy();
        $chartId = new IntType(array_pop($res));
        $root = $chart->getTree();

        $root->accept(new NodeSaver($chartId, $this->dbAdapter));

        return $chartId;
    }
}