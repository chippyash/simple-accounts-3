#!/usr/bin/env php
<?php
/**
 * Simple Double txn Bookkeeping V3
 *
 * Using the Chippyash\Currency classes in conjunction with Simple Accounts
 *
 * One thing that is probably not clear, is that a Currency is a child class of
 * an IntType, which is why this works seamlessly.
 *
 * i.e. giving the Accountant a Currency, is the same as giving it an IntType
 *
 * If you are using some other Currency handling convention, you will need to caste
 * into IntType for the accounting API to understand it.
 *
 * Also note that as far as the simple-accounts database system is concerned,
 * it doesn't care about the actual currency. It just stores integers.  Using
 * Currency in this context is as a helper. 
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license   GPL V3+ See LICENSE.md
 */

require_once '../vendor/autoload.php';

use Chippyash\Currency\Currency;
use Chippyash\Currency\Factory as Crcy;
use Chippyash\Type\String\StringType;
use SAccounts\Accountant;
use SAccounts\AccountType;
use SAccounts\ChartDefinition;
use SAccounts\Nominal;
use SAccounts\Transaction\SimpleTransaction;
use Zend\Db\Adapter\Adapter;

//currency code to use, try USD,EUR,BHD etc
//You can find valid codes in vendor/chippyash/currency/data/iso4217.xml
$crcyCd = 'GBP';

//set up database connection
$adapter = new Adapter(
    [
        'driver' => 'Pdo_mysql',
        'database' => 'test',
        'username' => 'test',
        'password' => 'test'
    ]
);

//clear down the test data
$adapter->query('delete from sa_coa', Adapter::QUERY_MODE_EXECUTE);

$accountant = new Accountant($adapter);

//create a new chart of accounts
$definition = new ChartDefinition(new StringType(dirname(__DIR__) . '/src/xml/personal.xml'));
$chartId = $accountant->createChart(new StringType('Personal'), $definition);

//we'll be working with these accounts
$bankAc = new Nominal('1210');
$savingsAc = new Nominal('1220');
$salaryAc = new Nominal('4100');
$foodAc = new Nominal('6400');

//let's pay our salary into the bank
/** @var Currency $salary */
$salary = Crcy::create($crcyCd, 4203.45);
$accountant->writeTransaction(
    new SimpleTransaction($bankAc, $salaryAc, $salary, new StringType('Jan salary')),
    new DateTime('2018-01-29')
);
echo "Pay salary of {$salary->display()} into Bank\n";

//and spend some on food
/** @var Currency $food */
$food = Crcy::create($crcyCd, 120.16);
$accountant->writeTransaction(
    new SimpleTransaction($foodAc, $bankAc, $food, new StringType('weekly food shop')),
    new DateTime('2018-01-29')
);
echo "Spend {$food->display()} on food\n";

//and save some money for a rainy day
/** @var Currency $savings */
$savings = Crcy::create($crcyCd, 500);
$accountant->writeTransaction(
    new SimpleTransaction($savingsAc, $bankAc, $savings, new StringType('rainy day')),
    new DateTime('2018-01-29')
);
echo "Save {$savings->display()} for a rainy day\n\n";

echo "Journal entries for bank account\n\n";
//get the bank's account type so we can later get the titles for dr and cr entries
$bankAcType = $accountant->fetchChart()->getAccount($bankAc)->getType();
//fetch the transactions
$txns = $accountant->fetchAccountJournals($bankAc);
/** @var \SAccounts\Transaction\SplitTransaction $txn */
foreach ($txns as $txn) {
    echo "#:    {$txn->getId()}\n";
    echo "Date: {$txn->getDate()->format('Y-M-D h:m:s')}\n";
    echo "Note: {$txn->getNote()}\n";
    echo "Src:  {$txn->getSrc()}\n";
    echo "Ref:  {$txn->getRef()}\n";

    $entries = $txn->getEntries();
    /** @var \SAccounts\Transaction\Entry $entry */
    echo str_pad($bankAcType->drTitle(), 12);
    echo $bankAcType->crTitle();
    echo "\n";
    foreach ($entries as $entry) {
        $type = $entry->getType()->equals(AccountType::DR()) ? 'dr' : 'cr';
        $displayAmount = Crcy::create($crcyCd)->set($entry->getAmount()->get())->display()->get();
        $dr = $type == 'dr' ? $displayAmount : '';
        $cr = $type == 'cr' ? $displayAmount : '';
        echo str_pad($dr, 12, ' ',STR_PAD_LEFT);
        echo str_pad($cr, 12, ' ', STR_PAD_LEFT);
        echo "\n";
    }
}