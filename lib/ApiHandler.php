<?php declare(strict_types=1);

/**
 *     _    _                    ___ ____  ____  ____
 *    / \  | |__  _   _ ___  ___|_ _|  _ \|  _ \| __ )
 *   / _ \ | '_ \| | | / __|/ _ \| || |_) | | | |  _ \
 *  / ___ \| |_) | |_| \__ \  __/| ||  __/| |_| | |_) |
 * /_/   \_\_.__/ \__,_|___/\___|___|_|   |____/|____/
 *
 * This file is part of Kristuff\AbuseIPDB.
 *
 * (c) Kristuff <kristuff@kristuff.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @version    0.9.13
 * @copyright  2020-2021 Kristuff
 */

namespace Kristuff\AbuseIPDB;

/**
 * Class ApiHandler
 * 
 * The main class to work with the AbuseIPDB API v2 
 */
class ApiHandler extends ApiBase
{
    /**
     * Curl helper functions
     */
    use CurlTrait;

    /**
     * @var string
     */
    const VERSION = 'v0.9.13'; 

    /**
     * The ips to remove from report messages
     * Generally you will add to this list yours ipv4 and ipv6, hostname, domain names
     * 
     * @access protected
     * @var array  
     */
    protected $selfIps = []; 

    /**
     * Constructor
     * 
     * @access public
     * @param string  $apiKey     The AbuseIPDB api key
     * @param array   $myIps      The Ips/domain name you don't want to display in report messages
     * 
     */
    public function __construct(string $apiKey, array $myIps = [])
    {
        $this->aipdbApiKey = $apiKey;
        $this->selfIps = $myIps;
    }

    /**
     * Get the current configuration in a indexed array
     * 
     * @access public
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return array(
            'apiKey'  => $this->aipdbApiKey,
            'selfIps' => $this->selfIps,
        );
    }

    /**
     * Performs a 'report' api request
     * 
     * Result, in json format will be something like this:
     *  {
     *       "data": {
     *         "ipAddress": "127.0.0.1",
     *         "abuseConfidenceScore": 52
     *       }
     *  }
     * 
     * @access public
     * @param string    $ip             The ip to report
     * @param string    $categories     The report category(es)
     * @param string    $message        The report message
     *
     * @return ApiResponse
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function report(string $ip, string $categories, string $message): ApiResponse
    {
         // ip must be set
        if (empty($ip)){
            throw new \InvalidArgumentException('Ip was empty');
        }

        // categories must be set
        if (empty($categories)){
            throw new \InvalidArgumentException('Categories list was empty');
        }

        // message must be set
        if (empty($message)){
            throw new \InvalidArgumentException('Report message was empty');
        }

        // validates categories, clean message 
        $cats = $this->validateReportCategories($categories);
        $msg  = $this->cleanMessage($message);

        // AbuseIPDB request
        return $this->apiRequest(
            'report', [
                'ip'            => $ip,
                'categories'    => $cats,
                'comment'       => $msg
            ],
            'POST'
        );
    }

    /**
     * Performs a 'bulk-report' api request
     * 
     * Result, in json format will be something like this:
     *   {
     *     "data": {
     *       "savedReports": 60,
     *       "invalidReports": [
     *         {
     *           "error": "Duplicate IP",
     *           "input": "41.188.138.68",
     *           "rowNumber": 5
     *         },
     *         {
     *           "error": "Invalid IP",
     *           "input": "127.0.foo.bar",
     *           "rowNumber": 6
     *         },
     *         {
     *           "error": "Invalid Category",
     *           "input": "189.87.146.50",
     *           "rowNumber": 8
     *         }
     *       ]
     *     }
     *   }
     *  
     * @access public
     * @param string    $filePath       The CSV file path. Could be an absolute or relative path.
     *
     * @return ApiResponse
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function bulkReport(string $filePath): ApiResponse
    {
        // check file exists
        if (!file_exists($filePath) || !is_file($filePath)){
            throw new \InvalidArgumentException('The file [' . $filePath . '] does not exist.');
        }

        // check file is readable
        if (!is_readable($filePath)){
            throw new InvalidPermissionException('The file [' . $filePath . '] is not readable.');
        }

        return $this->apiRequest('bulk-report', [], 'POST', $filePath);
    }

    /**
     * Perform a 'clear-address' api request
     * 
     *  Sample response:
     * 
     *    {
     *      "data": {
     *        "numReportsDeleted": 0
     *      }
     *    }
     * 
     * @access public
     * @param string    $ip             The IP to clear reports
     * 
     * @return ApiResponse
     * @throws \RuntimeException
     * @throws \InvalidArgumentException    When ip value was not set. 
     */
    public function clearAddress(string $ip): ApiResponse
    {
        // ip must be set
        if (empty($ip)){
            throw new \InvalidArgumentException('IP argument must be set.');
        }

        return $this->apiRequest('clear-address',  ['ipAddress' => $ip ], "DELETE") ;
    }

    /**
     * Perform a 'check' api request
     * 
     * @access public
     * @param string    $ip             The ip to check
     * @param int       $maxAgeInDays   Max age in days. Default is 30.
     * @param bool      $verbose        True to get the full response (last reports and countryName). Default is false
     * 
     * @return ApiResponse
     * @throws \RuntimeException
     * @throws \InvalidArgumentException    when maxAge is less than 1 or greater than 365, or when ip value was not set. 
     */
    public function check(string $ip, int $maxAgeInDays = 30, bool $verbose = false): ApiResponse
    {
        // max age must be less or equal to 365
        if ( $maxAgeInDays > 365 || $maxAgeInDays < 1 ){
            throw new \InvalidArgumentException('maxAgeInDays must be between 1 and 365.');
        }

        // ip must be set
        if (empty($ip)){
            throw new \InvalidArgumentException('ip argument must be set (empty value given)');
        }

        // minimal data
        $data = [
            'ipAddress'     => $ip, 
            'maxAgeInDays'  => $maxAgeInDays,  
        ];

        // option
        if ($verbose){
           $data['verbose'] = true;
        }

        return $this->apiRequest('check', $data, 'GET') ;
    }

    /**
     * Perform a 'check-block' api request
     * 
     * 
     * Sample json response for 127.0.0.1/24
     * 
     * {
     *    "data": {
     *      "networkAddress": "127.0.0.0",
     *      "netmask": "255.255.255.0",
     *      "minAddress": "127.0.0.1",
     *      "maxAddress": "127.0.0.254",
     *      "numPossibleHosts": 254,
     *      "addressSpaceDesc": "Loopback",
     *      "reportedAddress": [
     *        {
     *          "ipAddress": "127.0.0.1",
     *          "numReports": 631,
     *          "mostRecentReport": "2019-03-21T16:35:16+00:00",
     *          "abuseConfidenceScore": 0,
     *          "countryCode": null
     *        },
     *        {
     *          "ipAddress": "127.0.0.2",
     *          "numReports": 16,
     *          "mostRecentReport": "2019-03-12T20:31:17+00:00",
     *          "abuseConfidenceScore": 0,
     *          "countryCode": null
     *        },
     *        ...
     *      ]
     *    }
     *  }
     * 
     * 
     * @access public
     * @param string    $network        The network to check
     * @param int       $maxAgeInDays   The Max age in days, must 
     * 
     * @return ApiResponse
     * @throws \RuntimeException
     * @throws \InvalidArgumentException    when $maxAgeInDays is less than 1 or greater than 365, or when $network value was not set. 
     */
    public function checkBlock(string $network, int $maxAgeInDays = 30): ApiResponse
    {
        // max age must be between 1 and 365
        if ($maxAgeInDays > 365 || $maxAgeInDays < 1){
            throw new \InvalidArgumentException('maxAgeInDays must be between 1 and 365 (' . $maxAgeInDays . ' was given)');
        }

        // ip must be set
        if (empty($network)){
            throw new \InvalidArgumentException('network argument must be set (empty value given)');
        }

        // minimal data
        $data = [
            'network'       => $network, 
            'maxAgeInDays'  => $maxAgeInDays,  
        ];

        return $this->apiRequest('check-block', $data, 'GET');
    }

    /**
     * Perform a 'blacklist' api request
     * 
     * @access public
     * @param int       $limit              The blacklist limit. Default is 10000 (the api default limit) 
     * @param bool      $plainText          True to get the response in plaintext list. Default is false
     * @param int       $confidenceMinimum  The abuse confidence score minimum (subscribers feature). Default is 100.
     *                                      The confidence minimum must be between 25 and 100.
     *                                      This parameter is a subscriber feature (not honored otherwise).
     * 
     * @return ApiResponse
     * @throws \RuntimeException
     * @throws \InvalidArgumentException    When maxAge is not a numeric value, when $limit is less than 1. 
     */
    public function blacklist(int $limit = 10000, bool $plainText = false, int $confidenceMinimum = 100): ApiResponse
    {
        if ($limit < 1){
            throw new \InvalidArgumentException('limit must be at least 1 (' . $limit . ' was given)');
        }

        // minimal data
        $data = [
            'confidenceMinimum' => $confidenceMinimum, 
            'limit'             => $limit,
        ];

        // plaintext paremeter has no value and must be added only when true 
        // (set plaintext=false won't work)
        if ($plainText){
            $data['plaintext'] = $plainText;
        }

        return $this->apiRequest('blacklist', $data, 'GET');
    }
  
    /**
     * Perform a cURL request       
     * 
     * @access protected
     * @param string    $path           The api end path 
     * @param array     $data           The request data 
     * @param string    $method         The request method. Default is 'GET' 
     * @param string    $csvFilePath    The file path for csv file. When not empty, $data parameter is ignored and in place,
     *                                  the content of the given file if passed as csv. Default is empty string. 
     * 
     * @return ApiResponse
     * @throws \RuntimeException
     */
    protected function apiRequest(string $path, array $data, string $method = 'GET', string $csvFilePath = ''): ApiResponse
    {
        // set api url
        $url = $this->aipdbApiEndpoint . $path; 

        // set the wanted format, JSON (required to prevent having full html page on error)
        // and the AbuseIPDB API Key as a header
        $headers = [
            'Accept: application/json;',
            'Key: ' . $this->aipdbApiKey,
        ];

        // open curl connection
        $ch = curl_init(); 
  
        // for csv
        if (!empty($csvFilePath)){
            $cfile = new \CurlFile($csvFilePath,  'text/csv', 'csv');
            //curl file itself return the realpath with prefix of @
            $data = array('csv' => $cfile);
        }

        // set the method and data to send
        if ($method == 'POST') {
            $this->setCurlOption($ch, CURLOPT_POST, true);
            $this->setCurlOption($ch, CURLOPT_POSTFIELDS, $data);
        
        } else {
            $this->setCurlOption($ch, CURLOPT_CUSTOMREQUEST, $method);
            $url .= '?' . http_build_query($data);
        }

        // set the url to call
        $this->setCurlOption($ch, CURLOPT_URL, $url);
        $this->setCurlOption($ch, CURLOPT_RETURNTRANSFER, 1); 
        $this->setCurlOption($ch, CURLOPT_HTTPHEADER, $headers);
    
        // execute curl call
        $result = curl_exec($ch);
    
        // close connection
        curl_close($ch);
  
        return new ApiResponse($result !== false ? $result : '');
    }

    /** 
     * Clean message in case it comes from fail2ban <matches>
     * Remove backslashes and sensitive information from the report
     * @see https://wiki.shaunc.com/wikka.php?wakka=ReportingToAbuseIPDBWithFail2Ban
     * 
     * @access public
     * @param string      $message           The original message 
     *  
	 * @return string
     */
    public function cleanMessage(string $message): string
    {
        // Remove backslashes
        $message = str_replace('\\', '', $message);

        // Remove self ips
        foreach ($this->selfIps as $ip){
            $message = str_replace($ip, '*', $message);
        }

        // If we're reporting spam, further munge any email addresses in the report
        $emailPattern = "/[^@\s]*@[^@\s]*\.[^@\s]*/";
        $message = preg_replace($emailPattern, "*", $message);
        
        // Make sure message is less 1024 chars
        return substr($message, 0, 1024);
    }
}