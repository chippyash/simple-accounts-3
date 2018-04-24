<?php
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace SAccounts\Visitor;

use Chippyash\Currency\Currency;
use SAccounts\Account;
use SAccounts\AccountType;
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
        $nominal = str_pad($ac->getNominal(), 8);
        $name = str_pad($ac->getName(), 20);
        $dr = str_pad($this->crcy->set($ac->dr()->get())->display(), 15, ' ', STR_PAD_LEFT);
        $cr = str_pad($this->crcy->set($ac->cr()->get())->display(), 15, ' ', STR_PAD_LEFT);
        $balStr = ($ac->getType()->equals(AccountType::REAL()) ? $this->crcy->set(0)->display() : $this->crcy->set($ac->getBalance()->get())->display());
        $balance = str_pad($balStr, 15, ' ', STR_PAD_LEFT);
        echo "{$nominal}{$name}{$dr}{$cr}{$balance}\n";

        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }
}
