# Kristuff\AbuseIPDB
> A wrapper for AbuseIPDB API v2

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kristuff/abuseipdb/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kristuff/abuseipdb/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/kristuff/abuseipdb/badges/build.png?b=master)](https://scrutinizer-ci.com/g/kristuff/abuseipdb/build-status/master)
[![Latest Stable Version](https://poser.pugx.org/kristuff/abuseipdb/v/stable)](https://packagist.org/packages/kristuff/abuseipdb)
[![License](https://poser.pugx.org/kristuff/abuseipdb/license)](https://packagist.org/packages/kristuff/abuseipdb)

***see also [kristuff/abuseipdb-cli](https://github.com/kristuff/abuseipdb-cli) for the `CLI` version***

Features
--------
- Single IP check request **✓** 
- IP block check request **✓** 
- Blacklist request **✓** 
- Single IP report request **✓** 
- Bulk report request (send `csv` file) **✓** 
- Clear IP address request (remove your own reports) **✓**
- Auto cleaning report comments from sensitive data (email, custom ip/domain names list) **✓** 
- Define timeout for cURL internal requests **✓**

Requirements
------------    
- PHP >= 7.1
- PHP's cURL  
- A valid [abuseipdb.com](https://abuseipdb.com) account with an API key

Install
-------

Deploy with composer:

```json
...
"require": {
    "kristuff/abuseipdb": "^1.1-stable"
},
```

More infos
-----

- [Project website](https://kristuff.fr/projects/abuseipdb)
- [Api documentation](https://kristuff.fr/projects/abuseipdb/doc)
- CLI version: [github](https://github.com/kristuff/abuseipdb-cli) | [website](https://kristuff.fr/projects/abuseipdbcli)


License
-------

The MIT License (MIT)

Copyright (c) 2020-2022 Kristuff

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
