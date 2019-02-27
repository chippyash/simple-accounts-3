<?php
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license   BSD-3-Clause See LICENSE.md
 */
namespace SAccounts\Visitor;

use Assembler\FFor;
use Chippyash\Currency\Currency;
use SAccounts\Account;
use SAccounts\AccountType;
use Tree\Node\NodeInterface;
use Tree\Visitor\Visitor;

/**
 * Utility visitor to return Chart as array of entries + balances
 */
class ChartArray implements Visitor
{
    /**
     * @var Currency
     */
    protected $crcy;
    /**
     * @var bool
     */
    protected $asInt;

    /**
     * ChartArray constructor.
     *
     * @param Currency|null $crcy If set, use Currency precision to return values
     *                            as float, else return values as integers
     */
    public function __construct(Currency $crcy = null)
    {
        $this->crcy = !is_null($crcy) ? $crcy : new Currency(0,'','',0);
        $this->asInt = is_null($crcy);
    }

    /**
     * @param NodeInterface $node
     *
     * @return array [[nominal, acName, dr, cr, balance],...]
     */
    public function visit(NodeInterface $node)
    {
        return FFor::create([
             'ret' => [],
             'node' => $node,
             'ac' => $node->getValue()
            ])
            ->dr(function($ac){
                return $this->asInt
                    ? $ac->dr()->get()
                    : $this->crcy->set($ac->dr()->get())->getAsFloat();
            })
            ->cr(function($ac){
                return $this->asInt
                    ? $ac->cr()->get()
                    : $this->crcy->set($ac->cr()->get())->getAsFloat();
            })
            ->balance(function($ac){
                return $this->asInt
                    ? $ac->getBalance()->get()
                    : $this->crcy->set($ac->getBalance()->get())->getAsFloat();
            })
            ->loop(function($dr, $cr, $balance, $ac, $node, $ret) {
                $ret[] = [
                    $ac->getNominal()->get(),
                    $ac->getName()->get(),
                    $dr,
                    $cr,
                    $balance
                ];

                foreach ($node->getChildren() as $child) {
                    $ret = array_merge($ret, $child->accept($this));
                }
                return $ret;
            })
            ->fyield('loop');
    }
}
