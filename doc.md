# Kristuff/AbuseIPDB | Documentation

## 1 Overview

### 1.1 Create an ApiHandler 

The `ApiHandler` class contains all methods to work with the API. 

```.php
public ApiHandler::__construct ( string $apiKey, array $selfIps = [], int $timeout = 0 )
```

You need at least to pass your **api key** as parameter to create an `ApiHandler` instance.  

```.php
<?php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
```

**$selfIps**

If you plan to automate reports with **fail2ban** and send logs lines as message (***matches***), it's recommended to configure a ***whitelist*** of IPs and names (domain name) you want to exclude from message. When you use the [`ApiHandler::report()`](#23-report-a-single-ip-address) method, comments are checked and fields that match to an entry in the list are replaced by **\***. Note that email addresses are already replaced.

To use a whitelist, create an `ApiHandler` instance with your **api key** and a **whitelist** as parameters like this:

```.php
<?php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY", [
    "256.256.256.256",
    "subdomain.example.com",
    "example.com",
]);
```

> Order **does** matter. Enter subdomains first to prevent to replace a part of field only.  

**$timeout**

Since v0.9.14 you can define a **timeout** in third parameter: timeout represents the maximum number of **milliseconds** to allow cURL functions to execute. The timeout will apply to all API request methods. Default is **0** (no timeout).

> If libcurl is built to use the standard system name resolver, that portion of the connect will still use full-second resolution for timeouts with a minimum timeout allowed of one second. 

> When the timeout is reached during any query requests, a `RuntimeException` will be raised.

The following example shows how create an `ApiHandler` instance, and define a timeout of 4 seconds (4000ms).

```.php
<?php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY", [], 4000);
...
```

Since v0.9.15, you can also use the `ApiHandler::setTimeout(int $timeout)` method to overwrite the value passed in constructor, useful when performing multiple queries with same handler but different timeout.

```.php
<?php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$api->setTimeout(4000);
$api->report( ... );
$api->setTimeout(3000);
$api->report( ... );
...
```

### 1.2. ApiHandler response 

All query API methods return an `ApiResponse` object you can use to get the response in desired format.

### 1.2.1 ApiResponse methods 

Methods | Description
---     | ---
`ApiResponse::getPlaintext()`   | Returns the response as plain text, usually a `json` encoded `string`. May return `null`.
`ApiResponse::getObject()`      | Returns the response as decoded `object`. May return `null`.
`ApiResponse::getArray()`       | Returns the response as decoded  `array`. May return `null`. 
`ApiResponse::hasError()`       | Gets whether the decoded response contains errors or not (`bool`).
`ApiResponse::errors()`         | Gets the errors contained in decoded response (`array`). May be empty. 

### 1.2.2 ApiResponse usage 

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->checkIP("127.0.0.1");
    
$plaintext = $response->getPlaintext(); // get the response as plain text (json)
// echo $plaintext;

$object    = $response->getObject();    // get the response as decoded object
// echo $object->data->...;

$array     = $response->getArray();    // get the response as decoded array
// echo $array['data'][...];
```

### 1.3 Error handling 

AbuseIPDB API response (usually a **json** `string`) may contains error(s) (invalid parameter, too many requests, ...). The structure conforms to the [JSON API spec](https://jsonapi.org/format/#errors):

```.json
{
    "errors": [
      {
        "detail": "The max age in days must be between 1 and 365.",
        "status": 422
      }
    ]
}
```

At minimum, API errors always include the `detail` and `status` members and ***may*** provide more members, as per the spec.

The `ApiHandler` class may raise `\Exception` you need to handle when performing API query. To prevent this, you can use the `QuietApiHandler` class that extends `ApiHandler` with exception handling: API query methods won't raise any `\Exception`, instead return an `ApiResponse` with internal errors.    

Those internal errors include the `title` and `detail` members: 

```.json
{
    "errors": [
      {
        "title": "Internal Error"
        "detail": "The max age in days must be between 1 and 365.",
      }
    ]
}
```

#### 1.3.1 Example with ApiHandler 

The following example shows how to handle `\Exception` and errors in `ApiResponse` when performing a query with `ApiHandler` class:

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");

try {
    $query  = $api->check("127.0.0.1");

    // check for errors (comming from API)
    if ($query->hasError()){
        foreach ($query->errors() as $err){
            echo "Error: " . $err->detail . "\n";
        }
        return;
    }

    // ok, do something
    $response   = $query->getObject();
    ...
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
```

#### 1.3.2 Example with QuietApiHandler 

The following example shows how to handle errors in `ApiResponse` when performing a query with `QuietApiHandler` class:

```.php
use Kristuff\AbuseIPDB\QuietApiHandler;
$api = new QuietApiHandler("API_KEY");
$query  = $api->check("127.0.0.1");

// check for errors (comming from API or internal error)
if ($query->hasError()){
    foreach ($query->errors() as $err){
        echo "Error: " . $err->detail . "\n";
    }
    return;
}

// ok, do something
$response   = $query->getObject();
...
```


### 1.4. Report categories 

For [`ApiHandler::report()`](#23-report-a-single-ip-address) and [`ApiHandler::bulkReport()`](#24-bulk-report-ip-addresses) methods, you must define one or more report **category**. Some categories, for example ***ssh*** (22), cannot be used alone. 

With [`ApiHandler::report()`](#23-report-a-single-ip-address) method, a category can be represented by its **shortname** or by its **id**. Using category shortname is an internal feature of [ApiHandler::report()](#23-report-a-single-ip-address) method. When you use the [`ApiHandler::bulkReport()`](#24-bulk-report-ip-addresses) method, you **must** use the **id**.

***Categories list*** must be a comma separated list string. When using [`ApiHandler::bulkReport()`](#24-bulk-report-ip-addresses) method, categories list **requires** double quotes to include commas ("18,22").

Here is the full ***Categories list***:

ShortName        | Id    | Full name          | Can be alone?  
-----------------|-------|--------------------|----------------
 dns-c           |   1   | DNS Compromise     |      true
 dns-p           |   2   | DNS Poisoning      |      true
 fraud-orders    |   3   | Fraud Orders       |      true
 ddos            |   4   | DDoS Attack        |      true
 ftp-bf          |   5   | FTP Brute-Force    |      true
 pingdeath       |   6   | Ping of Death      |      true
 phishing        |   7   | Phishing           |      true
 fraudvoip       |   8   | Fraud VoIP         |      true
 openproxy       |   9   | Open Proxy         |      true
 webspam         |   10  | Web Spam           |      true
 emailspam       |   11  | Email Spam         |      true
 blogspam        |   12  | Blog Spam          |      true
 vpnip           |   13  | VPN IP             |      ***false***     
 scan            |   14  | Port Scan          |      true
 hack            |   15  | Hacking            |      true     
 sql             |   16  | SQL Injection      |      true
 spoof           |   17  | Spoofing           |      true
 brute           |   18  | Brute-Force        |      true
 badbot          |   19  | Bad Web Bot        |      true
 explhost        |   20  | Exploited Host     |      true
 webattack       |   21  | Web App Attack     |      true
 ssh             |   22  | SSH                |      ***false***     
 iot             |   23  | IoT Targeted       |      true

See also: [How to get categories list](#categories_list).


## 2. How to 

>  As said on [abuseipdb](https://www.abuseipdb.com/check/127.0.0.1), ip `127.0.0.1` is a private IP address you can use for check/report api testing. Make sure you **do not** blacklist an internal IP on your server, otherwise you won't have a good day! 

### 2.1 Check a single IP address 

You can check a single IP address using the `ApiHandler::check()` method. This method returns an  `ApiResponse` object you can use to get the response in the desired format (See [ApiHandler response](#12-apihandler-response)).

#### 2.1.1 Check() Overview 

```.php
ApiHandler::check(string $ip, int $maxAge = 30, bool $verbose = false): ApiResponse
```

You must pass a valid **IPv4** or **IPv6** as argument. 

By default, the last reports are checked on last **30** days, you can increase this number to **365** (min is **1**) using the `$maxAgeInDays` parameter. 

Response does not include last reports and country name by default (number or reports and country code only). If you want to get all informations, sets the  `$verbose` parameter to `true`. 

> `verbose` option **increases** request time and response size.

`ApiHandler::check()` method may raises `RuntimeException` or `InvalidArgumentException` when `$maxAgeInDays` is not a numeric value, when `$maxAgeInDays` is less than 1 or greater than 365, or when `$ip` value is not set or empty.

#### 2.1.2 Check() Usage 

Check the ip `127.0.0.1` (default is on last 30 days): 

```.php
use Kristuff\AbuseIPDB\QuietApiHandler;
$api = new QuietApiHandler("API_KEY");
$response = $api->checkIP("127.0.0.1");

echo $response->getPlaintext();
```

<details>
 <summary>Click to see sample output:</summary>

```.json
{
    "data": {
        "ipAddress": "127.0.0.1",
        "isPublic": false,
        "ipVersion": 4,
        "isWhitelisted": false,
        "abuseConfidenceScore": 0,
        "countryCode": null,
        "usageType": "Reserved",
        "isp": "Loopback",
        "domain": null,
        "hostnames": [
            "localhost"
        ],
        "totalReports": 707,
        "numDistinctUsers": 90,
        "lastReportedAt": "2021-01-15T22:03:22+00:00"
    }
}
```
</details>



Check the ip `127.0.0.1` in last **365** days: 

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->checkIP("127.0.0.1", 365);
```

Check the ip `127.0.0.1` in last day (**1** day) and display additional infos with `verbose` parameter: 

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->checkIP("127.0.0.1", 1, true);
```

<details>
 <summary>Click to see sample output:</summary>

```.json
{
    "data": {
        "ipAddress": "140.143.187.54",
        "isPublic": true,
        "ipVersion": 4,
        "isWhitelisted": false,
        "abuseConfidenceScore": 100,
        "countryCode": "CN",
        "usageType": "Data Center\/Web Hosting\/Transit",
        "isp": "Tencent Cloud Computing (Beijing) Co. Ltd.",
        "domain": "tencent.com",
        "hostnames": [],
        "countryName": "China",
        "totalReports": 7,
        "numDistinctUsers": 4,
        "lastReportedAt": "2021-01-15T14:04:48+00:00",
        "reports": [
            {
                "reportedAt": "2021-01-15T14:04:48+00:00",
                "comment": "Jan 15 13:57:26 ip-172-31-33-4 sshd\\[8061\\]: Invalid user admin from 140.143.187.54\\\nJan 15 13:57:28 ip-172-31-33-4 sshd\\[8061\\]: Failed password for invalid user admin from 140.143.187.54 port 52838 ssh2\\\nJan 15 14:02:59 ip-172-31-33-4 sshd\\[8087\\]: Invalid user ftpuser from 140.143.187.54\\\nJan 15 14:03:01 ip-172-31-33-4 sshd\\[8087\\]: Failed password for invalid user ftpuser from 140.143.187.54 port 52960 ssh2\\\nJan 15 14:04:27 ip-172-31-33-4 sshd\\[8092\\]: Invalid user soporte from 140.143.187.54\\",
                "categories": [
                    18,
                    22
                ],
                "reporterId": 26878,
                "reporterCountryCode": "US",
                "reporterCountryName": "United States of America"
            },
            {
                "reportedAt": "2021-01-15T12:56:53+00:00",
                "comment": "Jan 15 12:53:47 ip-172-31-33-4 sshd\\[7393\\]: Invalid user meteor from 140.143.187.54\\\nJan 15 12:53:49 ip-172-31-33-4 sshd\\[7393\\]: Failed password for invalid user meteor from 140.143.187.54 port 50620 ssh2\\\nJan 15 12:55:24 ip-172-31-33-4 sshd\\[7402\\]: Failed password for root from 140.143.187.54 port 36556 ssh2\\\nJan 15 12:56:50 ip-172-31-33-4 sshd\\[7417\\]: Invalid user web from 140.143.187.54\\\nJan 15 12:56:52 ip-172-31-33-4 sshd\\[7417\\]: Failed password for invalid user web from 140.143.187.54 port 50724 ssh2\\",
                "categories": [
                    18,
                    22
                ],
                "reporterId": 26878,
                "reporterCountryCode": "US",
                "reporterCountryName": "United States of America"
            },
            ...
            {
                "reportedAt": "2021-01-15T09:44:27+00:00",
                "comment": "Jan 15 09:44:24 ajax sshd[8710]: pam_unix(sshd:auth): authentication failure; logname= uid=0 euid=0 tty=ssh ruser= rhost=140.143.187.54 \nJan 15 09:44:27 ajax sshd[8710]: Failed password for invalid user ruser from 140.143.187.54 port 42204 ssh2",
                "categories": [
                    18,
                    22
                ],
                "reporterId": 6089,
                "reporterCountryCode": "FR",
                "reporterCountryName": "France"
            }
        ]
    }
}
```

</details>



### 2.2 Check an IP block 

You can check an IP block address using the `ApiHandlder::checkBlock()` method. This method returns an  `ApiResponse` object you can use to get the response in the desired format (See [ApiHandler response](#12-apihandler-response)). 


#### 2.1.1 CheckBlock() Overview 

```.php
checkBlock(string $network, int $maxAge = 30): ApiResponse
```

The `checkBlock()` method accepts a subnet (v4 or v6) denoted with CIDR notation as first argument.

By default, the last reports are checked on last **30** days, you can increase this number to **365** (min is **1**) using the `$maxAgeInDays` parameter. 

`ApiHandler::checkBlock()` method may raises `RuntimeException` or `InvalidArgumentException` when `$maxAgeInDays` is not a numeric value, when `$maxAgeInDays` is less than 1 or greater than 365, or when `$network` value was not set.


#### 2.2.2 CheckBlock Limits 

Due to the depth & breath of the these searches, the range of the parameters is capped by plan tier.

For most use cases, /24 is enough to an entire network. Many autonomous systems will sometimes have blocks of /20. Some of the largest autonomous systems will have blocks of /18 or /17.

Field           |	Standard 	| Basic Subscription    |	Premium Subscription
---             |   ---         | ---                   |   ---                    
network         |	Up to /24 	| Up to /20             |	Up to /16
maxAgeInDays    |   Up to 30 	| Up to 60 	            |   Up to 365

> Exceeding a parameter limit will raise an error (402 Payment Required).


#### 2.2.3 CheckBlock Usage 
 
Check the block ip `127.0.0.1/24` (default is on last 30 days): 

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->checkBlock("127.0.0.1/24");
```

Check the block ip `127.0.0.1/24` in last 15 days: 

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->checkBlock("127.0.0.1/24", 15);
```

<details>
 <summary>Click to see sample output:</summary>

```.json
{
    "data": {
      "networkAddress": "127.0.0.0",
      "netmask": "255.255.255.0",
      "minAddress": "127.0.0.1",
      "maxAddress": "127.0.0.254",
      "numPossibleHosts": 254,
      "addressSpaceDesc": "Loopback",
      "reportedAddress": [
        {
          "ipAddress": "127.0.0.1",
          "numReports": 631,
          "mostRecentReport": "2019-03-21T16:35:16+00:00",
          "abuseConfidenceScore": 0,
          "countryCode": null
        },
        {
          "ipAddress": "127.0.0.2",
          "numReports": 16,
          "mostRecentReport": "2019-03-12T20:31:17+00:00",
          "abuseConfidenceScore": 0,
          "countryCode": null
        },
        ...
      ]
    }
}
```

</details>




### 2.3 Report a single IP address 

You can reports a specific IP address using the `ApiHandler::report()` method. This method returns an  `ApiResponse` object you can use to get the response in the desired format (See [ApiHandler response](#12-apihandler-response)).

#### 2.3.1 Report Overview 

```.php
ApiHandler::report(string $ip, string $categories, string $message): ApiResponse
```

The `ApiHandler::report()` method accepts a single IP address (v4 or v6) as first parameter. 

You must also set :

- valid **report category(ies)** (`string`): comma separated list (See: [***Report categories***](#categories))
- a **message** (`string`).


#### 2.3.2 Reports Usage

Report the ip `127.0.0.1` for `ssh` (22) and `brute` (18) with message `ssh brute force attack` using categories **shortname** or **id**: 

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");

try {
    $query      = $api->report("127.0.0.1", "ssh,brute", "ssh brute force attack");
    
    // you could use cat id:
    // $query      = $api->report("127.0.0.1", "22,18", "ssh brute force attack");

    $response   = $query->getObject();

    // check for errors
    if (isset($response) && isset($response->errors)){
        foreach ($response->errors as $err){
            echo "Error: " . $err->detail . "\n";
        }
        exit;
    }

    echo sprintf(
        "IP: %s successfully reported (confidence score: %d) \n", 
        $response->data->ipAddress, 
        $response->data->abuseConfidenceScore
    ); 
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
```

<details>
 <summary>Click to see sample output:</summary>

```.json
{
    "data": {
      "ipAddress": "127.0.0.1",
      "abuseConfidenceScore": 52
    }
}
```
</details>


### 2.4 Bulk report IP addresses 

Instead of reporting IPs individually, you may compile a CSV of reports. This helps reduce bandwidth on both sides. 

> Note: The abuse confidence score of a IP reported this way is not immediately calculated.

You can report many IP by sending a csv file using the `ApiHandler::bulkReport()`method. This method returns an  `ApiResponse` object you can use to get the response in the desired format (See [ApiHandler response](#12-apihandler-response)).


#### 2.4.1 BulkReport() Overview 

```.php
ApiHandler::bulkReport(string $filePath): ApiResponse
```

This commands takes the file name/path as unique parameter. The CSV file must be under **2 MB** and less than or equal to **10,000 lines**, including the headings (see [CSV Format](#24-bulk-report-ip-addresses_csv)).

#### 2.4.2 CSV Format 

> Headings ***required***, any order.

- `IP` — A valid IPv4 or IPv6 IP address.
- `Categories` — At least one category ID. Comma separated for multiple categories. See: [Report Categories](#categories). Categories must be enclosed if there is more than one.
- `ReportDate` — Date and time of the attack or earliest observance of attack. Any format that `strtotime()` can process is permitted. However, it's strongly recommend a timezoned format such ISO 8601 e.g. 2017-09-08T10:00:37-04:00. A time lacking a timezone is assumed to be in UTC.
- `Comment` — A description of the attack. Truncated after **1,024** characters (bytes). The Comment must be enclosed in double quotes (") to include commas (,) and new line separators (\n, \r, \r\n). A blacklash (\) is needed to escape the enclosure. It does not escape itself.

Here is an example of valid content:

```.apacheconf
IP,Categories,ReportDate,Comment
89.205.125.160,"18,22",2018-12-18T10:00:37-04:00,"Failed password for invalid user odoo from 89.205.125.160 port 39121 ssh2"
123.183.209.136,"18,22",2018-12-18T11:25:11-04:00,"Did not receive identification string from 123.183.209.136 port 57192"
197.156.104.113,"14,15,18,11,10,21",2018-12-18T16:10:58+04:00,"[SMB remote code execution attempt: port tcp/445]
in blocklist.de:'listed [pop3]'
in SpamCop:'listed'
in sorbs:'listed [web], [spam]'
in Unsubscore:'listed'
*(RWIN=8192)(04:10)"
```
#### 2.4.3 BulkReport() Usage 

Suppose the csv file named `reports.csv`, located in the current directory, with the following content:

```.apacheconf
IP,Categories,ReportDate,Comment
127.0.0.1,"18,22",2021-01-08T10:00:37-04:00,"test api"
127.0.0.1,"18,22",2021-01-08T10:00:37-04:00,"test api DUPLICATE IP"
127.0.0.2,"18,22",2021-01-08T10:00:37-04:00,"test api"
127.0.0.3,"18,22",2021-01-08T10:00:37-04:00,"test api"
127.0.0.WRONGIP,"18,22",2021-01-08T10:00:37-04:00,"test api with WRONG IP"
127.0.0.100,"777,999",2021-01-08T10:00:37-04:00,"test api with WRONG categorgies"
```

> The file contains 3 **error** lines for testing (duplicate IP, wrong IP and wrong categories).

Send the csv file `reports.csv`:

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->bulkReport("reports.csv"); 

echo $response->getObject();
```

<details>
 <summary>Click to see sample output:</summary>

```.json
{
    "data": {
        "savedReports": 3,
        "invalidReports": [
            {
                "error": "Duplicate IP",
                "input": "127.0.0.1",
                "rowNumber": 3
            },
            {
                "error": "Invalid IP",
                "input": "127.0.0.WRONGIP",
                "rowNumber": 6
            },
            {
                "error": "Invalid Category",
                "input": "777,999",
                "rowNumber": 7
            }
        ]
    }
}
```
</details>


### 2.5 Remove own reports for an IP address 

You can delete reports for a specific address from **YOUR** account using the `ApiHandler::clearAddress()` method. It cannot delete another user's reports. This method returns an  `ApiResponse` object you can use to get the response in the desired format (See [ApiHandler response](#12-apihandler-response)).


#### 2.5.1 ClearAddress() Overview 

```.php
ApiHandler::clear(string $ip): ApiResponse
```

The `ApiHandler::clearAddress()` method accepts a single IP address (v4 or v6) as parameter.

The only property it returns is the number of reports deleted from your account.

#### 2.5.2 ClearAddress() Usage {#clear_usage}

Remove own reports for IP `127.0.0.1`:

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->clearAddress("127.0.0.1"); 

// get number of deleted reports
$numberReportDeleted = $response->getObject()->data->numReportsDeleted;

echo $response->getPlaintext();
```

<details>
 <summary>Click to see sample output:</summary>

```.json
{
    "data": {
        "numReportsDeleted": 2
    }
}
```

</details>


### 2.6 Download IP blacklist  

You can download a blacklist using the `ApiHandler::getBlacklist()` method. 

> The blacklist is the culmination of all of the valiant reporting by AbuseIPDB users. It's a list of the most reported IP addresses.

The blacklist response for a given query is **cached** for 1, 6, or 24 hours depending on your plan (see table below). 

Plan                    |   Hours Cached
----                    |   ----------
Individual              |   24
Basic Subscription      |   6
Premium Subscription    |   1

#### 2.6.1 Blacklist() Overview {#blacklist_overview}

```.php
ApiHandler::getBlacklist(int $limit = 10000, bool $plainText = false, int $confidenceMinimum = 100): ApiResponse
```

#### 2.6.2 Blacklist Limit 

To conserve bandwidth, the number of IP addresses included in the list is capped to **10,000**. Subscribers, both basic and premium, can overcome this limit. All users can set it between **1** and **10,000** using the limit parameter.

> If you are a subscriber and want to the full list, set the limit parameter to an absurd number like in the example (9,999,999). However, this large of a request may take a while, and firewall software such as csf may have trouble importing rules for over 10,000 IPs.

The **default** limit currently defined is **10,000**, you can change limit in your query with `$limit` parameter.


#### 2.6.3 Plaintext Blacklist

You can print the blacklist like any reports or just want to receive the IP list in **plain text** like this:

```.apacheconf
5.188.10.179
185.222.209.14
95.70.0.46
191.96.249.183
115.238.245.8
122.226.181.164
122.226.181.167
```

If you prefer a simple newline-separated plaintext response, set the `$plaintext` parameter to `true`.

#### 2.6.4 Confidence Score 

The **default** confidence score is **100**, you can change mimimum condidence score in your command with `$confidenceMinimum` parameter. Score must be between **25** to **100**. The `score` is a subscriber feature and is not honored otherwise.

> **The abuseConfidenceScore parameter is a subscriber feature**. This is because custom blacklists take more juice to generate on-demand. Should your subscriber status lapse, an error response will not be thrown. Rather, the response will degrade gracefully to the simple blacklist. This works nicely with existing firewall software. 

#### 2.6.5 Blacklist() Usage 

Get a blacklist of 10 items (decoded `object`):

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->blacklist(10)->getObject();

$date = $response->meta->generatedAt;

```

<details>
 <summary>Click to see sample output:</summary>

```.json
{
  "meta": {
    "generatedAt": "2020-09-24T19:54:11+00:00"
  },
  "data": [
    {
      "ipAddress": "5.188.10.179",
      "abuseConfidenceScore": 100,
      "lastReportedAt": "2020-09-24T19:17:02+00:00"
    },
    {
      "ipAddress": "185.222.209.14",
      "abuseConfidenceScore": 100,
      "lastReportedAt": "2020-09-24T19:17:02+00:00"
    },
    {
      "ipAddress": "191.96.249.183",
      "abuseConfidenceScore": 100,
      "lastReportedAt": "2020-09-24T19:17:01+00:00"
    },
    ...
  ]
}
```
</details>

Get a blacklist of **5** items in **plain text**:

```.php
use Kristuff\AbuseIPDB\ApiHandler;
$api = new ApiHandler("API_KEY");
$response = $api->blacklist(5, true); 

echo $response->getPlaintext();
```

<details>
 <summary>Click to see sample output:</summary>

```.apacheconf
81.71.138.241
210.10.208.238
178.128.217.58
106.52.231.46
31.184.199.114
```

</details>


## 3 Misc {#misc}

### 3.1 Get report categories list

You can can get the report categories list using `ApiHandler::getCategories()` method. It returns an array. Each array entry is an array containing shortname, id, long name. Last parameter, `bool` value, is false when the category cant't be use alone.  

### 3.2 Clean a single message 

When reporting IP address, you must send a message that describe the attack. The message can be hard coded or coming from logs. In that case, it could contain sensitive data like own IP address, own domain names or email address. 

The `ApiHandler::cleanMessage(string $message)` method:

- removes all email addresses; 
- removes and IPs and names defined in *whitelist* (see [Create an ApiHandler](#apihandler)).
- strips message to 1024 chars.

> The `cleanMessage()` method is already called when you use the `report()` method. You can use the `cleanMessage()` method when preparing a `csv` file of reports, for example.
