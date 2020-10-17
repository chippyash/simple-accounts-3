<?php
declare(strict_types=1);
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Test\SAccounts\Transaction;

use SAccounts\AccountType;
use SAccounts\Nominal;
use SAccounts\Transaction\Entries;
use SAccounts\Transaction\Entry;


class EntriesTest extends \PHPUnit_Framework_TestCase
{
    public function testYouCanCreateAnEmptyEntriesCollection()
    {
        $this->assertInstanceOf(Entries::class, new Entries());
    }

    public function testYouCanCreateAnEntriesCollectionsWithEntryValues()
    {
        $this->assertInstanceOf(
            'SAccounts\Transaction\Entries',
            new Entries(
                array(
                    $this->getEntry('7789', 1234, 'dr'),
                    $this->getEntry('3456', 617, 'cr'),
                    $this->getEntry('2001', 617, 'cr'),
                )
            )
        );
    }

    public function testYouCannotCreateAnEntriesCollectionWithNonEntryValues()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Value 0 is not a SAccounts\Transaction\Entry');
        new Entries(array(new \stdClass()));
    }

    public function testYouCanAddAnotherEntryToEntriesAndGetNewEntriesCollection()
    {
        $sut1 = new Entries(
            array(
                $this->getEntry('7789', 1234, 'dr'),
                $this->getEntry('3456', 617, 'cr'),
                $this->getEntry('2001', 617, 'cr'),
            )
        );

        $sut2 = $sut1->addEntry($this->getEntry('3333',1226,'cr'));

        $this->assertInstanceOf(Entries::class, $sut2);
        $this->assertEquals(3, count($sut1));
        $this->assertEquals(4, count($sut2));
        $this->assertTrue($sut1 != $sut2);
    }

    public function testCheckBalanceWillReturnTrueIfEntriesAreBalanced()
    {
        $sut1 = new Entries(
            array(
                $this->getEntry('7789', 1234, 'dr'),
                $this->getEntry('3456', 617, 'cr'),
                $this->getEntry('2001', 617, 'cr'),
            )
        );

        $this->assertTrue($sut1->checkBalance());
    }

    public function testCheckBalanceWillReturnFalseIfEntriesAreNotBalanced()
    {
        $sut1 = new Entries(
            array(
                $this->getEntry('7789', 1234, 'dr'),
                $this->getEntry('3456', 617, 'cr'),
            )
        );

        $this->assertFalse($sut1->checkBalance());
    }

    protected function getEntry($id, $amount, $type)
    {
        return new Entry(
            new Nominal($id),
            $amount,
            ($type == 'dr' ? AccountType::DR() : AccountType::CR())
        );
    }
}
