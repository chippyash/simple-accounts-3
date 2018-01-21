# chippyash/simple-accounts-3

This library is under development. It replaces [chippyash/simple-accounts](https://github.com/chippyash/simple-accounts)

## Requirements whilst under development
You will need MariaDB >=10 with the [OQGraph plugin](https://mariadb.com/kb/en/library/oqgraph-storage-engine/)
installed.

No Windows support is provided at present.  If you want it, please feel free to make
a pull request.  The library is developed under Linux.

Run composer.phar install to load in the PHP dependencies.  Sorry, but PHP >= 5.6 only.

Create a database, let's say 'test'.

Create a database user, 'test' with password 'test'.

Give that user all rights to the test database.

Now run the create script:
`./createdb.sh test test test` 

to create the database components.

You can run SQL tests by executing `./sqltest.sh`

You can run PHP tests by executing `./build.sh`. This also generates the test contract
in the ./docs directory.

You can run the ./examples/currency-example.php program to see
how you can convert between floating and integer types.  The library
and supporting database only handle integers, so if you need float support,
use [Chippyash\Currency](https://github.com/chippyash/currency) or provide your own handlers.


## Notes

The library is built to rely on pure SQL in the database.  Whilst I'm providing
a PHP API layer to it, you can use the underlaying SQL from any language.  If you 
are a Python, Java or other developer, please feel free to add your own
language API under the `src` directory