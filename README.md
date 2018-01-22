# chippyash/simple-accounts-3

This library is under development. It replaces [chippyash/simple-accounts](https://github.com/chippyash/simple-accounts)

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

Run composer.phar install to load in the PHP dependencies.  Sorry, but PHP >= 5.6 only.

Create a database, let's say 'test'.

Create a database user, 'test' with password 'test'.

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

You can run the ./examples/currency-example.php program to see
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

Most observers of my library code output will note that I have a set layout for the 
readme file.  I apologise that I am breaking that mould with this one, but it really
is a stream of consciousness at the moment.

Automated build testing is going on.  This library is not yet ready for production use, 
but you can find build info at [Travis](https://travis-ci.org/chippyash/simple-accounts-3)
in preparation for a formal release.  
