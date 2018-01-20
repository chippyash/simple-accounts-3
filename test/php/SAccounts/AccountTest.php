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
use SAccounts\Nominal;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;

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
            new StringType('foo'),
            new IntType(0),
            new IntType(0)
        );
        $this->assertInstanceOf('SAccounts\Account', $this->sut);
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
            new StringType('foo'),
            new IntType($dr),
            new IntType($cr)
        );
        $this->assertInstanceOf('Chippyash\Type\Number\IntType', $this->sut->getBalance());
        $this->assertEquals(12, $this->sut->getBalance()->get(), "wrong balance for: " . $acType->getKey());
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

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testGettingBalanceOfARealAccountTypeWillThrowAnException()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::REAL(),
            new StringType('foo'),
            new IntType(0),
            new IntType(0)
        );
        $this->sut->getBalance();
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testGettingBalanceOfADummyAccountTypeWillThrowAnException()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo'),
            new IntType(0),
            new IntType(0)
        );
        $this->sut->getBalance();
    }

    public function testYouCanGetTheAccountNominalCode()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo'),
            new IntType(0),
            new IntType(0)
        );
        $this->assertEquals(new Nominal('9999'), $this->sut->getNominal());
    }

    public function testYouCanGetTheAccountType()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo'),
            new IntType(0),
            new IntType(0)
        );
        $this->assertTrue(AccountType::DUMMY()->equals($this->sut->getType()));
    }

    public function testYouCanGetTheAccountName()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo'),
            new IntType(0),
            new IntType(0)
        );
        $this->assertEquals(new StringType('foo'), $this->sut->getName());
    }

    public function testYouCanGetTheDebitAndCreditAmounts()
    {
        $this->sut = new Account(
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo'),
            new IntType(12),
            new IntType(20)
        );

        $this->assertEquals(12, $this->sut->dr()->get());
        $this->assertEquals(20, $this->sut->cr()->get());
    }
}