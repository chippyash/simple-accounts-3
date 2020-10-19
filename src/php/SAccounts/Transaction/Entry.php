<?php

declare(strict_types=1);

/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license BSD-3-Clause See LICENSE.md
 */
namespace SAccounts\Transaction;

use Monad\FTry;
use Monad\Match;
use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\Nominal;

/**
 * Records a transaction value entry for a ledger
 */
class Entry
{
    /**
     * Exception error message
     */
    public const ERR_NOTYPE = 'Account type must be DR or CR';

    /**
     * @var Nominal
     */
    protected $entryId;

    /**
     * @var int
     */
    protected $amount;

    /**
     * @var AccountType
     */
    protected $type;

    /**
     * @param Nominal $entryId
     * @param int $amount
     * @param AccountType $type
     *
     * @throws AccountsException
     */
    public function __construct(Nominal $entryId, int $amount, AccountType $type)
    {
        $this->entryId = $entryId;
        $this->amount = $amount;
        $this->type = $this->checkType($type)
            ->pass()
            ->value();
    }

    /**
     * @return Nominal
     */
    public function getId(): Nominal
    {
        return $this->entryId;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return AccountType
     */
    public function getType(): AccountType
    {
        return $this->type;
    }

    /**
     * @param AccountType $type
     * @return FTry
     */
    protected function checkType(AccountType $type): FTry
    {
        return Match::on($type->getValue())
            ->test(AccountType::CR, function () {
                return FTry::with(AccountType::CR());
            })
            ->test(AccountType::DR, function () {
                return FTry::with(AccountType::DR());
            })
            ->any(function () {
                return FTry::with(new AccountsException(self::ERR_NOTYPE));
            })
            ->value();
    }
}
