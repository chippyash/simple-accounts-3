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
use SAccounts\Visitor\ChartArray;
use Tree\Node\Node;

class ChartArrayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Node
     */
    protected $tree;

    protected function setUp()
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
    }

    public function testConstructingWithNoCurrencyWillReturnIntegerValues()
    {
        $sut = new ChartArray();
        $result = $this->tree->accept($sut);
        $expected = [
            ['0000', 'COA', 1001, 1001, 0],
            ['1000', 'Assets', 1001, 0, 1001],
            ['2000', 'Liabilities', 0, 1001, 1001]
        ];
        $this->assertEquals($expected, $result);
    }

    public function testConstructingWithACurrencyWillReturnFloatsDependentOnTheCurrencyPrecision()
    {
        $sut = new ChartArray(new Currency(0,'','',2));
        $result = $this->tree->accept($sut);
        $expected = [
            ['0000', 'COA', 10.01, 10.01, 0],
            ['1000', 'Assets', 10.01, 0, 10.01],
            ['2000', 'Liabilities', 0, 10.01, 10.01]
        ];
        $this->assertEquals($expected, $result);
    }
}
