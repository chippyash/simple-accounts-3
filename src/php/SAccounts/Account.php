<?php
/**
 * Simple Double Entry Accounting V2
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Monad\FTry;
use Monad\Match;
use Monad\Option;

/**
 * An Account
 *
 */
class Account
{

    /**
     * Chart that this account belongs to
     *
     * @var Chart
     */
    protected $chart;

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
     * @param Chart             $chart
     * @param Nominal           $nominal
     * @param AccountType       $type
     * @param StringType        $name
     */
    public function __construct(
        Chart $chart,
        Nominal $nominal,
        AccountType $type,
        StringType $name
    ) {
        $this->chart = $chart;
        $this->nominal = $nominal;
        $this->type= $type;
        $this->name = $name;
        $this->acDr = new IntType(0);
        $this->acCr = new IntType(0);
    }

    /**
     * Add to debit amount for this account
     * Will update parent account if required
     *
     * @param IntType $amount
     *
     * @return $this
     */
    public function debit(IntType $amount)
    {
        $this->acDr->set($this->acDr->get() + $amount());
        Match::on($this->optGetParentId())
            ->Monad_Option_Some(function($parentId) use($amount) {
                $this->chart->getAccount($parentId->value())->debit($amount);
            });

        return $this;
    }

    /**
     * Add to credit amount for this account
     * Will update parent account if required
     *
     * @param IntType $amount
     *
     * @return $this
     */
    public function credit(IntType $amount)
    {
        $this->acCr->set($this->acCr->get() + $amount());
        Match::on($this->optGetParentId())
            ->Monad_Option_Some(function($parentId) use($amount) {
                $this->chart->getAccount($parentId->value())->credit($amount);
            });

        return $this;
    }

    /**
     * Return current debit amount
     *
     * @return IntType
     */
    public function getDebit()
    {
        return $this->acDr;
    }

    /**
     * Return current credit amount
     *
     * @return IntType
     */
    public function getCredit()
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

    /**
     * Return Chart that this account belongs to
     *
     * @return Chart
     */
    public function getChart()
    {
        return $this->chart;
    }

    /**
     * Get parent nominal id as an Option
     *
     * @return Option
     */
    protected function optGetParentId()
    {
        return Match::on(
            FTry::with(
                function () {
                    return $this->chart->getParentId($this->nominal);
                }
            )
        )
            ->Monad_FTry_Success(function ($id) {
                return Option::create($id->flatten());
            })
            ->Monad_FTry_Failure(function () {
                return Option::create(null);
            })
            ->value();
    }
}
