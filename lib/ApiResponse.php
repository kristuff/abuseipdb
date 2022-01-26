<?php declare(strict_types=1);

/**
 *       _                 ___ ___ ___  ___
 *  __ _| |__ _  _ ___ ___|_ _| _ \   \| _ )
 * / _` | '_ \ || (_-</ -_)| ||  _/ |) | _ \
 * \__,_|_.__/\_,_/__/\___|___|_| |___/|___/
 * 
 * This file is part of Kristuff\AbuseIPDB.
 *
 * (c) Kristuff <kristuff@kristuff.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @version    1.0
 * @copyright  2020-2022 Kristuff
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
        $this->decodedResponse = !empty($plaintext) ? json_decode($plaintext, false) : null;
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
     * @return \stdClass|null
     */
    public function getObject(): ?\stdClass
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
        return ($this->decodedResponse && property_exists($this->decodedResponse, 'errors')) ? $this->decodedResponse->errors : [];
    }

    /**
     * Get an internal error message in an ApiResponse object
     * 
     * @access public
     * @static
     * @param string    $message        The error message
     *
     * @return ApiResponse
     */
    public static function createErrorResponse(string $message): ApiResponse
    {
        $response = [
            "errors" => [
                [
                    "title"  => "Internal Error",
                    "detail" => $message
                ]
            ]
        ];

        return new ApiResponse(json_encode($response));
    }
}