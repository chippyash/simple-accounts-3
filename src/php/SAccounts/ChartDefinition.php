<?php
/**
 * Simple Double Entry Accounting 2
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts;


use Chippyash\Type\String\StringType;

/**
 * Helper to retrieve chart definition xml
 */
class ChartDefinition
{
    /**
     * @var StringType
     */
    protected $xmlFileName;

    /**
     * Constructor
     *
     * @param StringType $xmlFileName
     *
     * @throws AccountsException
     */
    public function __construct(StringType $xmlFileName)
    {
        if (!file_exists($xmlFileName())) {
            throw new AccountsException("Invalid file name: {$xmlFileName}");
        }
        $this->xmlFileName = $xmlFileName;
    }

    /**
     * Get chart definition as a DOMDocument
     *
     * @return \DOMDocument
     * @throws AccountsException
     */
    public function getDefinition()
    {
        $err = '';
        set_error_handler(function($number, $error) use ($err) {
            $err = $error;
            if (preg_match('/^DOMDocument::load\(\): (.+)$/', $error, $m) === 1) {
                throw new AccountsException($m[1]);
            }
        });
        $dom = new \DOMDocument();
        $dom->load($this->xmlFileName->get());

        if (!$dom->schemaValidate(dirname(dirname(__DIR__)) .'/xsd/chart-definition.xsd')) {
            throw new AccountsException('Definition does not validate: ' . $err);
        }

        restore_error_handler();
        return $dom;
    }
}