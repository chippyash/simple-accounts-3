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

use Monad\Match;
use Monad\Option;
use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\Nominal;

/**
 * A Complex Journal transaction type
 */
class SplitTransaction
{
    /**
     * @var string
     */
    const ERR1 = 'Entry not found';

    /**
     * @var int
     */
    protected $txnId = null;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var string
     */
    protected $note;

    /**
     * @var string
     */
    protected $src;

    /**
     * @var int
     */
    protected $ref;

    /**
     * @var Entries
     */
    protected $entries;

    /**
     * Constructor
     *
     * @param string|null $note Defaults to '' if not set
     * @param string|null $src  user defined source of transaction
     * @param int|null $ref user defined reference for transaction
     * @param \DateTime|null $date Defaults to today if not set
     */
    public function __construct(
        ?string $note = null,
        ?string $src = null,
        ?int $ref = null,
        ?\DateTime $date = null
    ) {
        Match::on(Option::create($date))
            ->Monad_Option_Some(function ($opt) {
                $this->date = $opt->value();
            })
            ->Monad_Option_None(function () {
                $this->date = new \DateTime();
            });

        Match::on(Option::create($note))
            ->Monad_Option_Some(function ($opt) {
                $this->note = $opt->value();
            })
            ->Monad_Option_None(function () {
                $this->note = null;
            });

        Match::on(Option::create($src))
            ->Monad_Option_Some(function ($opt) {
                $this->src = $opt->value();
            })
            ->Monad_Option_None(function () {
                $this->src = null;
            });

        Match::on(Option::create($ref))
            ->Monad_Option_Some(function ($opt) {
                $this->ref = $opt->value();
            })
            ->Monad_Option_None(function () {
                $this->ref = null;
            });

        $this->entries = new Entries();
    }

    /**
     * @param int $txnId
     *
     * @return SplitTransaction
     */
    public function setId(int $txnId): SplitTransaction
    {
        $this->txnId = $txnId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->txnId ?? null;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getNote(): string
    {
        return $this->note ?? '';
    }

    /**
     * @return string
     */
    public function getSrc(): string
    {
        return $this->src ?? '';
    }

    /**
     * @return int
     */
    public function getRef(): int
    {
        return $this->ref ?? 0;
    }

    /**
     * Add a transaction entry
     *
     * @param Entry $entry
     *
     * @return SplitTransaction
     */
    public function addEntry(Entry $entry): SplitTransaction
    {
        $this->entries = $this->entries->addEntry($entry);

        return $this;
    }

    /**
     * Do the entries balance?
     *
     * @return bool
     */
    public function checkBalance(): bool
    {
        return $this->entries->checkBalance();
    }

    /**
     * @return Entries
     */
    public function getEntries(): Entries
    {
        return $this->entries;
    }

    /**
     * @param Nominal $id
     *
     * @return Entry
     *
     * @throws AccountsException
     */
    public function getEntry(Nominal $id): Entry
    {
        $entries = array_values($this->entries->filter(function(Entry $entry) use ($id) {
            return ($entry->getId()->get() === $id());
        })->toArray());

        if (count($entries) == 0) {
            throw new AccountsException(self::ERR1);
        }

        return $entries[0];
    }


    /**
     * Get amount if the account is balanced
     *
     * @return int
     *
     * @throw AccountsException
     */
    public function getAmount(): int
    {
        return Match::create(Option::create($this->entries->checkBalance(), false))
            ->Monad_Option_Some(
                function () {
                    $tot = 0;
                    foreach ($this->entries as $entry) {
                        $tot += $entry->getAmount();
                    }
                    return $tot / 2;
                })
            ->Monad_Option_None(function () {
                throw new AccountsException('No amount for unbalanced transaction');
            })
            ->value();
    }

    /**
     * Return debit account ids
     * return zero, one or more Nominals in an array
     *
     * @return array [Nominal]
     */
    public function getDrAc(): array
    {
        $acs = [];
        foreach ($this->getEntries() as $entry) {
            if (AccountType::DR()->equals($entry->getType())) {
                $acs[] = $entry->getId();
            }
        }

        return $acs;
    }

    /**
     * Return credit account ids
     * return zero, one or more Nominals in an array
     *
     * @return array [Nominal]
     */
    public function getCrAc(): array
    {
        $acs = [];
        foreach ($this->getEntries() as $entry) {
            if (AccountType::CR()->equals($entry->getType())) {
                $acs[] = $entry->getId();
            }
        }

        return $acs;
    }

    /**
     * Is this a simple transaction, i.e. 1 dr and 1 cr entry
     *
     * @return bool
     */
    public function isSimple()
    {
        return (count($this->getDrAc()) == 1
            && count($this->getCrAc()) == 1);
    }

}