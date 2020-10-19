<?php
/**
 * Simple Double Entry Accounting V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Test\SAccounts;

use Ds\Set;
use SAccounts\Accountant;
use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\ChartDefinition;
use SAccounts\DbException;
use SAccounts\Nominal;
use SAccounts\Transaction\Entry;
use SAccounts\Transaction\SimpleTransaction;
use SAccounts\Transaction\SplitTransaction;
use Zend\Db\Adapter\Adapter;


class AccountantTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var Accountant
     */
    protected $sut;
    /**
     * @var Adapter
     */
    protected $adapter;

    protected function setUp(): void
    {
        $config = [
            'driver' => 'Pdo_mysql',
            'database' => DBNAME,
            'username' => DBUID,
            'password' => DBPWD,
            'host' => DBHOST
        ];
        $this->adapter = new Adapter($config);
        
        $this->sut = new Accountant($this->adapter);

        $this->adapter->query('delete from sa_coa', Adapter::QUERY_MODE_EXECUTE);
    }

    public function testAnAccountantCanCreateANewChartOfAccounts()
    {
        $this->assertIsInt($this->createChart());
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

    public function testFetchingAChartWhenChartIdIsNotSetWillThrowAnException()
    {
        $this->expectException(AccountsException::class);
        $this->expectExceptionMessage('Chart id not set');
        $this->sut->fetchChart();
    }

    public function testYouCanWriteATransactionToAJournalAndUpdateAChart()
    {
        $chartId = $this->createChart();
        $txn = new SplitTransaction('test', 'PUR', 10);
        $txn->addEntry(new Entry(new Nominal('7100'),1226, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('2110'),1226, AccountType::CR()));

        $txnId = $this->sut->writeTransaction($txn);
        $journal = $this->adapter->query("select * from sa_journal where id = ?")
            ->execute([$txnId])
            ->current();
        $this->assertEquals($chartId, $journal['chartId']);
        $this->assertEquals('test', $journal['note']);
        $this->assertEquals(10, $journal['ref']);

        $entries = $this->adapter->query('select * from sa_journal_entry where jrnId = ?')
            ->execute([$txnId]);
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
            $chart->getAccount(new Nominal('7100'))->getBalance()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('7000'))->getBalance()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('5000'))->dr()
        );
        $this->assertEquals(
            -1226,
            $chart->getAccount(new Nominal('2110'))->getBalance()
        );
        $this->assertEquals(
            -1226,
            $chart->getAccount(new Nominal('2100'))->getBalance()
        );
        $this->assertEquals(
            -1226,
            $chart->getAccount(new Nominal('2000'))->getBalance()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('1000'))->cr()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('0000'))->dr()
        );
        $this->assertEquals(
            1226,
            $chart->getAccount(new Nominal('0000'))->cr()
        );
    }

    public function testWritingATransactionWhenChartIdIsNotSetWillThrowAnException()
    {
        $txn = new SplitTransaction('test', 'PUR', 10);
        $txn->addEntry(new Entry(new Nominal('7100'),1226, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('2110'),1226, AccountType::CR()));

        $this->expectException(AccountsException::class);
        $this->expectExceptionMessage('Chart id not set');
        $this->sut->writeTransaction($txn);
    }

    public function testYouCanFetchAJournalTransactionByItsId()
    {
        $chartId = $this->createChart();
        $txn = new SplitTransaction('test', 'PUR', 10);
        $txn->addEntry(new Entry(new Nominal('7100'),1226, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('2110'),1226, AccountType::CR()));

        $txnId = $this->sut->writeTransaction($txn);

        $storedTxn = $this->sut->fetchTransaction($txnId);
        $this->assertInstanceOf(SplitTransaction::class, $storedTxn);

        $this->assertEquals(10, $storedTxn->getRef());
        $this->assertEquals('test', $storedTxn->getNote());
        $this->assertEquals($txnId, $storedTxn->getId());

        /** @var Entry $drAc */
        $drAc = $storedTxn->getEntry($storedTxn->getDrAc()[0]);
        $this->assertEquals('7100', $drAc->getId()->get());
        $this->assertEquals(1226, $drAc->getAmount());
        $this->assertTrue($drAc->getType()->equals(AccountType::DR()));
        /** @var Entry $crAc */
        $crAc = $storedTxn->getEntry($storedTxn->getCrAc()[0]);
        $this->assertEquals('2110', $crAc->getId()->get());
        $this->assertEquals(1226, $crAc->getAmount());
        $this->assertTrue($crAc->getType()->equals(AccountType::CR()));
    }

    public function testYouCanAddAnAccountToAChart()
    {
        $this->createChart();
        $nominal = new Nominal('7700');
        $prntNominal = new Nominal(('7000'));
        $chartBefore = $this->sut->fetchChart();

        $this->sut->addAccount($nominal, AccountType::EXPENSE(), 'foo', $prntNominal);
        $chartAfter = $this->sut->fetchChart();

        $this->assertFalse($chartBefore->hasAccount($nominal));
        $this->assertTrue($chartAfter->hasAccount($nominal));
    }

    public function testAddingAnAccountToANonExistentParentWillThrowAnException()
    {
        $this->createChart();
        $nominal = new Nominal('7700');
        $prntNominal = new Nominal(('9999'));

        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Invalid parent account nominal');
        $this->sut->addAccount($nominal, AccountType::EXPENSE(), 'foo', $prntNominal);

    }

    public function testTryingToAddASecondRootAccountWillThrowAnException()
    {
        $this->createChart();
        $nominal = new Nominal(('9999'));

        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Chart already has root account');
        $this->sut->addAccount($nominal, AccountType::EXPENSE(), 'foo');
    }

    public function testYouCanDeleteAZeroBalanceAccount()
    {
        $this->createChart();
        $nominal = new Nominal('3000');
        $chartBefore = $this->sut->fetchChart();
        $this->sut->delAccount($nominal);
        $chartAfter = $this->sut->fetchChart();

        $this->assertTrue($chartBefore->hasAccount($nominal));
        $this->assertFalse($chartAfter->hasAccount($nominal));
    }

    public function testDeletingANonZeroBalanceAccountWillThrowAnException()
    {
        $this->createChart();
        $nominal = new Nominal('3000');
        $txn = new SimpleTransaction(new Nominal('2110'), new Nominal('3100'),1226);
        $this->sut->writeTransaction($txn);

        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Account balance is non zero');
        $this->sut->delAccount($nominal);
    }

    public function testYouCanFetchJournalEntriesForAnAccount()
    {
        $this->createChart();
        $this->sut->writeTransaction(
            new SimpleTransaction(
                new Nominal('2110'), new Nominal('3100'),1226
            )
        );
        $this->sut->writeTransaction(
            new SimpleTransaction(
                new Nominal('3100'), new Nominal('2110'),1000
            )
        );
        $entries = $this->sut->fetchAccountJournals(new Nominal('3100'));

        $this->assertEquals(2, $entries->count());
    }

    public function testFetchingJournalEntriesReturnsASetOfSplitTransactions()
    {
        $this->createChart();
        $this->sut->writeTransaction(
            new SimpleTransaction(
                new Nominal('2110'), new Nominal('3100'),1226
            )
        );
        $entries = $this->sut->fetchAccountJournals(new Nominal('3100'));

        $this->assertInstanceOf(Set::class, $entries);
        $this->assertInstanceOf(SplitTransaction::class, $entries[0]);
    }

    public function testFetchingJournalEntriesForAnAggregateAccountWillReturnAnEmptySet()
    {
        $this->createChart();
        $this->sut->writeTransaction(
            new SimpleTransaction(
                new Nominal('2110'), new Nominal('3100'),1226
            )
        );
        $entries = $this->sut->fetchAccountJournals(new Nominal('3000'));

        $this->assertEquals(0, $entries->count());
    }

    protected function createChart()
    {
        $def = $this->getMockBuilder(ChartDefinition::class)
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

        return $this->sut->createChart('Personal', $def);
    }
}
