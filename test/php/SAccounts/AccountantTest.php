<?php
/**
 * Simple Double Entry Accounting V2
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Test\SAccounts;

use SAccounts\Accountant;
use Chippyash\Type\String\StringType;
use Zend\Db\Adapter\Adapter;


class AccountantTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Accountant
     */
    protected $sut;
    /**
     * @var Adapter
     */
    protected $adapter;

    protected function setUp()
    {
        $this->adapter = new Adapter(
            [
                'driver' => 'Pdo_mysql',
                'database' => 'test',
                'username' => 'test',
                'password' => 'test'
            ]
        );
        
        $this->sut = new Accountant($this->adapter);

        $this->adapter->query('delete from sa_coa', Adapter::QUERY_MODE_EXECUTE);
    }

    public function testAnAccountantCanCreateANewChartOfAccounts()
    {
        $this->assertInstanceOf(
            'Chippyash\Type\Number\IntType',
            $this->createChart()
        );
    }

    public function testAnAccountantCanFetchAChart()
    {
        $chartId = $this->createChart();
        $chart = $this->sut->fetchChart();
        $this->assertInstanceOf(
            'SAccounts\Chart',
            $chart
        );
        $this->assertEquals($chartId, $chart->id());
    }

    /**
     * @expectedException \SAccounts\AccountsException
     * @expectedExceptionMessage Chart id not set
     */
    public function testFetchingAChartWhenCahrtIdIsNotSetWillThrowAnException()
    {
        $this->sut->fetchChart();
    }
//
//    public function testYouCanWriteATransactionToAJournalAndUpdateAChart()
//    {
//        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), CurrencyFactory::create('gbp')));
//        $chart->addAccount(new Account($chart, new Nominal('0000'),AccountType::DR(), new StringType('Foo')));
//        $chart->addAccount(new Account($chart, new Nominal('0001'),AccountType::CR(), new StringType('Bar')));
//        $journal = new Journal(new StringType('Foo Journal'), CurrencyFactory::create('gbp'), $this->journalist);
//        $txn = new Transaction(new Nominal('0000'), new Nominal('0001'), CurrencyFactory::create('gbp', 12.26));
//        $this->journalist->expects($this->once())
//            ->method('writeTransaction')
//            ->will($this->returnValue(new IntType(1)));
//
//        $returnedTransaction = $this->sut->writeTransaction($txn, $chart, $journal);
//        $this->assertEquals(1, $returnedTransaction->getId()->get());
//        $this->assertEquals(1226, $chart->getAccount(new Nominal('0000'))->getDebit()->get());
//        $this->assertEquals(1226, $chart->getAccount(new Nominal('0001'))->getCredit()->get());
//    }

    protected function createChart()
    {
        $def = $this->getMockBuilder('SAccounts\ChartDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<chart  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="chart-definition.xsd"
        name="Personal">
    <account nominal="0000" type="real" name="COA" >
        <account nominal="1000" type="real" name="Balance Sheet" >
            <account nominal="2000" type="asset" name="Assets" >
                <account nominal="2100" type="bank" name="At Bank" >
                    <account nominal="2110" type="bank" name="Current Account" />
                    <account nominal="2120" type="bank" name="Savings Account" />
                </account>
            </account>
            <account nominal="3000" type="liability" name="Liabilities" >
                <account nominal="3100" type="equity" name="Equity" >
                    <account nominal="3110" type="equity" name="Opening Balance" />
                </account>
                <account nominal="3200" type="liability" name="Loans" >
                    <account nominal="3210" type="liability" name="Mortgage" />
                </account>
            </account>
        </account>
        <account nominal="5000" type="real" name="Profit And Loss" >
            <account nominal="6000" type="income" name="Income" >
                <account nominal="6100" type="income" name="Salary" />
                <account nominal="6200" type="income" name="Interest Received" />
            </account>
            <account nominal="7000" type="expense" name="Expenses" >
                <account nominal="7100" type="expense" name="House" />
                <account nominal="7200" type="expense" name="Travel" />
                <account nominal="7300" type="expense" name="Insurance" />
                <account nominal="7400" type="expense" name="Food" />
                <account nominal="7500" type="expense" name="Leisure" />
                <account nominal="7600" type="expense" name="Interest Payments" />
            </account>
        </account>
    </account>
</chart>
EOT;
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $def->expects($this->once())
            ->method('getDefinition')
            ->willReturn($dom);

        return $this->sut->createChart(new StringType('Personal'), $def);
    }
}
