# chippyash/simple-accounts-3

## Quality Assurance


![PHP 7.2](https://img.shields.io/badge/PHP-7.2-blue.svg)
![PHP 7.3](https://img.shields.io/badge/PHP-7.3-blue.svg)
![PHP 7.4](https://img.shields.io/badge/PHP-7.4-blue.svg)
![MariaDb 10.0](https://img.shields.io/badge/MariaDb-10.0-blue.svg)
![MySql 5.6](https://img.shields.io/badge/MySql-5.6-blue.svg)
[![Build Status](https://travis-ci.org/chippyash/simple-accounts-3.svg?branch=master)](https://travis-ci.org/chippyash/simple-accounts-3)
[![Test Coverage](https://api.codeclimate.com/v1/badges/5811a42ebda892357fba/test_coverage)](https://codeclimate.com/github/chippyash/simple-accounts-3/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/5811a42ebda892357fba/maintainability)](https://codeclimate.com/github/chippyash/simple-accounts-3/maintainability)

- Please note that developer support for PHP<7.2 was withdrawn at V2 of this library
- 
## What

Provides a simple database backed general ledger accounting library that allows for a 
quick implementation of double entry book keeping into an application.

This library replaces [chippyash/simple-accounts](https://github.com/chippyash/simple-accounts)

This library does not provide sales ledgers, purchase ledgers, or other operational
ledgers.  Your application probably has these in one form or another.  This provides
the 'central' accounting functionality.

## Why

Whilst full blown accounting systems are available, requiring a massive integration 
effort, some applications simply need to be able keep some form of internal account. 
This library is the direct descendant of something I wrote for a client many years 
ago to keep account of game points earned on a web site. Using the double entry 
accounting paradigm allowed the site owner to keep track of who had gathered points, 
and in which ways, whilst at the same time seeing what this meant to their business 
as game points translated into real world value for the customer by way of discounts 
and prizes.

## Requirements

Support in this application is given for 2 flavours of SQL, MariaDb and MySql.

### MariaDb

The MariaDb utilises a high performance plugin that greatly simplifies dealing with
hierarchical data.

You will need MariaDB >=10 with the [OQGraph plugin](https://mariadb.com/kb/en/library/oqgraph-storage-engine/)
installed. Take a look at the `.travis.yml` build script for how we do this on the
Travis build servers.  One 'gotcha' that we know about is the setting of the user
creating the triggers. Depending on your MariaDb setup, you may need to give the database
creation script user 'SUPER' privileges.  There seems no rhyme nor reason as to why this
is, but be aware.  For MariaDb 10 on the travis servers, it needs setting. See 
`scripts/test-user.sql` 

If for some reason you cannot use the OQGraph plugin, please follow the instructions 
for MySql.  It will work in MariaDb as well.

### MySql

Recognising that not everyone is able to utilise the MariaDb advantage, I have provided
an alternate database implementation for plain MySql that utilises Nested Sets.  See
[Mike Hillyer's blog](http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/)
for the basis of its implementation.

Please note that this is not as performant as the MariaDb solution.  Take a look at the 
stored procedure definitions for each DB to see the reason why.  If you can, use MariaDb.
If you can get the MySql code more performant, submit a pull request!
 
Postgres users should be able to adapt the MySql code to implement the database. I rarely
use it, so no direct support is given in this library. If you want to contribute the
Postgres SQL statements, please feel free to provide a pull request.  It would be
welcome.
 
No Windows variant support is provided at present.  If you want it, please feel free 
to make a pull request.  The library is developed under Linux.

## How

### Getting it up and running

Create a database, let's say 'test'.

Create a database user, 'test' with password 'test'. (You can run `scripts\test-user.sql`
to do this.)

Give that user all rights to the test database. (see note above re SUPER privs)

Now run the create script:

For MariaDb:

`./createmariaddb.sh test test test` 

For MySql:

`./createmysqldb.sh test test test`

to create the database components. NB - PHP Doctrine Migration users should read the
PHP code basic section to utilise the supplied migrations.

You can run SQL tests by executing `./sqltest.sh test test test localhost`.

- copy the `test/php/phpunit.xml` file to `test/php/local-phpunit.xml` and
edit it to ensure that you have the correct database connection parameters

You can run PHP tests by executing `./build.sh`. This also generates the test contract
in the ./docs directory if you have the [TestDox-Converter](https://github.com/chippyash/Testdox-Converter)
installed. If you don't then it may fail.  Inspect the script contents to run the raw
PHPUnit command.

You can run the `examples/currency-example.php` program to see
how you can convert between floating and integer types.  
<pre>
cd examples
chmod u+x currency-example.php
./currency-example.php

</pre>
The library and supporting database only handle integers, so if you need float support,
use [Chippyash\Currency](https://github.com/chippyash/currency) or provide your own handlers.

### Coding Basics- Terminology

To keep things concise and avoid confusion, particularly if you are an Accountant and
used to different terminology, here is a definition of terms used in this readme.

- SA: Simple Accounts, this library
- COA: Chart of Account.  This is an entire chart.  It has ledgers.  A COA is hierarchical
in nature, with a `root` ledger, normally called 'COA'.  It generally has 2 main child
ledgers, the balance sheet (BS) and the profit and loss account (P&L).  Under these, various
other ledgers will exist.  You can find numerous references to COA construction on
t'internet. 
- Ledger or Account: Used interchangeably. A line in the COA that holds the balance of 
all Journal transactions that have been made on the Ledger. In SA, the guiding principle 
is that if you update a child ledger, then it will update its parents all the way to
the root ledger, thus keeping the the entire COA in balance.
- Nominal or Nominal code: In General Ledger (GL), ledgers are often refered to by their
`nominal code` or `nominal`.  This is accountant or book keeper short code for a ledger.
Each Ledger in this system has a Nominal Code.  From a database point of view, it provides
part of the ledger unique key along with the chart id. By convention, it is a Digit String
and is usually from 4 digits upwards.  Nominals are used to group related Ledgers
together.  By default this library supports up to a 10 digit Nominal Code.  The example
programs only use 4 digits which is more than sufficient for everyday usage.  See the
chart of account xml files for examples.   
- Journal or Journal Entry or Transaction: A record of a change in the balance on a
Ledger.  It comprises two parts, the details of the reason for the entry, and the details
of the changes to each ledger that it effects. A Journal must be balanced.  That is
its debit and credit amounts must be equal.  The system will bork if they are not. This
is a principle defence for double entry book keeping.  
   
### Coding Basics (SQL)
As mentioned elsewhere, the fundamentals of this library lay in the SQL code, which
runs on MariaDb with the OQGraph plugin installed or MySql.

The SQL API is provided via stored procedures.  If you want to provide variants, please
respect the API.  

For MariaDb see:
 - the procedure definitions in the [src/sql/mariadb/build-procs](https://github.com/chippyash/simple-accounts-3/blob/master/src/sql/mariadb/build-procs.sql)
 - the trigger that maintains the account balances in the [src/sql/mariadb/build-triggers](https://github.com/chippyash/simple-accounts-3/blob/master/src/sql/mariadb/build-triggers.sql)
   script.
 
Otherwise the SQL is pretty straight forward.  Study the OQGraph
docs for an understanding of how it's being used. Magic underneath, but simple to use - my kind of code ;-) 

For MySql see:
 - the procedure definitions in the [src/sql/mysql/build-procs](https://github.com/chippyash/simple-accounts-3/blob/master/src/sql/mysql/build-procs.sql)
 - the trigger that maintains the account balances in the [src/sql/mysql/build-triggers](https://github.com/chippyash/simple-accounts-3/blob/master/src/sql/mysql/build-triggers.sql)
   script.

One slightly baffling procedure is `sa_fu_add_txn`. In particular the parameters, 
- arNominals TEXT,
- arAmounts TEXT,
- arTxnType TEXT

These require a matching set of comma delimited values, which is the only way of getting an array into SQL.:
- arNominals: the list of nominals to effect
- arAmounts: the list of amounts to use
- arTxnType: lst of dr or cr for each nominal

The php code deals with this by imploding values into a string before calling the SP:

See Accountant::writeTransaction() method.
See test/sql/add_transaction_test.sql circa L17 for how the SQL proc is called natively.

If you are a better SQL Head than me (not hard!), then I'd appreciate any suggestions
for operational efficiency.

### Coding Basics (PHP)

Whilst what follows will give you an introduction to what you can do with the library,
you should always look to the tests to gain further insight.

#### Changes from previous library versions
##### Organisations
Unlike the previous version of this library, we don't support the concepts of
organisations.  Organisations are outside of the remit of this
library as your implementation of them will differ according to your needs. Instead
you should plan on creating some form of one to many join between your organisations
and any chart of accounts (COA) that they use.  The `sa_coa` table can hold an 
infinite number of COAs, so it shouldn't be too much of a problem.

#### Doctrine Migrations

If you are using Doctrine Migrations and Mariadb, you can take advantage of the supplied migration 
files in `src\php\SAccounts\Doctrine`.

For development of this library you can migrate up the required DB structure into the 
test database by navigating to the root of this library and running

`vendor/bin/doctrine-migrations migrations:migrate --configuration doctrine-migrations.xml --db-configuration doctrine-db.php`

To migrate down use:

`vendor/bin/doctrine-migrations migrations:migrate prev --configuration doctrine-migrations.xml --db-configuration doctrine-db.php`

For production use, either copy the migration files into your own migrations directory, 
(files are in src/php/SAccounts/Doctrine), or possible more conveniently by creating 
your own migration classes in your existing structure and the extending them from the 
supplied migrations. That will keep them in your sequence.

Be aware that new features may result in additional migrations so if you update this 
library to a new feature version, check for new ones.
 
##### Control accounts
Like Organisations, we don't support the concept of control accounts in this library.
They are again an implementation detail between your application and this library,
more usually a configuration issue.  So add config linking where you need such functionality.
Another problem was the use of the term.  Too many accountants objected to it being
used in its previous incarnation, that it was safer to leave it out.
 
#### The Accountant
The Accountant is responsible for the majority of operations that you can carry out
in Simple Accounts and needs to be created before anything else can happen.  The 
Accountant requires a Zend Db Adapter as a construction parameter.

<pre>
use SAccounts\Accountant;
use Zend\Db\Adapter\Adapter;

$accountant = new Accountant(
	new Adapter(
		[
			'driver' => 'Pdo_mysql',
			'database' => 'test',
			'username' => 'test',
			'password' => 'test'		
		]
	)
);
</pre>

#### Creating a new chart of accounts

You create a new chart of accounts (COA) by supplying a ChartDefinition.  The
ChartDefinition is supplied with an XML definition file. An example of a definition
can be found in `src\xml\personal.xml` along with the XSD that is used to validate
any definitions in `src\xsd\chart-definition.xsd`.

<pre>
use SAccounts\ChartDefinition;

$definition = new ChartDefinition('src/xml/personal.xml');

$chartId = $accountant->createChart('Personal', $definition);
</pre>

This will create the entries in the `sa_coa` table and return you the id of the
new chart.  You will probably want to store this in your own tables so you can
retrieve it later.

The Accountant is now tied to that COA.  To use another COA you will
need to create another Accountant.  To create the Accountant and tell it to use an
existing COA, simply give the chart id  as the second parameter
when constructing the Accountant.

Please note that you never have to explicitly save the COA.  It is done transactionally
by the Accountant when you carry out operations with it.

#### Accountant operations

Most operations on the COA are carried out via the Accountant.

Operations on the COA invariably require you to give the Nominal code for the Account
which is to say, the Account identifier. Whilst in the database primary integer
ids are used, externally we operate using the Nominal code.

##### Adding an Account ledger to the COA
<pre>
use SAccounts\Nominal;
use SAccounts\Account;

$nominal = new Nominal('7700');
$prntNominal = new Nominal(('7000'));

$accountant->addAccount(
	$nominal,  		//nominal code
	AccountType::EXPENSE(), //account type
	new StringType('foo'),	//account name
	$prntNominal		//parent account nominal code (or null)
	);
</pre>

The parent Nominal must exist already with one exception. In a brand new COA you can
add the root Account and leave out the parent Nominal parameter.  For a root Account
the AccountType must be AccountType::REAL(). Trying to add a second
root Account will throw an exception.

The AccountType is important and must be appropriate for the Account you are adding.
It controls how the balance on the Account is derived.  It also allows you to display
appropriate labels for the debit and credit values on an account. Take a look at the 
`src\xml\personal.xml` file for an example of how AccountTypes are used.

Under typical circumstances, the root account is a REAL account, and has two children,
the Balance Sheet (type DR) and the Profit & Loss (type CR).  All other accounts are
children of these two.  There is however nothing stopping you configuring your chart
as you wish.

All DR (debit) type accounts derive their balance as dr amount - cr amount.  All CR (credit)
type accounts derive their baslance as cr amount - dr amount. REAL accounts derive their
balance as abs(cr amount - dr amount), which is the same as abs(dr amount - cr amount)
and should usually equal zero.

##### Deleting an Account ledger from the COA

You can delete an Account ledger only if its balance is zero. Attempting to delete
a non zero ledger will throw an exception.  NB. Deleting a ledger will delete all of 
its child ledgers as well.

By zero, it is meant that both the debit and credit account values == zero.  You cannot
therefore delete account ledgers for which any transactions have been made for it or
any child accounts.  This is a simple security measure to ensure that data is not lost.

<pre>
$accountant->delAccount(new Nominal('7000'));
</pre>

#### Fetching the COA

Having created the COA or instantiated the Accountant with the chart id, you can fetch
the COA simply with:

<pre>
use SAccounts\Chart;

/* @var Chart $chart */
$chart = $accountant->fetchChart(); 
</pre>

#### Operations on the COA

##### Basic COA operations

<pre>
//get an Account from the Chart
/* @var Account $acount */
$account = $chart->getAccount(new Nominal('2000'));

//get the parent account of an Account
$account = $chart->getAccount($chart->getParentId(new Nominal('2000')))
//or
$subAccount = $chart->getAccount(new Nominal('2000'));
$prntAccount = $chart->getAccount($chart->getParentId($account->getNominal()));

//testing if an account exists in the COA
//returns true or false
$exists = $chart->hasAccount(new Nominal('3000'));

//get the name of the COA
/* @var StringType $name */
$name = $chart->getName();

//get a ledger's values
$account = $chart->getAccount(new Nominal('2000'));
/* @var IntType $dr */
$dr = $account->dr();
/* @var IntType $cr */
$cr = $account->cr();

//NB getting the balance of the root COA account should return zero.  If not
//then your accounts are out of balance and need investigating. Perhaps something
//outside of SA made an update, or a database glitch occurred.
/* @var IntType $balance */
$balance = $account->getBalance();

/* @var Nominal $nom */
$nom = $account->getNominal();
/* @var StringType $name */
$name = $account->getName();
/* @var AccountType $type */
$type = $account->getType();

</pre>

##### The COA as a Tree

Under the covers, the chart is kept as a [nicmart/Tree](https://github.com/nicmart/Tree)
which I do recommend to you if you need to carry out Tree operations. Gaining access
to it is useful for a variety of tasks, such as displaying a trial balance. For this
we use tree Visitors. You can see a full working example of this in the 
`examples\currency-example.php` script.  The line of interest is:

<pre>
$accountant->fetchChart()->getTree()->accept(new ChartPrinter(Crcy::create($crcyCd)));
</pre>

Two end user Visitors are supplied: 
 - `SAccounts\Visitor\ChartPrinter` which prints the COA to the console
 - `SAccounts\Visitor\ChartArray` which returns the COA as an array of values and 
 account balances
 
Other Visitors are used internally in the Chart, but they should all give you a firm
grasp on how to create your own if you need to.

#### Journal Entries

##### Creating Entries

You create Journal entries in your accounts by adding Transactions to the system. A
Transaction is made up of two parts, the Journal description and a list of transaction
entries, one for each account that is effected by the transaction.  Those transactions
have a debit or credit amount.  The sum of all debits must equal the sum of all
credits so that the transaction balances in order for the Transaction to be accepted by
the system.

The basic Transaction type is the SplitTransaction:

<pre>
    /**
     * Constructor
     *
     * @param string $note Defaults to '' if not set
     * @param string $src  user defined source of transaction
     * @param int $ref user defined reference for transaction
     * @param \DateTime $date Defaults to today if not set
     */
    public function __construct(
        string $note = null,
        string $src = null,
        int $ref = null,
        \DateTime $date = null
    )
</pre>

After construction, you add transaction entries by passing an Entry object to the addEntry()
method.

<pre>
use SAccounts\Transaction\SplitTransaction;
use SAccounts\Transaction\Entry;

$amount = new IntType(100);
$txn = (new SplitTransaction())
     ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
     ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()))
</pre>

Here, we added two entries for same amount to two accounts, but with the transaction
type being debit for one and credit for the other.  You can check that the
transaction is balanced with the `checkBalance()` method which will return true
if the transaction is balanced or false otherwise.

Whilst the SplitTransaction is useful for adding transactions that comprise of many
entries (e.g. a sale comprises of sale account, vat account and a bank account entries),
for a simple two account entry(like a transfer between bank accounts) you can use the
SimpleTransaction, which is a child of SplitTransaction.

<pre>
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
    )
</pre>

Thus:

<pre>
use SAccounts\Transaction\SimpleTransaction;

$txn = new SimpleTransaction(new Nominal('0000'), new Nominal('1000'), 1226);
</pre>

Having created your transaction by whatever means, you can then add it to the accounts 
with:

<pre>
$txnId = $accountant->writeTransaction($txn);
</pre>

Writing a transaction automatically updates the COA ledger balances.

##### Retrieving Entries

You retrieve a single transaction from the accounts with

<pre>
/* @var SplitTransaction $txn */
$txn = $accountant->fetchTransaction(102);
</pre>

To retrieve all entries for an account ledger:
<pre>
/** @var Ds\Set $transactions */
$transactions = $accountant->fetchAccountJournals(new Nominal('2000'));
</pre>

The Set contains SplitTransactions with Entries reflecting only the side of the
double entry that the transaction acted up for that journal entry.  You can see an
example of how this works in `examples/journal-entries-example.php`.

## Notes

The library is built to rely on pure SQL in the database.  Whilst I'm providing
a PHP API layer to it, you can use the underlaying SQL from any language.  If you 
are a Python, Java or other developer, please feel free to add your own
language API under the `src` directory

My references here apply to the fact that I develop primarily in PHP.  If they don't 
apply to your dev language of choice, ignore them.

Finally, if in doubt, read the source code. It's well documented.  

## Further documentation

[Test Contract](https://github.com/chippyash/simple-accounts-3/blob/master/docs/Test-Contract.md) in the docs directory.

This library makes use of the Standard [PHP DS extension](https://www.php.net/manual/en/book.ds.php)

It also employs a great deal of functional programming derived from the [Monad](https://github.com/chippyash/monad)
and [Assembly](https://github.com/chippyash/Assembly-Builder) libraries. 

If you are unfamiliar with them, please take a moment to study them. 

As the library only deals with integers as values (to ensure numerical accuracy) you 
may want to have a look at the [Currency](https://github.com/chippyash/currency) library 
that is used in the example scripts.

Check out [ZF4 Packages](http://zf4.biz/packages?utm_source=github&utm_medium=web&utm_campaign=blinks&utm_content=simpleaccounts3) for more packages

## Changing the library

1.  fork it
2.  write the test
3.  amend it
4.  do a pull request

Found a bug you can't figure out?

1.  fork it
2.  write the test
3.  do a pull request

NB. Make sure you rebase to HEAD before your pull request

Or - raise an issue ticket.

## Where?

The library is hosted at [Github](https://github.com/chippyash/simple-accounts-3). It is
available at [Packagist.org](https://packagist.org/packages/chippyash/simple-accounts-3)

### Installation (PHP)

Install [Composer](https://getcomposer.org/)

#### For production

<pre>
    "chippyash/simple-accounts-3": "~2.0"
</pre>
 
`composer install --no-dev`

#### For development

Clone this repo, and then run Composer in local repo root to pull in dependencies

<pre>
    git clone git@github.com:chippyash/simple-accounts-3.git simple-accounts
    cd simple-accounts
    composer update
</pre>

- copy the `test/php/phpunit.xml` file to `test/php/local-phpunit.xml` and
edit it to ensure that you have the correct database connection parameters

- To run the tests: `composer test:run`
- To lint the code: `composer lint:run`
- To fix lint issues: `composer lint:fix`

## License

This software library is released under the [BSD 3 Clause license](https://opensource.org/licenses/BSD-3-Clause)

This software library is Copyright (c) 2017-2020, Ashley Kitson, UK

## History

V1.0.0 First production release

V1.0.1 Documentation for first release

V1.1.0 Add PHP Doctrine Migrations

V1.1.1 Change namespace for migrations

V1.1.2 Support PHP 7.0 & 7.1

V1.1.3 Support PHP 7.2

V1.2.0 Add ability to retrieve all transactions for an account

V1.2.1 docs licenses update

V1.3.0 Support for MySql

V1.3.1 File path amend for mariadb migration

V1.3.2 Remove ambiguity for treatment of real account balances

V1.4.0 Change of license from GPL V3 to BSD 3 Clause 

V1.4.1 Fix issue 1

V2.0.0 BC Break. PHP<7.2 support withdrawn. Remove dependency on chippyash/strongtype.