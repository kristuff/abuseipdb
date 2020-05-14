<?php

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
 * @version    0.1.0
 * @copyright  2020 Kristuff
 */

namespace Kristuff\AbuseIPDB;

/**
 * Class ApiManager
 * 
 * The main class to work with the AbuseIPDB API v2 
 */
class ApiManager extends ApiDefintion
{
    /**
     * AbuseIPDB API key
     *  
     * @access protected
     * @var string $aipdbApiKey  
     */
    protected $aipdbApiKey = null; 

    /**
     * AbuseIPDB user id 
     * 
     * @access protected
     * @var string $aipdbUserId  
     */
    protected $aipdbUserId = null; 

    /**
     * The ips to remove from message
     * Generally you will add to this list yours ipv4 and ipv6, and the hostname
     * 
     * @access protected
     * @var array $selfIps  
     */
    protected $selfIps = []; 

    /**
     * Constructor
     * 
     * @access public
     * @param string  $apiKey     The AbuseIPDB api key
     * @param string  $userId     The AbuseIPDB user's id
     * @param array   $myIps      The Ips you dont want to report
     * 
     */
    public function __construct(string $apiKey, string $userId, array $myIps = [])
    {
        $this->aipdbApiKey = $apiKey;
        $this->aipdbUserId = $userId;
        $this->selfIps = $myIps;
    }

    /**
     * Get the current configuration in a indexed array
     * 
     * @access public 
     * @return array
     */
    public function getConfig()
    {
        return array(
            'userId'  => $this->aipdbUserId,
            'apiKey'  => $this->aipdbApiKey,

          // TODO  'selfIps' => $this->selfIps,
          // TODO default report cat 
        );
    }

    /**
     * Get a new instance of ApiManager with config stored in a Json file
     * 
     * @access public 
     * @static
     * @param string    $configPath     The configuration file path
     * 
     * @return \Kristuff\AbuseIPDB\ApiManager
     */
    public static function fromConfigFile(string $configPath)
    {
        //todo check file exist
        $config = self::loadJsonFile($configPath);

        // TODO $config->self_ips
        // TODO other options
        return new ApiManager($config->api_key, $config->user_id);
    }

    /**
     * Get the list of report categories
     * 
     * @access public 
     * @return array
     */
    public function getCategories()
    {
        return $this->aipdbApiCategories;
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
     * @param array     $categories     The report categories
     * @param string    $message        The report message
     *
     * @return stdClass|array
     * @throws \InvalidArgumentException
     */
    public function report(string $ip = '', array $categories = [], $message = '')
    {
         // ip must be set
        if (empty($ip)){
            throw new \InvalidArgumentException('Ip was empty');
        }

        // categories must be set
        if (empty($categories)){
            throw new \InvalidArgumentException('categories list was empty');
        }

        // message must be set
          if (empty($message)){
            throw new \InvalidArgumentException('report message was empty');
        }

        // TODO valider les cat / seules pas seules...
        // TODO clean message ? selfips list 
        $cats = $this->validateCategories($categories);

        // report AbuseIPDB request
        
        
        //TODO
        return $this->apiRequest('report', 'POST', [
            'ip' => $ip,
            'categories' =>   'TODO', '21,15',
            'comment' => $message
        ]);
    }

    /**
     * Check if the category(ies) given is/are valid
     * Check for shortname or id, and categories that can't be used alone 
     * 
     * @access public
     * @param array $categories       The report categories list
     *
     * @return string               Formatted string id list ('18,2,3...')
     * @throws \InvalidArgumentException
     */
    public function validateCategories(array $categories = [])
    {
        $newList = [];
        $needAnother = false;

        foreach ($categories as $cat){

        }
        //todo

    }

    /**
     * Perform a 'check' api request
     * 
     * 
     *  TODO        OPTION POUR VERBOSE ;;;
     *              force $maxAge int as parameter ?
     * 
     * @access public
     * @param string $ip        The ip to check
     * @param string $maxAge    Max age in days
     *
     * @return stdObj
     * @throws \InvalidArgumentException
     */
    public function check(string $ip = null, string $maxAge = '30')
    {
        
        $maxAge = intval($maxAge);

        // max age must less or equal to 365
        if ($maxAge > 365 || $maxAge < 1){
            throw new \InvalidArgumentException('maxAge must be at least 1 and less than 365 (' . $maxAge . ' was given)');
        }

        //ip must be set
        if (empty($ip)){
            throw new \InvalidArgumentException('ip argument must be set (null given)');
        }

        // check AbuseIPDB request
        return $this->apiRequest('check', 'GET', [
             'ipAddress' => $ip, 
             'maxAgeInDays' => $maxAge,  
             'verbose' => true 
        ]);
    }

    /**
     * Perform a cURL request       TODO: option as array
     * 
     * @access protected
     * @param string    $path      The api end path 
     * @param string    $method    The request method. Default is 'GET' 
     * @param array     $data      The request data 
     * 
     * @return stdObj TODO object ARRAY ;;;
     */
    protected function apiRequest(string $path, string $method = 'GET', array $data) 
    {
        // set api url
        $url = $this->aipdbApiEndpoint . $path; 

        // open curl connection
        $ch = curl_init(); 
  
        // set the method and data to send
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            $url .= '?' . http_build_query($data);
        }
         
        // set the url to call
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
      
        // set the AbuseIPDB API Key as a header
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json;',
            'Key: ' . $this->aipdbApiKey,
        ]);
  
      // execute curl call
      $result = curl_exec($ch);
  
      // close connection
      curl_close($ch);
  
      // return response as json object
      return json_decode($result);
    }

    /** 
     * Load and returns decoded Json from given file  
     *
     * @access public
     * @static
	 * @param string    $filePath       The file's full path
	 * @param bool     [$trowError]     Throw error on true or silent process. Default is true
     *  
	 * @return string|null 
     * @throws \Exception
     * @throws \LogicException
     */
    protected static function loadJsonFile(string $filePath, bool $throwError = true)
    {
        // check file exists
        if (!file_exists($filePath) || !is_file($filePath)){
           if ($throwError) {
                throw new \Exception('Config file not found');
           }
           return null;  
        }

        // get and parse content
        $content = file_get_contents($filePath);
        $json    = json_decode(utf8_encode($content));

        // check for errors
        if ($json == null && json_last_error() != JSON_ERROR_NONE){
            if ($throwError) {
                throw new \LogicException(sprintf("Failed to parse config file Error: '%s'", json_last_error_msg()));
            }
        }

        return $json;        
    }
}