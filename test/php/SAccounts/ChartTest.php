<?php
/**
 * Simple Double Entry Accounting V2
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Test\SAccounts;

use SAccounts\Account;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\Nominal;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Tree\Node\Node;

class ChartTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Chart
     */
    protected $sut;

    /**
     * @var Node
     */
    protected $tree;

    protected function setUp()
    {
        $tree = new Node(
            new Account(
                new Nominal('0000'),
                AccountType::REAL(),
                new StringType('COA'),
                new IntType(0),
                new IntType(0)
            ),
            [
                new Node(
                    new Account(
                        new Nominal('1000'),
                        AccountType::ASSET(),
                        new StringType('Assets'),
                        new IntType(0),
                        new IntType(0)
                    )
                ),
                new Node(
                    new Account(
                        new Nominal('2000'),
                        AccountType::LIABILITY(),
                        new StringType('Liabilities'),
                        new IntType(0),
                        new IntType(0)
                    )
                )
            ]
        );
        $this->sut = new Chart(new StringType('Foo Chart'), $tree);
    }

    public function testConstructionCreatesChart()
    {
        $this->assertInstanceOf('SAccounts\Chart', $this->sut);
    }

    public function testYouCanGiveAChartAnOptionalTreeInConstruction()
    {
        $tree = new Node();
        $sut = new Chart(new StringType('Foo Chart'), $tree);
        $this->assertInstanceOf('SAccounts\Chart', $sut);
    }


    public function testYouCanGetAnAccountIfItExists()
    {
        $ac = $this->sut->getAccount(new Nominal('2000'));
        $this->assertEquals('2000', $ac->getNominal()->get());
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testTryingToGetANonExistentAccountWillThrowAnException()
    {
        $this->sut->getAccount(new Nominal('9999'));
    }

    public function testYouCanTestIfAChartHasAnAccount()
    {
        $this->assertTrue($this->sut->hasAccount(new Nominal('1000')));
        $this->assertFalse($this->sut->hasAccount(new Nominal('9999')));
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testTryingToGetAParentIdOfANonExistentAccountWillThrowAnException()
    {
        $this->sut->getParentId(new Nominal('9999'));
    }

    public function testGettingTheParentIdOfAnAccountThatHasAParentWillReturnTheParentId()
    {
        $this->assertEquals('0000', $this->sut->getParentId(new Nominal('2000'))->get());
    }

    public function testYouCanProvideAnOptionalInternalIdWhenConstructingAChart()
    {
        $sut = new Chart(
            new StringType('Foo'),
            null,
            new IntType(12)
        );

        $this->assertEquals(12, $sut->id()->get());
    }

    public function testYouCanSetTheChartRootNode()
    {
        $ac1 = new Account(
            new Nominal('9998'),
            AccountType::ASSET(),
            new StringType('Asset'),
            new IntType(0),
            new IntType(0)
        );
        $root = new Node($ac1);
        $this->sut->setRootNode($root);
        $tree = $this->sut->getTree();

        $this->assertEquals($root, $tree);
    }
}
