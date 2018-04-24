# chippyash/simple-accounts-3

## Quality Assurance

![PHP 5.6](https://img.shields.io/badge/PHP-5.6-blue.svg)
[![Build Status](https://travis-ci.org/chippyash/simple-accounts-3.svg?branch=master)](https://travis-ci.org/chippyash/simple-accounts-3)
[![Test Coverage](https://api.codeclimate.com/v1/badges/5811a42ebda892357fba/test_coverage)](https://codeclimate.com/github/chippyash/simple-accounts-3/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/5811a42ebda892357fba/maintainability)](https://codeclimate.com/github/chippyash/simple-accounts-3/maintainability)


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
You will need MariaDB >=10 with the [OQGraph plugin](https://mariadb.com/kb/en/library/oqgraph-storage-engine/)
installed. Take a look at the `.travis.yml` build script for how we do this on the
Travis build servers.  One 'gotcha' that we know about is the setting of the user
creating the triggers. Depending on your MariaDb setup, you may need to give the database
creation script user 'SUPER' privileges.  There seems no rhyme nor reason as to why this
is, but be aware.  For MariaDb 10 on the travis servers, it needs setting. See 
`scripts/test-user.sql` 

No Windows support is provided at present.  If you want it, please feel free to make
a pull request.  The library is developed under Linux.

## How

### Getting it up and running

Run composer.phar install to load in the PHP dependencies.  Sorry, but PHP ~5.6 only.
PHP 7 isn't tested yet, you'll see a badge in Quality Assurance when it is

Create a database, let's say 'test'.

Create a database user, 'test' with password 'test'. (You can run `scripts\test-user.sql`
to do this.)

Give that user all rights to the test database. (see note above re SUPER privs)

Now run the create script:
`./createdb.sh test test test` 

to create the database components.

You can run SQL tests by executing `./sqltest.sh`.  Please be aware that this script is
bound to db = test, uid = test, pwd = test. 

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

Pay salary of £4,203.45 into Bank
Spend £120.16 on food
Save £500.00 for a rainy day

Nominal Name                     DR            CR            Balance
0000    COA                      £4,823.61     £4,823.61         £0.00
0001    Balance Sheet            £4,703.45       £620.16     £4,083.29
1000    Assets                   £4,703.45       £620.16     £4,083.29
1100    Current Assets           £4,703.45       £620.16     £4,083.29
1200    At Bank                  £4,703.45       £620.16     £4,083.29
1210    Current Accounts         £4,203.45       £620.16     £3,583.29
1220    Savings Accounts           £500.00         £0.00       £500.00
1300    Shares                       £0.00         £0.00         £0.00
1400    Bonds                        £0.00         £0.00         £0.00
1500    Fixed Assets                 £0.00         £0.00         £0.00
1600    Property                     £0.00         £0.00         £0.00
1700    Vehicles                     £0.00         £0.00         £0.00
1800    Equipment                    £0.00         £0.00         £0.00
2000    Liabilities                  £0.00         £0.00         £0.00
2100    Mortgages                    £0.00         £0.00         £0.00
2200    Loans                        £0.00         £0.00         £0.00
3000    Equity                       £0.00         £0.00         £0.00
3100    Opening Balance              £0.00         £0.00         £0.00
0002    Profit And Loss            £120.16     £4,203.45     £4,083.29
4000    Income                       £0.00     £4,203.45     £4,203.45
4100    Salary & Wages               £0.00     £4,203.45     £4,203.45
4200    Misc paid work               £0.00         £0.00         £0.00
6000    Expenses                   £120.16         £0.00       £120.16
6100    House                        £0.00         £0.00         £0.00
6110    Repairs                      £0.00         £0.00         £0.00
6120    Garden                       £0.00         £0.00         £0.00
6121    Gardener                     £0.00         £0.00         £0.00
6122    Plants                       £0.00         £0.00         £0.00
6123    Consumables                  £0.00         £0.00         £0.00
6130    Services                     £0.00         £0.00         £0.00
6131    Window Cleaner               £0.00         £0.00         £0.00
6132    Laundry                      £0.00         £0.00         £0.00
6140    Property Tax                 £0.00         £0.00         £0.00
6200    Travel                       £0.00         £0.00         £0.00
6300    Insurance                    £0.00         £0.00         £0.00
6310    Buildings Insurance          £0.00         £0.00         £0.00
6320    Contents Insurance           £0.00         £0.00         £0.00
6330    Health Insurance             £0.00         £0.00         £0.00
6340    Travel Insurance             £0.00         £0.00         £0.00
6400    Food                       £120.16         £0.00       £120.16
6500    Leisure                      £0.00         £0.00         £0.00
6510    Holidays                     £0.00         £0.00         £0.00
6520    Memberships                  £0.00         £0.00         £0.00
6530    Events                       £0.00         £0.00         £0.00
6600    Utilities                    £0.00         £0.00         £0.00
6610    Gas                          £0.00         £0.00         £0.00
6620    Electricity                  £0.00         £0.00         £0.00
6630    Water                        £0.00         £0.00         £0.00
6640    Telephone                    £0.00         £0.00         £0.00
6650    Internet                     £0.00         £0.00         £0.00
6700    Interest                     £0.00         £0.00         £0.00
6710    Mortgage Interest            £0.00         £0.00         £0.00
6720    Loan Interest                £0.00         £0.00         £0.00
7000    Other Income                 £0.00         £0.00         £0.00
7100    Interest Received            £0.00         £0.00         £0.00
8000    Other Expenses               £0.00         £0.00         £0.00
8100    Interest Payments            £0.00         £0.00         £0.00

Go look at the database journal tables for their entries

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
runs on MariaDb with the OQGraph plugin installed.  OQGraph provides a very fast and
efficient way of using hierarchical data within an RDMS.  The alternative is to use
some [nested set strategy](http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/)
which can of course be done, but slows down the whole operation.  If you fancy doing 
a plain nested set implementation, please consider contributing to this library.

The SQL API is provided via stored procedures.  If you want to provide variants, please
respect the API.  You can see the procedure definitions in the [src/sql/build-procs](https://github.com/chippyash/simple-accounts-3/blob/master/src/sql/build-procs.sql)
script and the trigger that maintains the account balances in the [src/sql/build-triggers](https://github.com/chippyash/simple-accounts-3/blob/master/src/sql/build-triggers.sql)
script.

One slightly baffling procedure is `sa_fu_add_txn`. In particular the parameters, 
- arNominals TEXT,
- arAmounts TEXT,
- arTxnType TEXT

These require a matching set of comma delimited values, which is the only way of getting an array into SQL.:
- arNominals: the list of nominals to effect
- arAmounts: the amount to use
- arTxnType: dr or cr for each nominal

The php code deals with this by imploding values into a string before calling the SP:

See Accountant::writeTransaction() method.

Otherwise the SQL is pretty straight forward.  Study the OQGraph
docs for an understanding of how it's being used but. Magic underneath, but simple to use - my kind of code ;-) 

If you are a better SQL Head than me (not hard!), then I'd appreciate any suggestions
for operational efficiency.

### Coding Basics (PHP)
#### Changes from previous library versions
##### Organisations
Unlike the previous version of this library, we don't support the concepts of
organisations.  Organisations are outside of the remit of this
library as your implementation of them will differ according to your needs. Instead
you should plan on creating some form of many to many table between your organisations
and any chart of accounts (COA) that they use.  The `sa_coa` table can hold an 
infinite number of COAs, so it shouldn't be too much of a problem.
##### Control accounts
Like Organisations, we don't support the concept of control accounts in this library.
They are again an implementation detail between your application and this library,
more usually a configuration issue.  So add config linking where you need such functionality.
Another problem was the use of the term.  Too many accountants objected to it being
used in its previous incarnation, that it was safer to leave it out.
 
**To Be Continued**

You can define charts using xml.  See `src/xml/personal.xml` which is used in the
example program to create the COA.  The top or root account should always be of type 'real'.
The root account should be the only 'real' account. 

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

### Installation

Install [Composer](https://getcomposer.org/)

#### For production

<pre>
    "chippyash/simple-accounts-3": "~1.0"
</pre>
 
#### For development

Clone this repo, and then run Composer in local repo root to pull in dependencies

<pre>
    git clone git@github.com:chippyash/simple-accounts-3.git simple-accounts
    cd simple-accounts
    composer update
</pre>

To run the tests:

<pre>
    cd simple-accounts
    vendor/bin/phpunit -c test/phpunit.xml test/
</pre>

## License

This software library is released under the [GNU GPL V3 or later license](http://www.gnu.org/copyleft/gpl.html)

This software library is Copyright (c) 2017-2018, Ashley Kitson, UK

A commercial license is available for this software library, please contact the author. 
It is normally free to deserving causes, but gets you around the limitation of the GPL
license, which does not allow unrestricted inclusion of this code in commercial works.

This library is supported by <a href="https://www.jetbrains.com"><img src="https://github.com/chippyash/Strong-Type/raw/master/img/JetBrains.png" alt="Jetbrains" style="height: 200px;vertical-align: middle;"></a>
who provide their IDEs to Open Source developers.


## History

V1.0.0 First production release