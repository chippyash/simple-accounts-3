#!/usr/bin/env php
<?php
/**
 * Simple Double Entry Bookkeeping V3
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
 * Currency in this context is as a helper. In the Visitor, for instance, try setting
 * the currency code to something other than GBP for output.
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
use SAccounts\ChartDefinition;
use SAccounts\Visitor\ChartPrinter;
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
    new SimpleTransaction($bankAc, $salaryAc, $salary->getValue(), new StringType('Jan salary')),
    new DateTime('2018-01-29')
);
echo "Pay salary of {$salary->display()} into Bank\n";

//and spend some on food
/** @var Currency $food */
$food = Crcy::create($crcyCd, 120.16);
$accountant->writeTransaction(
    new SimpleTransaction($foodAc, $bankAc, $food->getValue(), new StringType('weekly food shop')),
    new DateTime('2018-01-29')
);
echo "Spend {$food->display()} on food\n";

//and save some money for a rainy day
/** @var Currency $savings */
$savings = Crcy::create($crcyCd, 500);
$accountant->writeTransaction(
    new SimpleTransaction($savingsAc, $bankAc, $savings->getValue(), new StringType('rainy day')),
    new DateTime('2018-01-29')
);
echo "Save {$savings->display()} for a rainy day\n\n";

//Print out the chart using the console ChartPrinter
$accountant->fetchChart()->getTree()->accept(new ChartPrinter(Crcy::create($crcyCd)));

echo "\nGo look at the database journal tables for their entries\n";
