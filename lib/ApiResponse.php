<?php declare(strict_types=1);

/**
 *     _    _                    ___ ____  ____  ____
 *    / \  | |__  _   _ ___  ___|_ _|  _ \|  _ \| __ )
 *   / _ \ | '_ \| | | / __|/ _ \| || |_) | | | |  _ \
 *  / ___ \| |_) | |_| \__ \  __/| ||  __/| |_| | |_) |
 * /_/   \_\_.__/ \__,_|___/\___|___|_|   |____/|____/
 *
 * This file is part of Kristuff\AbsuseIPDB.
 *
 * (c) Kristuff <contact@kristuff.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @version    0.9.9
 * @copyright  2020-2021 Kristuff
 */

namespace Kristuff\AbuseIPDB;

/**
 * Class ApiResponse
 * 
 */
class ApiResponse
{
    /**
     * 
     * @access protected
     * @var string 
     */
    protected $curlResponse; 

    /**
     * 
     * @access protected
     * @var object 
     */
    protected $decodedResponse; 

    /**
     * Constructor
     * 
     * @access public
     * @param string     $plaintext      AbuseIPDB response in plaintext 
     * 
     */
    public function __construct(?string $plaintext = null)
    {
        $this->curlResponse = $plaintext;
        $this->decodedResponse = json_decode($plaintext, false);
    }

    /**
     * Get response as array. May return null
     * 
     * @access public
     * 
     * @return array|null
     */
    public function getArray(): ?array
    {
        return json_decode($this->curlResponse, true);
    }

    /**
     * Get response as object. May return null
     * 
     * @access public
     * 
     * @return object|null
     */
    public function getObject(): ?object
    {
        return $this->decodedResponse;
    }

    /**
     * Get response as plaintext. May return null
     * 
     * @access public
     * 
     * @return string|null
     */
    public function getPlaintext(): ?string
    {
        return $this->curlResponse;
    }
    
    /**
     * Get whether the response contains error(s)
     * 
     * @access public
     * 
     * @return bool
     */
    public function hasError(): bool
    {
        return count($this->errors()) > 0;
    }

    /**
     * Get an array of errors (object) contained is response 
     * 
     * @access public
     * 
     * @return array
     */
    public function errors(): array
    {
        return ($this->decodedResponse && $this->decodedResponse->errors) ? $this->decodedResponse->errors : [];
    }
}