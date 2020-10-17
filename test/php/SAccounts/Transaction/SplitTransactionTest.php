<?php
declare(strict_types=1);
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Test\SAccounts\Transaction;

use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\Nominal;
use SAccounts\Transaction\Entry;
use SAccounts\Transaction\SplitTransaction;

class SplitTransactionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SplitTransaction
     */
    protected $sut;

    protected function setUp()
    {
        $amount = 1226;
        $this->sut = (new SplitTransaction())
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
    }

    public function testBasicConstructionSetsAnEmptyNoteOnTheTransaction()
    {
        $this->assertEquals('', $this->sut->getNote());
    }

    public function testBasicConstructionSetsDateForTodayOnTheTransaction()
    {
        $dt = new \DateTime();
        $date = $dt->format('yyyy-mm-dd');
        $txnDate = $this->sut->getDate()->format('yyyy-mm-dd');
        $this->assertEquals($date, $txnDate);
    }

    public function testYouCanSetAnOptionalNoteOnConstruction()
    {
        $note = 'foo bar';
        $amount = 1226;
        $sut = (new SplitTransaction($note))
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals($note, $sut->getNote());
    }

    public function testANullNoteWillBeRetrievedAsAnEmptyString()
    {
        $amount = 1226;
        $sut = (new SplitTransaction())
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals('', $sut->getNote());
    }

    public function testYouCanSetAnOptionalSourceOnConstruction()
    {
        $amount = 1226;
        $sut = (new SplitTransaction(null, 'PUR'))
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals('PUR', $sut->getSrc());
    }

    public function testANullSourceWillBeRetrievedAsAnEmptyString()
    {
        $amount = 1226;
        $sut = (new SplitTransaction())
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals('', $sut->getSrc());
    }

    public function testYouCanSetAnOptionalReferenceOnConstruction()
    {
        $amount = 1226;
        $sut = (new SplitTransaction(null, null, 22))
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals(22, $sut->getRef());
    }

    public function testANullReferenceWillBeRetrievedAsAZeroInteger()
    {
        $amount = 1226;
        $sut = (new SplitTransaction())
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals(0, $sut->getRef());
    }

    public function testYouCanSetAnOptionalDateOnConstruction()
    {
        $note = 'foo bar';
        $dt = new \DateTime();
        $amount = 1226;
        $sut = (new SplitTransaction($note, null, null, $dt))
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals($dt, $sut->getDate());
    }

    public function testConstructingASplitTransactionDoesNotSetItsId()
    {
        $this->assertNull($this->sut->getId());
    }

    public function testYouCanSetAndGetAnId()
    {
        $id = 1;
        $this->assertEquals($id, $this->sut->setId($id)->getId());
    }

    public function testGettingTheDebitAccountForASplitTransactionWillReturnAnArrayOfNominals()
    {
        $codes = $this->sut->getDrAc();
        $this->assertInternalType('array', $codes);
        $this->assertInstanceOf(Nominal::class, $codes[0]);
    }

    public function testGettingTheCreditAccountForASplitTransactionWillReturnAnArrayOfNominals()
    {
        $codes = $this->sut->getCrAc();
        $this->assertInternalType('array', $codes);
        $this->assertInstanceOf(Nominal::class, $codes[0]);
    }

    public function testCheckingIfASplitTransactionIsBalancedWillReturnTrueIfBalanced()
    {
        $this->assertTrue($this->sut->checkBalance());
    }

    public function testCheckingIfASplitTransactionIsBalancedWillReturnFalseIfNotBalanced()
    {
        $amount = 10;
        $this->sut->addEntry(new Entry(new Nominal('2000'), $amount, AccountType::CR()));
        $this->assertFalse($this->sut->checkBalance());
    }

    public function testYouCanGetTheTotalTransactionAmountIfTheTransactionIsBalanced()
    {
        $this->assertEquals(1226, $this->sut->getAmount());
    }

    public function testIfTheTransactionIsNotBalancedGettingTheTotalTransactionAmountWillThrowAnException()
    {
        $this->expectException(AccountsException::class);
        $this->sut->addEntry(new Entry(new Nominal('2000'), 10, AccountType::CR()))
            ->getAmount();
    }

    public function testYouCanGetTheTransactionNote()
    {
        $this->assertInternalType('string', $this->sut->getNote());
    }

    public function testYouCanGetTheTransactionDatetime()
    {
        $this->assertInstanceOf(\DateTime::class, $this->sut->getDate());
    }

    public function testASplitTransactionIsSimpleIfItHasOneDrAndOneCrEntry()
    {
        $this->assertTrue($this->sut->isSimple());
        $this->sut->addEntry(new Entry(new Nominal('2000'), 10, AccountType::CR()));
        $this->assertFalse($this->sut->isSimple());
    }

    public function testYouCanGetAnEntryByItsNominalId()
    {
        $this->sut->addEntry(new Entry(new Nominal('2000'), 10, AccountType::CR()));
        $test = $this->sut->getEntry(new Nominal('2000'));
        $this->assertInstanceOf(Entry::class, $test);
    }

    public function testGettingAnUnknownEntryWillThrowAnException()
    {
        $this->expectException(AccountsException::class);
        $this->expectExceptionMessage('Entry not found');
        $this->sut->getEntry(new Nominal('2000'));
    }
}
