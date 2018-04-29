# Simple Accounts V3

## SAccounts\Account

*  ✓ You can create any valid account type
*  ✓ You can get a balance for account types that support it
*  ✓ Getting balance of a real account type will throw an exception
*  ✓ Getting balance of a dummy account type will throw an exception
*  ✓ You can get the account nominal code
*  ✓ You can get the account type
*  ✓ You can get the account name
*  ✓ You can get the debit and credit amounts

## SAccounts\AccountType

*  ✓ Can get values as constants
*  ✓ Can get values as classes using static methods
*  ✓ Can get a debit column title for a valid account type
*  ✓ Get a debit column title with invalid account type will throw exception
*  ✓ Get a credit column title with invalid account type will throw exception
*  ✓ Can get a credit column title for a valid account type
*  ✓ Will get correct balance for all valid account types
*  ✓ Get a balance with invalid account type will throw exception

## SAccounts\Accountant

*  ✓ An accountant can create a new chart of accounts
*  ✓ An accountant can fetch a chart
*  ✓ Fetching a chart when chart id is not set will throw an exception
*  ✓ You can write a transaction to a journal and update a chart
*  ✓ Writing a transaction when chart id is not set will throw an exception
*  ✓ You can fetch a journal transaction by its id
*  ✓ You can add an account to a chart
*  ✓ Adding an account to a non existent parent will throw an exception
*  ✓ Trying to add a second root account will throw an exception
*  ✓ You can delete a zero balance account
*  ✓ Deleting a non zero balance account will throw an exception
*  ✓ You can fetch journal entries for an account
*  ✓ Fetching journal entries returns a set of split transactions
*  ✓ Fetching journal entries for an aggregate account will return an empty set

## SAccounts\ChartDefinition

*  ✓ Can construct with valid file name
*  ✓ Construction with invalid file name will throw exception
*  ✓ Construction with valid file name will return class
*  ✓ Getting the definition will throw exception if definition file is invalid xml
*  ✓ Getting definition will throw exception if definition fails validation
*  ✓ Getting the definition will return a dom document with valid definition file

## SAccounts\Chart

*  ✓ Construction creates chart
*  ✓ You can give a chart an optional tree in construction
*  ✓ You can get an account if it exists
*  ✓ Trying to get a non existent account will throw an exception
*  ✓ You can test if a chart has an account
*  ✓ Trying to get a parent id of a non existent account will throw an exception
*  ✓ Getting the parent id of an account that has a parent will return the parent nominal
*  ✓ You can provide an optional internal id when constructing a chart
*  ✓ You can set the chart root node

## SAccounts\Transaction\Entries

*  ✓ You can create an empty entries collection
*  ✓ You can create an entries collections with entry values
*  ✓ You cannot create an entries collection with non entry values
*  ✓ You can add another entry to entries and get new entries collection
*  ✓ Check balance will return true if entries are balanced
*  ✓ Check balance will return false if entries are not balanced

## SAccounts\Transaction\Entry

*  ✓ An entry requires an id an amount and a type
*  ✓ An entry must have cr or dr type
*  ✓ Constructing an entry with invalid type will throw exception
*  ✓ You can get the id of an entry
*  ✓ You can get the amount of an entry
*  ✓ You can get the type of an entry

## Chippyash\Test\SAccounts\Transaction\SimpleTransaction

*  ✓ Basic construction sets an empty note on the transaction
*  ✓ Basic construction sets date for today on the transaction
*  ✓ You can set an optional note on construction
*  ✓ You can set an optional source on construction
*  ✓ You can set an optional reference on construction
*  ✓ You can set an optional date on construction
*  ✓ Constructing a transaction does not set its id
*  ✓ You can set and get an id
*  ✓ You can get the debit account code
*  ✓ You can get the credit account code
*  ✓ You can get the transaction amount
*  ✓ You can get the transaction note
*  ✓ You can get the transaction datetime

## SAccounts\Transaction\SplitTransaction

*  ✓ Basic construction sets an empty note on the transaction
*  ✓ Basic construction sets date for today on the transaction
*  ✓ You can set an optional note on construction
*  ✓ A null note will be retrieved as an empty string
*  ✓ You can set an optional source on construction
*  ✓ A null source will be retrieved as an empty string
*  ✓ You can set an optional reference on construction
*  ✓ A null reference will be retrieved as a zero integer
*  ✓ You can set an optional date on construction
*  ✓ Constructing a split transaction does not set its id
*  ✓ You can set and get an id
*  ✓ Getting the debit account for a split transaction will return an array of nominals
*  ✓ Getting the credit account for a split transaction will return an array of nominals
*  ✓ Checking if a split transaction is balanced will return true if balanced
*  ✓ Checking if a split transaction is balanced will return false if not balanced
*  ✓ You can get the total transaction amount if the transaction is balanced
*  ✓ If the transaction is not balanced getting the total transaction amount will throw an exception
*  ✓ You can get the transaction note
*  ✓ You can get the transaction datetime
*  ✓ A split transaction is simple if it has one dr and one cr entry
*  ✓ You can get an entry by its nominal id
*  ✓ Getting an unknown entry will throw an exception

## SAccounts\Visitor\ChartArray

*  ✓ Constructing with no currency will return integer values
*  ✓ Constructing with a currency will return floats dependent on the currency precision

## SAccounts\Visitor\ChartPrinter

*  ✓ The output is sent to the console
*  ✓ Output is formatted using the currency symbol


Generated by [chippyash/testdox-converter](https://github.com/chippyash/Testdox-Converter)