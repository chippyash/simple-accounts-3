!#/usr/bin/env php
<?php
/**
 * Simple Double Entry Bookkeeping V3
 *
 * Using the Chippyash\Currency classes in conjunction with Simple Accounts
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license   GPL V3+ See LICENSE.md
 */

require_once '../vendor/autoload.php';

use Chippyash\Currency\Currency;
use Chippyash\Currency\Factory as Crcy;
use Chippyash\Type\String\StringType;
use SAccounts\Account;
use SAccounts\Accountant;
use SAccounts\AccountType;
use SAccounts\ChartDefinition;
use SAccounts\Nominal;
use SAccounts\Transaction\SimpleTransaction;
use Tree\Node\NodeInterface;
use Tree\Visitor\Visitor;
use Zend\Db\Adapter\Adapter;

/**
 * Utility class to print chart values
 */
class chartPrinter implements Visitor
{
    /**
     * @param NodeInterface $node
     *
     * @return mixed
     */
    public function visit(NodeInterface $node)
    {
        /** @var Account $ac */
        $ac = $node->getValue();
        $nominal = str_pad($ac->getNominal(), 8);
        $name = str_pad($ac->getName(), 20);
        $dr = str_pad(Crcy::create('GBP')->set($ac->dr()->get())->display(), 15, ' ', STR_PAD_LEFT);
        $cr = str_pad(Crcy::create('GBP')->set($ac->cr()->get())->display(), 15, ' ', STR_PAD_LEFT);
        $balStr = ($ac->getType()->equals(AccountType::REAL()) ? Crcy::create('GBP', 0)->display() : Crcy::create('GBP')->set($ac->getBalance()->get())->display());
        $balance = str_pad($balStr, 15, ' ', STR_PAD_LEFT);
        echo "{$nominal}{$name}{$dr}{$cr}{$balance}\n";

        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }
}

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
$salary = Crcy::create('GBP')->setAsFloat(4203.45);
$accountant->writeTransaction(
    new SimpleTransaction($bankAc, $salaryAc, $salary, new StringType('Jan salary')),
    new DateTime('2018-01-29')
);

//and spend some on food
/** @var Currency $food */
$food = Crcy::create('GBP')->setAsFloat(120.16);
$accountant->writeTransaction(
    new SimpleTransaction($foodAc, $bankAc, $food, new StringType('weekly food shop')),
    new DateTime('2018-01-29')
);

//and save some money for a rainy day
/** @var Currency $savings */
$savings = Crcy::create('GBP')->setAsFloat(500);
$accountant->writeTransaction(
    new SimpleTransaction($savingsAc, $bankAc, $savings, new StringType('rainy day')),
    new DateTime('2018-01-29')
);

echo "Nominal Name                     DR            CR            Balance\n";
$accountant->fetchChart()->getTree()->accept(new \ChartPrinter());

echo "\nGo look at the database journal tables for their entries\n";
