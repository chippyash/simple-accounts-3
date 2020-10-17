<?php
declare(strict_types=1);
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Transaction;

use Chippyash\Currency\Factory;


use SAccounts\Nominal;
use SAccounts\Transaction\SimpleTransaction;

class SimpleTransactionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SimpleTransaction
     */
    protected $sut;

    protected function setUp()
    {
        $this->sut = new SimpleTransaction(new Nominal('0000'), new Nominal('1000'), 1226);
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
        $sut = new SimpleTransaction(new Nominal('0000'), new Nominal('1000'), 1226, $note);
        $this->assertEquals($note, $sut->getNote());
    }

    public function testYouCanSetAnOptionalSourceOnConstruction()
    {
        $sut = new SimpleTransaction(
            new Nominal('0000'),
            new Nominal('1000'),
            1226,
            null,
            'PUR'
        );
        $this->assertEquals('PUR', $sut->getSrc());
    }

    public function testYouCanSetAnOptionalReferenceOnConstruction()
    {
        $sut = new SimpleTransaction(
            new Nominal('0000'),
            new Nominal('1000'),
            1226,
            null,
            null,
            22
        );
        $this->assertEquals(22, $sut->getRef());
    }

    public function testYouCanSetAnOptionalDateOnConstruction()
    {
        $note = 'foo bar';
        $dt = new \DateTime();
        $sut = new SimpleTransaction(
            new Nominal('0000'),
            new Nominal('1000'),
            1226,
            $note,
            null,
            null,
            $dt);
        $this->assertEquals($dt, $sut->getDate());
    }

    public function testConstructingATransactionDoesNotSetItsId()
    {
        $this->assertNull($this->sut->getId());
    }

    public function testYouCanSetAndGetAnId()
    {
        $id = 1;
        $this->assertEquals($id, $this->sut->setId($id)->getId());
    }

    public function testYouCanGetTheDebitAccountCode()
    {
        $this->assertEquals('0000', $this->sut->getDrAc()[0]->get());
    }

    public function testYouCanGetTheCreditAccountCode()
    {
        $this->assertEquals('1000', $this->sut->getCrAc()[0]->get());
    }

    public function testYouCanGetTheTransactionAmount()
    {
        $this->assertEquals(1226, $this->sut->getAmount());
    }

    public function testYouCanGetTheTransactionNote()
    {
        $this->assertInternalType('string', $this->sut->getNote());
    }

    public function testYouCanGetTheTransactionDatetime()
    {
        $this->assertInstanceOf(\DateTime::class, $this->sut->getDate());
    }
}
