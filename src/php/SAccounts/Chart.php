<?php
/**
 * Simple Double Entry Accounting V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Chippyash\Identity\Identifying;
use Chippyash\Identity\Identifiable;
use Monad\FTry;
use Monad\Match;
use Tree\Node\Node;
use SAccounts\Visitor\NodeFinder;

/**
 * A Chart of Accounts
 */
class Chart implements Identifiable
{
    use Identifying;

    /**@+
     * Exception error messages
     */
    const ERR_INVALAC = 'Invalid account nominal identifier';
    /**@-*/

    /**
     * Tree of accounts
     * @var Node
     */
    protected $tree;

    /**
     * Name of this chart
     * @var StringType
     */
    protected $chartName;

    /**
     * Constructor
     *
     * @param StringType $name Chart Name
     * @param Node $tree Tree of accounts
     * @param IntType|null $internalId default == 0
     */
    public function __construct(
        StringType $name,
        Node $tree = null,
        IntType $internalId = null
    ) {
        $this->chartName = $name;
        $this->tree = Match::on($tree)
            ->Tree_Node_Node($tree)
            ->null(new Node())
            ->value();
        $this->id = $internalId;
    }

    /**
     * Get an account from the chart
     *
     * @param Nominal $nId
     *
     * @return Account|null
     */
    public function getAccount(Nominal $nId)
    {
        return Match::on($this->tryGetNode($nId, self::ERR_INVALAC))
            ->Monad_FTry_Success(function ($account) {
                return FTry::with($account->flatten()->getValue());
            })
            ->value()
            ->pass()
            ->value();
    }

    /**
     * Does this chart have specified account
     *
     * @param Nominal $nId
     * @return bool
     */
    public function hasAccount(Nominal $nId)
    {
        return Match::on(FTry::with(function () use ($nId) {
            $this->getAccount($nId);
        }))
            ->Monad_FTry_Success(true)
            ->Monad_FTry_Failure(false)
            ->value();
    }

    /**
     * Get Id of parent for an account
     *
     * @param Nominal $nId
     * @return null|IntType
     *
     * @throws AccountsException
     */
    public function getParentId(Nominal $nId)
    {
        return Match::on(
            Match::on($this->tryGetNode($nId, self::ERR_INVALAC))
                ->Monad_FTry_Success(function ($node) {
                    return Match::on($node->flatten()->getParent());
                })
                ->value()
                ->pass()
                ->value()
        )
            ->Tree_Node_Node(function ($node) {
                /** @var Account $v */
                $v = $node->getValue();
                return is_null($v) ? null : $v->getNominal();
            })
            ->value();
    }

    /**
     * Return account tree
     *
     * @return Node
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Return chart name
     *
     * @return StringType
     */
    public function getName()
    {
        return $this->chartName;
    }

    /**
     * Set the Chart's root node
     *
     * @param Node $root
     *
     * @return $this
     */
    public function setRootNode(Node $root)
    {
        $this->tree = $root;

        return $this;
    }

    /**
     * @param Nominal $nId
     * @param $exceptionMessage
     *
     * @return FTry
     */
    protected function tryGetNode(Nominal $nId, $exceptionMessage)
    {
        return FTry::with(function () use ($nId, $exceptionMessage) {
            $node = $this->findNode($nId);
            if (is_null($node)) {
                throw new AccountsException($exceptionMessage);
            }
            return $node;
        });
    }

    /**
     * Find an account node using its nominal code
     *
     * @param Nominal $nId
     * @return node|null
     */
    protected function findNode(Nominal $nId)
    {
        return $this->tree->accept(new NodeFinder($nId));
    }
}
