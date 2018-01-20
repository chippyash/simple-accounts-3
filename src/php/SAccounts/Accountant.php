<?php
/**
 * Simple Double Entry Bookkeeping V2
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Zend\Db\Adapter\Adapter;
use Assembler\FFor;
use Tree\Node\Node;
use SAccounts\Visitor\NodeSaver;

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
     * Create a new Chart from a definition and staore it in the database
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
     *
     * @return IntType Transaction Id
     * @throws AccountsException
     */
    public function writeTransaction(SplitTransaction $txn)
    {
        return FFor::create()
            ->txn(function () use ($journal, $txn) {
                return $journal->write($txn);
            })
            ->chart(function ($txn) use ($chart) {
                $chart->getAccount($txn->getDrAc()[0])->debit($txn->getAmount());
                $chart->getAccount($txn->getCrAc()[0])->credit($txn->getAmount());
            })
            ->fyield('txn');
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