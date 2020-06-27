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
 * @version    0.9.0
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
     * @param array   $myIps      The Ips/domain name you dont want to display in report messages
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
            'selfIps' => $this->selfIps,
            
            // TODO  default report cat 
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
     * @throws \InvalidArgumentException                        If the given file does not exist
     * @throws \Kristuff\AbuseIPDB\InvalidPermissionException   If the given file is not readable 
     */
    public static function fromConfigFile(string $configPath)
    {

        // check file exists
        if (!file_exists($configPath) || !is_file($configPath)){
            throw new \InvalidArgumentException('The file [' . $configPath . '] does not exist.');
        }

        // check file is readable
        if (!is_readable($configPath)){
            throw new InvalidPermissionException('The file [' . $configPath . '] is not readable.');
        }

        // todo check file exist
        $keyConfig = self::loadJsonFile($configPath);
        $selfIps = [];
        
        // Look for other optional config files in the same directory 
        $selfIpsConfigPath = pathinfo($configPath, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'self_ips.json';
        if (file_exists($selfIpsConfigPath)){
            $selfIps = self::loadJsonFile($selfIpsConfigPath)->self_ips;
        }

        $app = new ApiManager($keyConfig->api_key, $keyConfig->user_id, $selfIps);
        
        return $app;
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
     * @param string    $categories     The report categories
     * @param string    $message        The report message
     * @param bool      $returnArray    True to return an indexed array instead of an object. Default is false. 
     *
     * @return object|array
     * @throws \InvalidArgumentException
     */
    public function report(string $ip = '', string $categories = '', string $message = '', bool $returnArray = false)
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

        // validates categories, clean message 
        $cats = $this->validateCategories($categories);
        $msg = $this->cleanMessage($message);

        // report AbuseIPDB request
        return $this->apiRequest('report', [
            'ip' => $ip,
            'categories' => $cats,
            'comment' => $msg
            ],
            'POST', $returnArray
        );
    }

    /**
     * Check if the category(ies) given is/are valid
     * Check for shortname or id, and categories that can't be used alone 
     * 
     * @access protected
     * @param array $categories       The report categories list
     *
     * @return string               Formatted string id list ('18,2,3...')
     * @throws \InvalidArgumentException
     */
    protected function validateCategories(string $categories)
    {
        // the return categories string
        $catsString = ''; 

        // used when cat that can't be used alone
        $needAnother = null;

        // parse given categories
        $cats = explode(',', $categories);

        foreach ($cats as $cat) {

            // get index on our array of categories
            $catIndex    = is_numeric($cat) ? $this->getCategoryIndex($cat, 1) : $this->getCategoryIndex($cat, 0);

            // check if found
            if ($catIndex === false ){
                throw new \InvalidArgumentException('Invalid report category was given : ['. $cat .  ']');
            }

            // get Id
            $catId = $this->aipdbApiCategories[$catIndex][1];

            // need another ?
            if ($needAnother !== false){

                // is a standalone cat ?
                if ($this->aipdbApiCategories[$catIndex][3] === false) {
                    $needAnother = true;

                } else {
                    // ok, continue with other at least one given
                    // no need to reperform this check
                    $needAnother = false;
                }
            }

            // set or add to cats list 
            $catsString = ($catsString === '') ? $catId : $catsString .','.$catId;
        }

        if ($needAnother !== false){
            throw new \InvalidArgumentException('Invalid report category paremeter given: some categories can\'t be used alone');
        }

        // if here that ok
        return $catsString;
    }

    /**
     * Perform a 'check' api request
     * 
     * @access public
     * @param string    $ip             The ip to check
     * @param string    $maxAge         Max age in days
     * @param bool      $verbose        True to get the full response. Default is false
     * @param bool      $returnArray    True to return an indexed array instead of an object. Default is false. 
     * 
     * @return object|array
     * @throws \InvalidArgumentException    When maxAge is not a numeric value, when maxAge is less than 1 or 
     *                                      greater than 365, or when ip value was not set. 
     */
    public function check(string $ip = null, string $maxAge = '30', bool $verbose = false, bool $returnArray = false)
    {
        
        if (!is_numeric($maxAge)){
            throw new \InvalidArgumentException('maxAge must be a numeric value (' . $maxAge . ' was given)');
        }
        $maxAge = intval($maxAge);

        // max age must less or equal to 365
        if ($maxAge > 365 || $maxAge < 1){
            throw new \InvalidArgumentException('maxAge must be at least 1 and less than 365 (' . $maxAge . ' was given)');
        }

        //ip must be set
        if (empty($ip)){
            throw new \InvalidArgumentException('ip argument must be set (null given)');
        }

        // minimal data
        $data = [
            'ipAddress'     => $ip, 
            'maxAgeInDays'  => $maxAge,  
        ];

        // option
        if ($verbose){
           $data['verbose'] = true;
        }

        // check AbuseIPDB request
        return $this->apiRequest('check', $data, 'GET', $returnArray) ;
    }

    /**
     * Perform a cURL request       
     * 
     * @access protected
     * @param string    $path           The api end path 
     * @param array     $data           The request data 
     * @param string    $method         The request method. Default is 'GET' 
     * @param bool      $returnArray    True to return an indexed array instead of an object. Default is false. 
     * 
     * @return object|array
     */
    protected function apiRequest(string $path, array $data, string $method = 'GET', bool $returnArray = false) 
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
  
      // return response as object / array
      return json_decode($result, $returnArray);
    }

    /** 
     * Clean message in case it comes from fail2ban <matches>
     * https://wiki.shaunc.com/wikka.php?wakka=ReportingToAbuseIPDBWithFail2Ban
     * 
     * @access public
     * @param string      $message           The original message 
     *  
	 * @return string
     */
    protected function cleanMessage(string $message)
    {
        // Remove backslashes and sensitive information from the report
        $message = str_replace('\\', '', $message);

        // Remove self ips
        foreach ($this->selfIps as $ip){
            $message = str_replace($ip, '[MUNGED]', $message);
        } 

        // If we're reporting spam, further munge any email addresses in the report
        $emailPattern = "/[^@\s]*@[^@\s]*\.[^@\s]*/";
        $emailRemplacement = "[MUNGED]";
        preg_replace($emailPattern, $emailRemplacement, $message);
        
        return $message;
    }

    /** 
     * Load and returns decoded Json from given file  
     *
     * @access public
     * @static
	 * @param string    $filePath       The file's full path
	 * @param bool      $trowError      Throw error on true or silent process. Default is true
     *  
	 * @return object|null 
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