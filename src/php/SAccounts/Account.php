<?php
/**
 * Simple Double Entry Accounting V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license BSD-3-Clause See LICENSE.md
 */
namespace SAccounts;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;

/**
 * An Account
 *
 */
class Account
{
    /**
     * Account Id
     *
     * @var Nominal
     */
    protected $nominal;

    /**
     * Account Type
     *
     * @var AccountType
     */
    protected $type;

    /**
     * Account Name
     *
     * @var StringType
     */
    protected $name;

    /**
     * Account debit amount
     *
     * @var IntType
     */
	protected $acDr;

    /**
     * Account credit amount
     *
     * @var IntType
     */
	protected $acCr;

    /**
     * Account constructor.
     *
     * @param Nominal           $nominal
     * @param AccountType       $type
     * @param StringType        $name
     * @param IntType           $dr
     * @param IntType           $cr
     */
    public function __construct(
        Nominal $nominal,
        AccountType $type,
        StringType $name,
        IntType $dr,
        IntType $cr
    ) {
        $this->nominal = $nominal;
        $this->type= $type;
        $this->name = $name;
        $this->acDr = $dr;
        $this->acCr = $cr;
    }

    /**
     * Return current debit amount
     *
     * @return IntType
     */
    public function dr()
    {
        return $this->acDr;
    }

    /**
     * Return current credit amount
     *
     * @return IntType
     */
    public function cr()
    {
        return $this->acCr;
    }

    /**
     * Get the account balance
     *
     * Returns the current account balance.
     *
     * @return IntType
     *
     * @throws AccountsException
     */
    public function getBalance() {
        return $this->type->balance($this->acDr, $this->acCr);
    }

    /**
     * Return account unique id (Nominal Code)
     *
     * @return Nominal
     */
    public function getNominal()
    {
        return $this->nominal;
    }

    /**
     * Return account type
     *
     * @return AccountType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return account name
     *
     * @return StringType
     */
    public function getName()
    {
        return $this->name;
    }
}
