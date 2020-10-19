<?php
declare(strict_types=1);
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace Test\SAccounts\Visitor;

use Chippyash\Currency\Currency;
use SAccounts\Account;
use SAccounts\AccountType;
use SAccounts\Nominal;
use Tree\Node\Node;
use SAccounts\Visitor\ChartPrinter;

class ChartPrinterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ChartPrinter
     */
    protected $sut;

    /**
     * @var Node
     */
    protected $tree;

    protected function setUp(): void
    {
        $this->tree = new Node(
            new Account(
                new Nominal('0000'),
                AccountType::REAL(),
                'COA',
                1001,
                1001
            ),
            [
                new Node(
                    new Account(
                        new Nominal('1000'),
                        AccountType::ASSET(),
                        'Assets',
                        1001,
                        0
                    )
                ),
                new Node(
                    new Account(
                        new Nominal('2000'),
                        AccountType::LIABILITY(),
                        'Liabilities',
                        0,
                        1001
                    )
                )
            ]
        );

        $this->sut = new ChartPrinter(new Currency(0, 'GBP', '£'));
    }

    public function testTheOutputIsSentToTheConsole()
    {
        $this->expectOutputRegex('/.*Nominal.*/');
        $this->tree->accept($this->sut);
    }

    public function testOutputIsFormattedUsingTheCurrencySymbol()
    {
        $this->expectOutputRegex('/.*£.*/');
        $this->tree->accept($this->sut);
    }
}
