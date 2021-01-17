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
 * Class SilentApiHandler
 * 
 * Overwrite ApiHandler with Exception handling
 * Instead of Exception, all method return an ApiResponse that may 
 * contains errors from the AbuseIPDB API, or internal errors  
 */
class SilentApiHandler extends ApiHandler
{
    /**
     * Get an internal error message in an ApiResponse object
     * 
     * @access public
     * @param string    $message        The error message
     *
     * @return ApiResponse
     */
    public function getErrorResponse(string $message): ApiResponse
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

    /**
     * Performs a 'report' api request, with Exception handling
     * 
     * @access public
     * @param string    $ip             The ip to report
     * @param string    $categories     The report category(es)
     * @param string    $message        The report message
     *
     * @return ApiResponse
     */
    public function report(string $ip, string $categories, string $message): ApiResponse
    {
        try {
            return parent::report($ip,$categories,$message);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e->getMessage());
        }
    }

    /**
     * Performs a 'bulk-report' api request, with Exception handling
     * 
     * @access public
     * @param string    $filePath       The CSV file path. Could be an absolute or relative path.
     *
     * @return ApiResponse
     */
    public function bulkReport(string $filePath): ApiResponse
    {
        try {
            return parent::bulkReport($filePath);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e->getMessage());
        }
    }

    /**
     * Perform a 'clear-address' api request, with Exception handling
     * 
     * @access public
     * @param string    $ip             The IP to clear reports
     * 
     * @return ApiResponse
     */
    public function clearAddress(string $ip): ApiResponse
    {
        try {
            return parent::clearAddress($ip);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e->getMessage());
        }
    }

    /**
     * Perform a 'check' api request, with Exception handling
     * 
     * @access public
     * @param string    $ip             The ip to check
     * @param int       $maxAgeInDays   Max age in days. Default is 30.
     * @param bool      $verbose        True to get the full response (last reports and countryName). Default is false
     * 
     * @return ApiResponse
     */
    public function check(string $ip, int $maxAgeInDays = 30, bool $verbose = false): ApiResponse
    {
        try {
            return parent::check($ip, $maxAgeInDays, $verbose);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e->getMessage());
        }
    }

    /**
     * Perform a 'check-block' api request, with Exception handling
     * 
     * @access public
     * @param string    $network        The network to check
     * @param int       $maxAgeInDays   The Max age in days, must 
     * 
     * @return ApiResponse
     */
    public function checkBlock(string $network, int $maxAgeInDays = 30): ApiResponse
    {
        try {
            return parent::checkBlock($network, $maxAgeInDays);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e->getMessage());
        }
    }

    /**
     * Perform a 'blacklist' api request, with Exception handling
     * 
     * @access public
     * @param int       $limit              The blacklist limit. Default is 10000 (the api default limit) 
     * @param bool      $plainText          True to get the response in plaintext list. Default is false
     * @param int       $confidenceMinimum  The abuse confidence score minimum (subscribers feature). Default is 100.
     *                                      The confidence minimum must be between 25 and 100.
     *                                      This parameter is subscriber feature (not honored otherwise).
     * 
     * @return ApiResponse
     */
    public function blacklist(int $limit = 10000, bool $plainText = false, int $confidenceMinimum = 100): ApiResponse
    {
        try {
            return parent::blacklist($limit, $plainText, $confidenceMinimum);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e->getMessage());
        }
    }
}