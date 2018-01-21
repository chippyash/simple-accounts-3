<?php
/**
 * Simple Double Entry Bookkeeping V2
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Test\SAccounts\Transaction;

use SAccounts\AccountType;
use SAccounts\Nominal;
use SAccounts\Transaction\Entry;
use Chippyash\Type\Number\IntType;

class EntryTest extends \PHPUnit_Framework_TestCase
{
    public function testAnEntryRequiresAnIdAnAmountAndAType()
    {
        $sut = new Entry(new Nominal('9999'), new IntType(0), AccountType::CR());
        $this->assertInstanceOf('SAccounts\Transaction\Entry', $sut);
    }

    public function testAnEntryMustHaveCrOrDrType()
    {
        $sut = new Entry(new Nominal('9999'), new IntType(0), AccountType::CR());
        $this->assertInstanceOf('SAccounts\Transaction\Entry', $sut);
        $sut = new Entry(new Nominal('9999'), new IntType(0), AccountType::DR());
        $this->assertInstanceOf('SAccounts\Transaction\Entry', $sut);
    }

    /**
     * @dataProvider invalidAccountTypes
     * @expectedException \SAccounts\AccountsException
     * @param AccountType $type
     */
    public function testConstructingAnEntryWithInvalidTypeWillThrowException($type)
    {
        $sut = new Entry(new Nominal('9999'), new IntType(0), $type);
    }

    public function invalidAccountTypes()
    {
        return array(
            array(AccountType::ASSET()),
            array(AccountType::BANK()),
            array(AccountType::CUSTOMER()),
            array(AccountType::EQUITY()),
            array(AccountType::EXPENSE()),
            array(AccountType::INCOME()),
            array(AccountType::LIABILITY()),
            array(AccountType::REAL()),
            array(AccountType::SUPPLIER()),
        );
    }

    public function testYouCanGetTheIdOfAnEntry()
    {
        $this->assertEquals(
            '9999',
            (new Entry(new Nominal('9999'), new IntType(0), AccountType::CR()))
                ->getId()
                ->get()
        );
    }

    public function testYouCanGetTheAmountOfAnEntry()
    {
        $this->assertEquals(
            100,
            (new Entry(new Nominal('9999'), new IntType(100), AccountType::CR()))
                ->getAmount()
                ->get()
        );
    }

    public function testYouCanGetTheTypeOfAnEntry()
    {
        $this->assertEquals(
            AccountType::CR(),
            (new Entry(new Nominal('9999'), new IntType(1), AccountType::CR()))
                ->getType()
        );
    }
}
