<?php
declare(strict_types=1);
/**
 * Simple Double Entry Accounting V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Test\SAccounts;

use SAccounts\Account;
use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\Nominal;
use Tree\Node\Node;

class ChartTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var Chart
     */
    protected $sut;

    /**
     * @var Node
     */
    protected $tree;

    protected function setUp(): void
    {
        $tree = new Node(
            new Account(
                new Nominal('0000'),
                AccountType::REAL(),
                'COA',
                0,
                0
            ),
            [
                new Node(
                    new Account(
                        new Nominal('1000'),
                        AccountType::ASSET(),
                        'Assets',
                        0,
                        0
                    )
                ),
                new Node(
                    new Account(
                        new Nominal('2000'),
                        AccountType::LIABILITY(),
                        'Liabilities',
                        0,
                        0
                    )
                )
            ]
        );
        $this->sut = new Chart('Foo Chart', $tree);
    }

    public function testConstructionCreatesChart()
    {
        $this->assertInstanceOf('SAccounts\Chart', $this->sut);
    }

    public function testYouCanGiveAChartAnOptionalTreeInConstruction()
    {
        $tree = new Node();
        $sut = new Chart('Foo Chart', $tree);
        $this->assertInstanceOf(Chart::class, $sut);
    }


    public function testYouCanGetAnAccountIfItExists()
    {
        $ac = $this->sut->getAccount(new Nominal('2000'));
        $this->assertEquals('2000', $ac->getNominal()->get());
    }

    public function testTryingToGetANonExistentAccountWillThrowAnException()
    {
        $this->expectException(AccountsException::class);
        $this->sut->getAccount(new Nominal('9999'));
    }

    public function testYouCanTestIfAChartHasAnAccount()
    {
        $this->assertTrue($this->sut->hasAccount(new Nominal('1000')));
        $this->assertFalse($this->sut->hasAccount(new Nominal('9999')));
    }

    public function testTryingToGetAParentIdOfANonExistentAccountWillThrowAnException()
    {
        $this->expectException(AccountsException::class);
        $this->sut->getParentId(new Nominal('9999'));
    }

    public function testGettingTheParentIdOfAnAccountThatHasAParentWillReturnTheParentNominal()
    {
        $this->assertEquals('0000', $this->sut->getParentId(new Nominal('2000'))->get());
    }

    public function testYouCanProvideAnOptionalInternalIdWhenConstructingAChart()
    {
        $sut = new Chart(
            'Foo',
            null,
            12
        );

        $this->assertEquals(12, $sut->id());
    }

    public function testYouCanSetTheChartRootNode()
    {
        $ac1 = new Account(
            new Nominal('9998'),
            AccountType::ASSET(),
            'Asset',
            0,
            0
        );
        $root = new Node($ac1);
        $this->sut->setRootNode($root);
        $tree = $this->sut->getTree();

        $this->assertEquals($root, $tree);
    }
}
