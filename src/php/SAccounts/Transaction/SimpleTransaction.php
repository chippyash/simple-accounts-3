<?php
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Transaction;

use Chippyash\Type\Number\IntType;
use SAccounts\AccountType;
use SAccounts\Nominal;
use Chippyash\Type\String\StringType;

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
     * @param IntType $amount Transaction amount
     * @param StringType $note Defaults to '' if not set
     * @param IntType $ref Defaults to 0 if not set
     * @param \DateTime $date Defaults to today if not set
     */
    public function __construct(
        Nominal $drAc,
        Nominal $crAc,
        IntType $amount,
        StringType $note = null,
        StringType $src = null,
        IntType $ref = null,
        \DateTime $date = null
    ) {
        parent::__construct($note, $src, $ref, $date);
        $this->addEntry(new Entry($drAc, $amount, AccountType::DR()));
        $this->addEntry(new Entry($crAc, $amount, AccountType::CR()));
    }
}