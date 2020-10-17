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

use Doctrine\DBAL\Driver\PDOException;
use Zend\Db\Adapter\Exception\InvalidQueryException;

/**
 * SAccounts Database Exception Class
 */
class DbException extends AccountsException
{
    public function __construct(\Throwable $previous)
    {
        $errMsg = $previous->getMessage();
        $matches = [];
        if ($previous instanceof InvalidQueryException) {
            preg_match(
                '/.*45000 - (?P<code>\d+) - (?P<err>[\w, ]+)\)/', $errMsg, $matches
            );
        }
        if ($previous instanceof \PDOException) {
            preg_match('/.*\[45000\].*: (?P<code>\d+) (?P<err>[\w, ]+)/', $errMsg, $matches);
        }
        parent::__construct($matches['err'], (int) $matches['code'], $previous);
    }
}