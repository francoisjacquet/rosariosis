PHP malware scanner
===================

Traversing directories for files with php extensions and testing files against text or regexp rules, the rules based on self gathered samples and publicly available malwares/webshells.
The goal is to find infected files and fight against kiddies, because to easy to bypass rules.

How to install?
---

Simply clone the repository or with composer install globally `composer global require scr34m/php-malware-scanner`.

How to use?
-----------

```
Usage: php scan.php -d <directory>
    -h                   --help               Show this help message
    -d <directory>       --directory          Directory for searching
    -e <file extension>  --extension          File Extension to Scan
    -E                   --scan-everything    Scan all files, with or without extensions
    -i <directory|file>  --ignore             Directory of file to ignore
    -a                   --all-output         Enables --checksum,--comment,--pattern,--time
    -b                   --base64             Scan for base64 encoded PHP keywords
    -m                   --checksum           Display MD5 Hash/Checksum of file
    -c                   --comment            Display comments for matched patterns
    -x                   --extra-check        Adds GoogleBot and htaccess to Scan List
    -l                   --follow-symlink     Follow symlinked directories
    -k                   --hide-ok            Hide results with 'OK' status
    -r                   --hide-err           Hide results with 'ER' status
    -w                   --hide-whitelist     Hide results with 'WL' status
    -n                   --no-color           Disable color mode
    -s                   --no-stop            Continue scanning file after first hit
    -p                   --pattern            Show Patterns next to the file name
    -t                   --time               Show time of last file change
    -L                   --line-number        Display matching pattern line number in file
    -o                   --output-format      Custom defined output format
    -j <version>         --wordpress-version  Version of wordpress to get md5 signatures
                         --combined-whitelist Combined whitelist
                         --custom-whitelist   Loads whitelist from specified file and merge with existing
                         --disable-stats      Disable statistics output
```

Ignore argument could be used multiple times and accept glob style matching ex.: "`cache*`", "`??-cache.php`" or "`/cache`" etc.

Extension argument defaults to "`.php`" and also can be used multiple times too.

* `--base64` is an alternative scan mode which ignores the main pattern files and uses a large list of php keywords and functions that have been converted to base64.  Slower and prone to false positives, but gives additional base64 scanning coverage.  These pattern files are located in base64_patterns and were derived from php 7 keywords and functions.  Not many PHP extensions are included.
* `--comment` flag will display the last comment to appear in the pattern file before the matched pattern, so documenting the pattern files is important.

Output formatting
-----------------

Default output depending on the specified parameters, but the full format is "%S %T %M # {%F} %C %P # %L" and using ANSI coloring too.

Possible variables are:

* `%S` - matching indicator, possible values are OK, ER, WL
* `%T` - file change time
* `%M` - file md5 hash value
* `%F` - file with path
* `%P` - pattern
* `%C` - pattern comment
* `%L` - matching pattern line number

Patterns
--------

There are three main pattern files the cover different types of pattern matching.  There is one pattern per line.  All lines where the very first character is a "`#`" is considered a comment and not used as a pattern.  Whitespace in the pattern files is not used.

* `patterns_raw.txt` - Raw string matching
* `patterns-iraw.txt` - Case insensitive raw string matching
* `patterns-re.txt`- Regular expression matching.

Whitelisting
------------

See [whitelist.txt](https://github.com/scr34m/php-malware-scanner/blob/master/whitelist.txt) file for a predefined MD5 hash list. Only the first 32 characters are used, rest of the line ignored so feel free to leave a comment.

Wordpress md5 sum whitelisting
-------------
You can automatically add md5sum from wordpress core files by specifing version as argument to --wordpress-version or -j. 
Example:
```
scan -d . -j 4.9.2
```
That will automatically get md5sums from wordpress api (https://api.wordpress.org/core/checksums/1.0/?version=x.x.x) and add it to whitelist. To check your version simply check wp-includes/version.php file of your wordpress

Combined whitelist
---

This list is a pre generated database for opensource projects more information at https://scr34m.github.io/php-malware-scanner/ site.
The scanner check for database hash validity and only download if it is different and of course when argument used.

Tools
-----

**text2base64.py**

Takes a plaintext string as input and returns 3 base64 string equivalents.
Python script that needs to be executed from the terminal to be used.

It is worth noting that the presence of one of the three output strings in a block of text does not 100% guarantee that the string was
present in the original code.  It is guaranteed that IF the subject string was present in the original code, then one of the three
output strings will be present in the base64 version.

```
$ python tools/text2base64.py 'base64_decode'  
YmFzZTY0X2RlY29kZ  
Jhc2U2NF9kZWNvZG  
iYXNlNjRfZGVjb2Rl
```  
  
An example: The presence of 'YmFzZTY0X2RlY29kZ' does not guarantee that 'base64_decode' is in the plain text code.   
It is guaranteed that IF 'base64_decode' was present in the plain text code, then one of these three base64 strings WILL be present.
The presence of 'YmFzZTY0X2RlY29kZ' in a block of code may be because 'ase64_decod' was in the original code.  
ote the missing edge characters which is due to bit misalignment and character bleed.

Using as library
----------------

The scan.php perform a check, that it's called by commandline or not, so to use as library use different directory than scan.php it self.
 
```php
<?php

require_once '../scan.php';

$scan = new MalwareScanner();
$scan->setFlagHideWhitelist(true);
$scan->setFlagHideOk(true);
$scan->run('../samples/test');
```

Resources
---------

* [PHPScanner](https://github.com/PHPScannr/phpFUS)
* [PMF - PHP Malware Finder](https://github.com/nbs-system/php-malware-finder)
* [check regexp online](http://www.phpliveregex.com)
* [malware samples 1](https://github.com/nbs-system/php-malware-finder/tree/master/php-malware-finder/samples)
* [malware samples 2](https://github.com/r4v/php-exploits)
* [malware samples 3](https://github.com/nikicat/web-malware-collection)
* [malware samples 4](https://github.com/antimalware/manul/tree/master/src/scanner/static/signatures)

Licensing
---------

PHP malware scanner is [licensed](https://github.com/scr34m/php-malware-scanner/blob/master/LICENSE.txt) under the GNU General Public License v3.

Docker Usage
-----------

You can also run the scanner using Docker:

1. Build the image:
```bash
docker build -t php-malware-scanner .
```

2. Scan a directory:
```bash
docker run -v /path/to/scan:/code php-malware-scanner -d /code
```

For example, to scan a WordPress installation:
```bash
docker run -v /var/www/html:/code php-malware-scanner -d /code -j 6.4.1
```

Common usage with flags:
```bash
# Show only infected files (hide OK status)
docker run -v /path/to/scan:/code php-malware-scanner -d /code -k

# Show comments for matched patterns
docker run -v /path/to/scan:/code php-malware-scanner -d /code -c

# Show MD5 hashes and continue after first match
docker run -v /path/to/scan:/code php-malware-scanner -d /code -m -s
```

The `/code` directory inside the container is where your files will be mounted for scanning.
