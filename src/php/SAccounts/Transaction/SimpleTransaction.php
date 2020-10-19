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

use SAccounts\AccountType;
use SAccounts\Nominal;

/**
 * Simple two entry balanced transaction
 *
 * Only really useful for adding new transactions as any transactions
 * returned from a Journal will be in SplitTransaction form
 */
class SimpleTransaction extends SplitTransaction
{
    /**
     * Constructor
     *
     * @param Nominal $drAc Account to debit
     * @param Nominal $crAc Account to credit
     * @param int $amount Transaction amount
     * @param string $note Defaults to '' if not set
     * @param int $ref Defaults to 0 if not set
     * @param \DateTime $date Defaults to today if not set
     */
    public function __construct(
        Nominal $drAc,
        Nominal $crAc,
        int $amount,
        ?string $note = null,
        ?string $src = null,
        ?int $ref = null,
        ?\DateTime $date = null
    ) {
        parent::__construct($note, $src, $ref, $date);
        $this->addEntry(new Entry($drAc, $amount, AccountType::DR()));
        $this->addEntry(new Entry($crAc, $amount, AccountType::CR()));
    }
}
