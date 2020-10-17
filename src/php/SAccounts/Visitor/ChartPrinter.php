<?php
declare(strict_types=1);
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license   BSD-3-Clause See LICENSE.md
 */
namespace SAccounts\Visitor;

use Chippyash\Currency\Currency;
use SAccounts\Account;
use Tree\Node\NodeInterface;
use Tree\Visitor\Visitor;

/**
 * Utility visitor to print chart to console
 */
class ChartPrinter implements Visitor
{
    /**
     * @var Currency
     */
    protected $crcy;
    /**
     * @var bool
     */
    protected $headerPrinted;

    public function __construct(Currency $crcy)
    {
        $this->crcy = $crcy;
        $this->headerPrinted = false;
    }

    /**
     * @param NodeInterface $node
     *
     * @return mixed
     */
    public function visit(NodeInterface $node)
    {
        if (!$this->headerPrinted) {
            echo "Nominal Name                     DR            CR            Balance\n";
            $this->headerPrinted = true;
        }

        /** @var Account $ac */
        $ac = $node->getValue();
        $nominal = str_pad($ac->getNominal()->get(), 8);
        $name = str_pad($ac->getName(), 20);
        $dr = str_pad($this->crcy->set($ac->dr())->display()->get(), 15, ' ', STR_PAD_LEFT);
        $cr = str_pad($this->crcy->set($ac->cr())->display()->get(), 15, ' ', STR_PAD_LEFT);
        $balStr = $this->crcy->set($ac->getBalance())->display()->get();
        $balance = str_pad($balStr, 15, ' ', STR_PAD_LEFT);
        echo "{$nominal}{$name}{$dr}{$cr}{$balance}\n";

        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }
}
