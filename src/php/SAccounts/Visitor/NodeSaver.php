<?php

declare(strict_types=1);

/**
 * Simple Double Entry Accounting V3

 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license BSD-3-Clause See LICENSE.md
 */
namespace SAccounts\Visitor;

use SAccounts\Account;
use Tree\Node\NodeInterface;
use Tree\Visitor\Visitor;
use Zend\Db\Adapter\Adapter;

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

    public function __construct(int $chartId, Adapter $db)
    {
        $this->chartId = $chartId;
        $this->db = $db;
        $this->prnt = null;
    }

    /**
     * @param NodeInterface $node
     *
     * @return Account|null
     */
    public function visit(NodeInterface $node): ?Account
    {
        /** @var Account $currAc */
        $currAc = $node->getValue();

        $nominal = $currAc->getNominal()->get();
        $type = $currAc->getType()->getKey();
        $name = $currAc->getName();
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
