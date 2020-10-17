<?php
declare(strict_types=1);
/**
 * Simple Double Entry Accounting V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license BSD-3-Clause See LICENSE.md
 */
namespace SAccounts;

use SAccounts\Zend\ErrorHandler;

/**
 * An Account Nominal code
 */
class Nominal
{
    /**
     * Value of the type
     *
     * @var mixed
     */
    protected $value;

    /**
     * Is PCRE compiled with Unicode support?
     *
     * @var bool
     **/
    protected static $hasPcreUnicodeSupport = null;

    /**
     * Constructor
     *
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->set($value);
    }

    /**
     * Set the object value.
     * Forces type
     *
     * @param mixed $value
     *
     * @return Nominal
     *
     * @see typeOf
     */
    public function set($value): Nominal
    {
        $this->value = $this->typeOf($value);

        return $this;
    }

    /**
     * Get the value of the object typed properly
     *
     * @return string
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Magic invoke method
     * Proxy to get()
     *
     * @see get
     *
     * @return string
     */
    public function __invoke(): string
    {
        return $this->get();
    }

    /**
     * Magic method - convert to string
     * Proxy to get()
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->get();
    }

    /**
     * This will filter out any non numeric characters.  You may potentially
     * get an empty string
     *
     * @param mixed $value
     * @return string
     */
    protected function typeOf($value): string
    {
        return (string) $this->filter($value);
    }

    /**
     * Lifted entirely from the Zend framework so that we don't have to include
     * the Zend\Filter package and all its dependencies.
     *
     * @param  string $value
     * @return string
     * zendframework/zend-filter/Zend/Filter/Digits.php
     * Zend Framework (http://framework.zend.com/)
     *
     * @link      http://github.com/zendframework/zf2 for the canonical source repository
     * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   http://framework.zend.com/license/new-bsd New BSD License
     * Defined by Zend\Filter\FilterInterface
     *
     * Returns the string $value, removing all but digit characters
     *
     * If the value provided is non-scalar, the value will remain unfiltered
     *
     */
    protected function filter($value): string
    {
        if (!is_scalar($value)) {
            return $value;
        }
        $value = (string) $value;

        if (!$this->hasPcreUnicodeSupport()) {
            // POSIX named classes are not supported, use alternative 0-9 match
            return preg_replace('/[^0-9]/', '', $value);
        }

        if (extension_loaded('mbstring')) {
            // Filter for the value with mbstring
            return preg_replace('/[^[:digit:]]/', '', $value);
        }

        // Filter for the value without mbstring
        return preg_replace('/[\p{^N}]/', '', $value);
    }

    /**
     * Lifted entirely from Zend Framework (http://framework.zend.com/) so we don't have
     * to include Zend/Stdlib
     *
     * @link      http://github.com/zendframework/zf2 for the canonical source repository
     * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   http://framework.zend.com/license/new-bsd New BSD License
     * Is PCRE compiled with Unicode support?
     *
     * @return bool
     */
    protected function hasPcreUnicodeSupport(): bool
    {
        if (static::$hasPcreUnicodeSupport === null) {
            ErrorHandler::start();
            static::$hasPcreUnicodeSupport =
                defined('PREG_BAD_UTF8_OFFSET_ERROR') && preg_match('/\pL/u', 'a') == 1;
            ErrorHandler::stop();
        }
        return static::$hasPcreUnicodeSupport;
    }
}