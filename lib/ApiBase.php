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
 * @version    0.9.14
 * @copyright  2020-2021 Kristuff
 */

namespace Kristuff\AbuseIPDB;

/**
 * Class ApiBase
 * 
 * Abstract base class for ApiHanlder
 * Contains main hard coded api settings
 */
abstract class ApiBase
{
    /**
     * AbuseIPDB API v2 Endpoint
     * @var string  
     */
    protected $aipdbApiEndpoint = 'https://api.abuseipdb.com/api/v2/'; 

    /**
     * AbuseIPDB API key
     *  
     * @access protected
     * @var string $aipdbApiKey  
     */
    protected $aipdbApiKey = null; 
  
    /**
     * AbuseIPDB API v2 categories
     * shortname, id (string), long name
     * last parameter is false when the category can't be used alone
     * 
     * @static
     * @var array
     */
    protected static $aipdbApiCategories = [
        
        // Altering DNS records resulting in improper redirection.        
        ['dns-c'           , '1', 'DNS Compromise', true],    
        
        // Falsifying domain server cache (cache poisoning).
        ['dns-p'           , '2', 'DNS Poisoning', true],     
        
        // Fraudulent orders.
        ['fraud-orders'    , '3', 'Fraud Orders', true],      

        // Participating in distributed denial-of-service (usually part of botnet).        
        ['ddos'            , '4', 'DDoS Attack', true],       
        
        // 
        ['ftp-bf'          , '5', 'FTP Brute-Force', true],   
        
        // Oversized IP packet.
        ['pingdeath'       , '6', 'Ping of Death', true],     

        // Phishing websites and/or email.
        ['phishing'        , '7', 'Phishing', true],          
        
        //
        ['fraudvoip'       , '8', 'Fraud VoIP', true],        

        // Open proxy, open relay, or Tor exit node.
        ['openproxy'       , '9', 'Open Proxy', true],        

         // Comment/forum spam, HTTP referer spam, or other CMS spam.
         ['webspam'         , '10', 'Web Spam', true],        

        // Spam email content, infected attachments, and phishing emails. Note: Limit comments to only relevent
        // information (instead of log dumps) and be sure to remove PII if you want to remain anonymous.
        ['emailspam'       , '11', 'Email Spam', true],                                                   
             
        // CMS blog comment spam.
        ['blogspam'        , '12', 'Blog Spam', true],      
        
        // Conjunctive category.
        ['vpnip'           , '13', 'VPN IP', false], // to check alone ??           

        // Scanning for open ports and vulnerable services.
        ['scan'            , '14', 'Port Scan', true],        
       
        // 
        ['hack'            , '15', 'Hacking', true],           

        // Attempts at SQL injection.
        ['sql'             , '16', 'SQL Injection', true],     
        
        // Email sender spoofing.
        ['spoof'           , '17', 'Spoofing', true],         

        // Credential brute-force attacks on webpage logins and services like SSH, FTP, SIP, SMTP, RDP, etc. 
        // This category is seperate from DDoS attacks.
        ['brute'           , '18', 'Brute-Force', true],     

        // Webpage scraping (for email addresses, content, etc) and crawlers that do not honor robots.txt.                                  
        // Excessive requests and user agent spoofing can also be reported here.                        
        ['badbot'          , '19', 'Bad Web Bot', true],      
                                                         
        // Host is likely infected with malware and being used for other attacks or to host malicious content. 
        // The host owner may not be aware of the compromise. This category is often used in combination 
        // with other attack categories.
        ['explhost'        , '20', 'Exploited Host', true],
        
        // Attempts to probe for or exploit installed web applications such as a CMS 
        // like WordPress/Drupal, e-commerce solutions, forum software, phpMyAdmin and 
        // various other software plugins/solutions.                                                         
        ['webattack'       , '21', 'Web App Attack', true ],   
        
        // Secure Shell (SSH) abuse. Use this category in combination 
        // with more specific categories.
        ['ssh'             , '22', 'SSH', false],              

        // Abuse was targeted at an "Internet of Things" type device. Include 
        // information about what type of device was targeted in the comments.         
        ['iot'             , '23', 'IoT Targeted', true],     
    ];

    /**
     * Get the list of report categories
     * 
     * @access public
     * @static
     *  
     * @return array
     */
    public static function getCategories(): array
    {
        return self::$aipdbApiCategories;
    }

    /**
     * Get the category id corresponding to given name
     * 
     * @access public
     * @static
     * @param string $categoryName    The report category name
     * 
     * @return string|bool            The category id in string format if found, otherwise false
     */
    public static function getCategoryIdByName(string $categoryName)
    {
        foreach (self::$aipdbApiCategories as $cat){
            if ($cat[0] === $categoryName) {
                return $cat[1];
            }
         }

        // not found
        return false;
    }

    /**
     * Get the category name corresponding to given id
     * 
     * @access public
     * @static
     * @param string    $categoryId   The report category id
     * 
     * @return string|bool            The category name if found, otherwise false
     */
    public static function getCategoryNameById(string $categoryId)
    {
        foreach (self::$aipdbApiCategories as $cat){
           if ($cat[1] === $categoryId) {
               return $cat[0];
           }
        }

        // not found
        return false;
    }

    /**
     * Get the index of category corresponding to given value
     * 
     * @access protected
     * @static
     * @param string        $value          The report category id or name
     * @param string        $index          The index in value array 
     * 
     * @return int|bool     The category index if found, otherwise false
     */
    protected static function getCategoryIndex(string $value, int $index)
    {
        $i = 0;
        foreach (self::$aipdbApiCategories as $cat){
            if ($cat[$index] === $value) {
                return $i;
            }
            $i++;
         }

        // not found
        return false;
    }

    /**
     * Check if the category(ies) given is/are valid
     * Check for shortname or id, and categories that can't be used alone 
     * 
     * @access protected
     * @param array         $categories       The report categories list
     *
     * @return string       Formatted string id list ('18,2,3...')
     * @throws \InvalidArgumentException
     */
    protected function validateReportCategories(string $categories)
    {
        // the return categories string
        $catsString = ''; 

        // used when cat that can't be used alone
        $needAnother = null;

        // parse given categories
        $cats = explode(',', $categories);

        foreach ($cats as $cat) {

            // get index on our array of categories
            $catIndex    = is_numeric($cat) ? self::getCategoryIndex($cat, 1) : self::getCategoryIndex($cat, 0);

            // check if found
            if ($catIndex === false ){
                throw new \InvalidArgumentException('Invalid report category was given.');
            }

            // get Id
            $catId = self::$aipdbApiCategories[$catIndex][1];

            // need another ?
            if ($needAnother !== false){

                // is a standalone cat ?
                if (self::$aipdbApiCategories[$catIndex][3] === false) {
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
            throw new \InvalidArgumentException('Invalid report category parameter given: this category can\'t be used alone.');
        }

        // if here that ok
        return $catsString;
    }
}