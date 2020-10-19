<?php

declare(strict_types=1);

/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license BSD-3-Clause See LICENSE.md
 */
namespace SAccounts;

/**
 * Helper to retrieve chart definition xml
 */
class ChartDefinition
{
    /**
     * @var string
     */
    protected $xmlFileName;

    /**
     * Constructor
     *
     * @param string $xmlFileName
     *
     * @throws AccountsException
     */
    public function __construct(string $xmlFileName)
    {
        if (!file_exists($xmlFileName)) {
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
    public function getDefinition(): \DOMDocument
    {
        $err = '';
        set_error_handler(function ($number, $error) use (&$err): void {
            $err = $error;
            if (preg_match('/^DOMDocument::load\(\): (.+)$/', $error, $m) === 1) {
                throw new AccountsException($m[1]);
            }
        });
        $dom = new \DOMDocument();
        $dom->load($this->xmlFileName);

        if (!$dom->schemaValidate(dirname(dirname(__DIR__)) . '/xsd/chart-definition.xsd')) {
            throw new AccountsException('Definition does not validate: ' . $err);
        }

        restore_error_handler();

        return $dom;
    }
}
