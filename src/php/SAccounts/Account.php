<?php

declare(strict_types=1);

/**
 * Simple Double Entry Accounting V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license BSD-3-Clause See LICENSE.md
 */
namespace SAccounts;

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
     * @var string
     */
    protected $name;

    /**
     * Account debit amount
     *
     * @var int
     */
    protected $acDr;

    /**
     * Account credit amount
     *
     * @var int
     */
    protected $acCr;

    /**
     * Account constructor.
     *
     * @param Nominal           $nominal
     * @param AccountType       $type
     * @param string        $name
     * @param int           $dr
     * @param int           $cr
     */
    public function __construct(
        Nominal $nominal,
        AccountType $type,
        string $name,
        int $dr,
        int $cr
    ) {
        $this->nominal = $nominal;
        $this->type = $type;
        $this->name = $name;
        $this->acDr = $dr;
        $this->acCr = $cr;
    }

    /**
     * Return current debit amount
     *
     * @return int
     */
    public function dr()
    {
        return $this->acDr;
    }

    /**
     * Return current credit amount
     *
     * @return int
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
     * @return int
     *
     * @throws AccountsException
     */
    public function getBalance()
    {
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
