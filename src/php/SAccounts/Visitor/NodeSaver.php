<?php
/**
 * Simple Double Entry Accounting V3
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Visitor;

use Chippyash\Type\Number\IntType;
use Tree\Visitor\Visitor;
use Tree\Node\NodeInterface;
use Zend\Db\Adapter\Adapter;
use SAccounts\Account;

/**
 * Save a node into account ledger
 */
class NodeSaver implements Visitor
{
    /**
     * @var int
     */
    protected $chartId;
    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(IntType $chartId, Adapter $db)
    {
        $this->chartId = $chartId();
        $this->db = $db;
        $this->prnt = null;
    }

    /**
     * @param NodeInterface $node
     *
     * @return Account|null
     */
    public function visit(NodeInterface $node)
    {
        /** @var Account $currAc */
        $currAc = $node->getValue();

        $nominal = $currAc->getNominal()->get();
        $type = $currAc->getType()->getKey();
        $name = $currAc->getName()->get();
        $prntNominal = ($node->isRoot() ? '' : $node->getParent()->getValue()->getNominal()->get());

        $this->db->query(
            "call sa_sp_add_ledger({$this->chartId}, '{$nominal}', '{$type}', '{$name}', '{$prntNominal}')",
            Adapter::QUERY_MODE_EXECUTE
        );

        foreach ($node->getChildren() as $child) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $child->accept($this);
        }

        return $currAc;
    }
}