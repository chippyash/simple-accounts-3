<?php
declare(strict_types=1);
/**
 * Simple Double Entry Accounting V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Test\SAccounts;

use SAccounts\AccountsException;
use SAccounts\ChartDefinition;

use org\bovigo\vfs\vfsStream;

class ChartDefinitionTest extends \PHPUnit_Framework_TestCase {

    protected $sut;

    protected $filePath;

    protected $xmlFile = <<<EOT
<?xml version="1.0"?>
<root><foo bar="2"/></root>
EOT;

    protected function setUp()
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('test.xml')
            ->withContent($this->xmlFile)
            ->at($root);
        $this->filePath = $file->url();
    }

    public function testCanConstructWithValidFileName()
    {
        $this->assertInstanceOf('SAccounts\ChartDefinition', new ChartDefinition($this->filePath));
    }

    public function testConstructionWithInvalidFileNameWillThrowException()
    {
        $this->expectException(AccountsException::class);
        new ChartDefinition('foo');
    }

    public function testConstructionWithValidFileNameWillReturnClass()
    {
        $sut = new ChartDefinition($this->filePath);
        $this->assertInstanceOf(ChartDefinition::class, $sut);
    }

    public function testGettingTheDefinitionWillThrowExceptionIfDefinitionFileIsInvalidXml()
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('test2.xml')
            ->withContent('')
            ->at($root);
        $sut = new ChartDefinition($file->url());

        $this->expectException(AccountsException::class);
        $sut->getDefinition();
    }

    public function testGettingDefinitionWillThrowExceptionIfDefinitionFailsValidation()
    {
        $this->expectException(AccountsException::class);
        $sut = new ChartDefinition($this->filePath);

        $this->assertInstanceOf('DOMDocument', $sut->getDefinition());
    }

    public function testGettingTheDefinitionWillReturnADomDocumentWithValidDefinitionFile()
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<chart name="Personal">
    <account nominal="0000" type="real" name="COA">
        <account nominal="1000" type="real" name="Balance Sheet"/>
    </account>
</chart>
EOT;

        $root = vfsStream::setup();
        $file = vfsStream::newFile('test3.xml')
            ->withContent($xml)
            ->at($root);
        $sut = new ChartDefinition($file->url());
        $this->assertInstanceOf('DOMDocument', $sut->getDefinition());
    }

}
