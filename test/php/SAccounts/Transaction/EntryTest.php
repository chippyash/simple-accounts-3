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


class EntryTest extends \PHPUnit_Framework_TestCase
{
    public function testAnEntryRequiresAnIdAnAmountAndAType()
    {
        $sut = new Entry(new Nominal('9999'), 0, AccountType::CR());
        $this->assertInstanceOf(Entry::class, $sut);
    }

    public function testAnEntryMustHaveCrOrDrType()
    {
        $sut = new Entry(new Nominal('9999'), 0, AccountType::CR());
        $this->assertInstanceOf(Entry::class, $sut);
        $sut = new Entry(new Nominal('9999'), 0, AccountType::DR());
        $this->assertInstanceOf(Entry::class, $sut);
    }

    /**
     * @dataProvider invalidAccountTypes
     * @param AccountType $type
     */
    public function testConstructingAnEntryWithInvalidTypeWillThrowException($type)
    {
        $this->expectException(AccountsException::class);
        $sut = new Entry(new Nominal('9999'), 0, $type);
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
            (new Entry(new Nominal('9999'), 0, AccountType::CR()))
                ->getId()
                ->get()
        );
    }

    public function testYouCanGetTheAmountOfAnEntry()
    {
        $this->assertEquals(
            100,
            (new Entry(new Nominal('9999'), 100, AccountType::CR()))
                ->getAmount()
        );
    }

    public function testYouCanGetTheTypeOfAnEntry()
    {
        $this->assertEquals(
            AccountType::CR(),
            (new Entry(new Nominal('9999'), 1, AccountType::CR()))
                ->getType()
        );
    }
}
