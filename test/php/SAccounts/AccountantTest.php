<?php
/**
 * Simple Double Entry Accounting V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Test\SAccounts;

use Chippyash\Type\Number\IntType;
use SAccounts\Accountant;
use Chippyash\Type\String\StringType;
use SAccounts\AccountType;
use SAccounts\Nominal;
use SAccounts\Transaction\Entry;
use SAccounts\Transaction\SplitTransaction;
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
    public function testFetchingAChartWhenChartIdIsNotSetWillThrowAnException()
    {
        $this->sut->fetchChart();
    }

    public function testYouCanWriteATransactionToAJournalAndUpdateAChart()
    {
        $chartId = $this->createChart();
        $txn = new SplitTransaction(new StringType('test'), new IntType(10));
        $txn->addEntry(new Entry(new Nominal('7100'),new IntType(1226), AccountType::DR()))
            ->addEntry(new Entry(new Nominal('2110'),new IntType(1226), AccountType::CR()));

        $txnId = $this->sut->writeTransaction($txn);
        $journal = $this->adapter->query("select * from sa_journal where id = ?")
            ->execute([$txnId()])
            ->current();
        $this->assertEquals($chartId, $journal['chartId']);
        $this->assertEquals('test', $journal['note']);
        $this->assertEquals(10, $journal['ref']);

        $entries = $this->adapter->query('select * from sa_journal_entry where jrnId = ?')
            ->execute([$txnId()]);
        $this->assertEquals(2, $entries->count());
        $entries = $entries->getResource()->fetchAll(\PDO::FETCH_ASSOC);

        $drEntry = array_filter(
            $entries,
            function($entry) {
                return $entry['nominal'] == '7100';
            }
        );

        $drEntry = array_pop($drEntry);
        $this->assertEquals($txnId, $drEntry['jrnId']);
        $this->assertEquals(1226, $drEntry['acDr']);
        $this->assertEquals(0, $drEntry['acCr']);

        $crEntry = array_filter(
            $entries,
            function($entry) {
                return $entry['nominal'] == '2110';
            }
        );
        $crEntry = array_pop($crEntry);
        $this->assertEquals($txnId, $crEntry['jrnId']);
        $this->assertEquals(1226, $crEntry['acCr']);
        $this->assertEquals(0, $crEntry['acDr']);

        $chart = $this->sut->fetchChart();
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('7100'))->getBalance()->get()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('7000'))->getBalance()->get()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('5000'))->dr()->get()
        );
        $this->assertEquals(
            -1226,
            $chart->getAccount(new Nominal('2110'))->getBalance()->get()
        );
        $this->assertEquals(
            -1226,
            $chart->getAccount(new Nominal('2100'))->getBalance()->get()
        );
        $this->assertEquals(
            -1226,
            $chart->getAccount(new Nominal('2000'))->getBalance()->get()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('1000'))->cr()->get()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('0000'))->dr()->get()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('0000'))->cr()->get()
        );
    }

    /**
     * @expectedException \SAccounts\AccountsException
     * @expectedExceptionMessage Chart id not set
     */
    public function testWritingATransactionWhenChartIdIsNotSetWillThrowAnException()
    {
        $txn = new SplitTransaction(new StringType('test'), new IntType(10));
        $txn->addEntry(new Entry(new Nominal('7100'),new IntType(1226), AccountType::DR()))
            ->addEntry(new Entry(new Nominal('2110'),new IntType(1226), AccountType::CR()));

        $this->sut->writeTransaction($txn);
    }

    public function testYouCanFetchAJournalTransactionByItsId()
    {
        $chartId = $this->createChart();
        $txn = new SplitTransaction(new StringType('test'), new IntType(10));
        $txn->addEntry(new Entry(new Nominal('7100'),new IntType(1226), AccountType::DR()))
            ->addEntry(new Entry(new Nominal('2110'),new IntType(1226), AccountType::CR()));

        $txnId = $this->sut->writeTransaction($txn);

        $storedTxn = $this->sut->fetchTransaction($txnId);
        $this->assertInstanceOf('SAccounts\Transaction\SplitTransaction', $storedTxn);

        $this->assertEquals(10, $storedTxn->getRef()->get());
        $this->assertEquals('test', $storedTxn->getNote()->get());
        $this->assertEquals($txnId, $storedTxn->getId());

        /** @var Entry $drAc */
        $drAc = $storedTxn->getEntry($storedTxn->getDrAc()[0]);
        $this->assertEquals('7100', $drAc->getId()->get());
        $this->assertEquals(1226, $drAc->getAmount()->get());
        $this->assertTrue($drAc->getType()->equals(AccountType::DR()));
        /** @var Entry $crAc */
        $crAc = $storedTxn->getEntry($storedTxn->getCrAc()[0]);
        $this->assertEquals('2110', $crAc->getId()->get());
        $this->assertEquals(1226, $crAc->getAmount()->get());
        $this->assertTrue($crAc->getType()->equals(AccountType::CR()));
    }

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
