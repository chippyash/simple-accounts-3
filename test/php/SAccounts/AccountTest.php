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
use SAccounts\Nominal;

class AccountTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Account
     */
    protected $sut;

    /**
     * @dataProvider validAccountTypes
     */
    public function testYouCanCreateAnyValidAccountType($acType)
    {
        $this->sut = new Account(
            new Nominal('9999'),
            $acType,
            'foo',
            0,
            0
        );
        $this->assertInstanceOf(Account::class, $this->sut);
    }

    public function validAccountTypes()
    {
        return [
            [AccountType::DUMMY()],
            [AccountType::DR()],
            [AccountType::CR()],
            [AccountType::ASSET()],
            [AccountType::LIABILITY()],
            [AccountType::BANK()],
            [AccountType::CUSTOMER()],
            [AccountType::EQUITY()],
            [AccountType::EXPENSE()],
            [AccountType::INCOME()],
            [AccountType::REAL()],
            [AccountType::SUPPLIER()],
        ];
    }

    /**
     * @dataProvider accountTypesThatHaveBalance
     */
    public function testYouCanGetABalanceForAccountTypesThatSupportIt(AccountType $acType, $dr, $cr)
    {
        $this->sut = new Account(
            new Nominal('9999'),
            $acType,
            'foo',
            $dr,
            $cr
        );
        $this->assertInternalType('int', $this->sut->getBalance());
        $this->assertEquals(12, $this->sut->getBalance(), "wrong balance for: " . $acType->getKey());
    }

    public function accountTypesThatHaveBalance()
    {
        return [
            [AccountType::DR(), 12, 0],
            [AccountType::CR(), 0, 12],
            [AccountType::ASSET(), 12, 0],
            [AccountType::LIABILITY(), 0, 12],
            [AccountType::BANK(), 12, 0],
            [AccountType::CUSTOMER(), 12, 0],
            [AccountType::EQUITY(), 0, 12],
            [AccountType::EXPENSE(), 12, 0],
            [AccountType::INCOME(), 0, 12],
            [AccountType::SUPPLIER(), 0, 12],
        ];
    }

    public function testGettingBalanceOfADummyAccountTypeWillThrowAnException()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            'foo',
            0,
            0
        );
        $this->expectException(AccountsException::class);
        $this->sut->getBalance();
    }

    public function testYouCanGetTheAccountNominalCode()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            'foo',
            0,
            0
        );
        $this->assertEquals(new Nominal('9999'), $this->sut->getNominal());
    }

    public function testYouCanGetTheAccountType()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            'foo',
            0,
            0
        );
        $this->assertTrue(AccountType::DUMMY()->equals($this->sut->getType()));
    }

    public function testYouCanGetTheAccountName()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            'foo',
            0,
            0
        );
        $this->assertEquals('foo', $this->sut->getName());
    }

    public function testYouCanGetTheDebitAndCreditAmounts()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            'foo',
            12,
            20
        );

        $this->assertEquals(12, $this->sut->dr());
        $this->assertEquals(20, $this->sut->cr());
    }
}