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
use SAccounts\Accountant;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\Journal;
use SAccounts\Nominal;
use SAccounts\Transaction;
use Chippyash\Type\Number\IntType;
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


//    public function testAnAccountantCanFetchAChart()
//    {
//        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), CurrencyFactory::create('gbp')));
//        $this->fileClerk->expects($this->once())
//            ->method('fetch')
//            ->will($this->returnValue($chart));
//        $this->assertInstanceOf(
//            'SAccounts\Chart',
//            $this->sut->fetchChart(new StringType('foo bar'), new IntType(1))
//        );
//    }

    public function testAnAccountantCanCreateANewChartOfAccounts()
    {
        $def = $this->getMockBuilder('SAccounts\ChartDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<chart  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="chart-definition.xsd"
        name="Personal">
    <account id="1" nominal="0000" type="real" name="COA" status="active">
        <account id="2" nominal="1000" type="real" name="Balance Sheet" status="active">
            <account id="3" nominal="2000" type="asset" name="Assets" status="active">
                <account id="4" nominal="2100" type="bank" name="At Bank" status="active">
                    <account id="5" nominal="2110" type="bank" name="Current Account" status="active"/>
                    <account id="6" nominal="2120" type="bank" name="Savings Account" status="active"/>
                </account>
            </account>
            <account id="7" nominal="3000" type="liability" name="Liabilities" status="active">
                <account id="8" nominal="3100" type="equity" name="Equity" status="active">
                    <account id="9" nominal="3110" type="equity" name="Opening Balance" status="active"/>
                </account>
                <account id="10" nominal="3200" type="liability" name="Loans" status="active">
                    <account id="11" nominal="3210" type="liability" name="Mortgage" status="active"/>
                </account>
            </account>
        </account>
        <account id="12" nominal="5000" type="real" name="Profit And Loss" status="active">
            <account id="13" nominal="6000" type="income" name="Income" status="active">
                <account id="14" nominal="6100" type="income" name="Salary" status="active"/>
                <account id="15" nominal="6200" type="income" name="Interest Received" status="active"/>
            </account>
            <account id="16" nominal="7000" type="expense" name="Expenses" status="active">
                <account id="17" nominal="7100" type="expense" name="House" status="active"/>
                <account id="18" nominal="7200" type="expense" name="Travel" status="active"/>
                <account id="19" nominal="7300" type="expense" name="Insurance" status="active"/>
                <account id="20" nominal="7400" type="expense" name="Food" status="active"/>
                <account id="21" nominal="7500" type="expense" name="Leisure" status="active"/>
                <account id="22" nominal="7600" type="expense" name="Interest Payments" status="active"/>
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
        $ret = $this->sut->createChart(new StringType('Personal'), $def);
        $this->assertInstanceOf('Chippyash\Type\Number\IntType', $ret);

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
}
