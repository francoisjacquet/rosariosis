# INSTALLATION DIRECTIONS

## RosarioSIS Student Information System

Version 2.9-alpha
-----------------

NOTE: Before Installing RosarioSIS, you must read and agree to the included license.

RosarioSIS is a web based application which relies on other facilities such as a web server, PHP server-side scripting, and PostgreSQL database server.

For RosarioSIS to work you must first have your web server working, PHP working, PostgreSQL working. Setting these up varies a lot with platform, operating system, and distribution so it is well beyond to scope of this brief install document.

RosarioSIS was tested on:

- Windows 7 x64 with Apache 2.2.21, Postgres 9.1, and PHP 5.3.9
- Windows 10 x86 with Apache 2.4.16, Postgres 9.3.6, and PHP 5.4.45
- Ubuntu 12.04 with Apache 2.2.22, Postgres 9.1, and PHP 5.3.10
- Ubuntu 14.04 with Apache 2.4.18, Postgres 9.3.10, and PHP 5.5.9
- Debian Jessie with Apache 2.4.16, Postgres 9.4, and PHP 5.6.13
- through Mozilla Firefox
- through BrowserStack for cross-browser compatibility

Minimum requirements: **PHP 5.3.2** & **Postgres 8**

[Installation Directions for **Windows**](https://github.com/francoisjacquet/rosariosis/wiki/How-to-install-RosarioSIS-on-Windows)


Installing the Package
----------------------

Unzip the RosarioSIS distribution to a directory that is accessible to your web browser. Edit the `config.inc.sample.php` file to set the configuration variables as appropriate for your installation. Rename the file to `config.inc.php`.

- `$DatabaseServer` is the host name or ip for the database server
- `$DatabaseUsername` is the username used for authenticating the database
- `$DatabasePassword` is the password used for authenticating the database
- `$DatabaseName` is the database name
- `$DatabasePort` is the socket port number for accessing the database server

- `$pg_dumpPath` is full path to the postgres database dump utility pg_dump
- `$wkhtmltopdfPath` full path to wkhtmltopdf for pdf 'printing'
  
- `$DefaultSyear` default school year, should be present in the database to be able to login
- `$RosarioNotifyAddress` is the email address to send error and new administrator notifications
- `$RosarioLocales` is a comma separated list of the locale names of the translations (see `locale/` folder for available locales)

  [Optional variables]
- `$RosarioPath` is full path to RosarioSIS installation, you can define it statically for your installation or the runtime value derived from the `__FILE__` magic constant should work
- `$wkhtmltopdfAssetsPath` is path where wkhtmltopdf will access the `assets/` directory, possibly different than how the user's web browser finds it, empty string means no translation
- `$StudentPicturesPath` relative path to student pictures
- `$UserPicturesPath` relative path to user pictures
- `$PortalNotesFilesPath` relative path to portal notes attached files
- `$FS_IconsPath` relative path to food service icons
- `$LocalePath` relative path were the language packs are stored. You need to restart Apache at each change in this directory.
- `$Timezone` sets the default time zone used by all date/time functions. See [List of Supported Timezones](http://php.net/manual/en/timezones.php).

  [Debug mode: add the following line to activate]
- `define( 'ROSARIO_DEBUG', true );`

Now, you're ready to setup the RosarioSIS database. If you have access to the command prompt for your server, follow these instructions. If you're using phpPGAdmin or a similar tool, import the `rosariosis.sql` file included in this package.

1. Open a command prompt window.
2. Login as the postgres user: `server$ su - postgres`
3. Login to the postgres database server using the template1 database: `server$ psql template1`
4. Create the rosariosis database: `template1=# CREATE DATABASE rosariosis;`
5. Skip to step 6 if used in an American / English-speaking environment. If your data will include non-standard English characters (like characters with accent marks), you'll have to create your database with a different character encoding (the default is unicode). `createdb rosariosis -E UTF8` UTF8 is Unicode, 8-bit -- useful for all languages. See [there](http://www.postgresql.org/docs/9.1/interactive/multibyte.html) for more information.
6. Logout of the database server: `template1=# \q`
7. Run the RosarioSIS sql file: `server$ psql rosariosis < YOUR_ROSARIO_INSTALL_DIRECTORY/rosariosis.sql > rosariosis.log`

Notice, you will see several lines beginning "NOTICE:  ALTER TABLE / ADD PRIMARY KEY will create implicit index " after completing direction 6.  Disregard these messages.

If you run into permissions problems involving `rosariosis.log`, simply remove the ` > rosariosis.log` from the command

You will have to start postgres with the -i option, telling postmaster to accept TCP/IP connections. Also, the `pg_hba.conf` file may have to be altered to specify the server's TCP/IP address.

That's it!... now, point your browser to: `http://yourdomain.com/INSTALL_LOCATION/index.php`

and login as 'admin' password 'admin'.  With this login, you can create new users, and change and delete the three template users.  Since students can not be deleted the template student should be changed to a proper student.


Installation problems
---------------------

To help you spot problems, point your browser to: `http://yourdomain.com/INSTALL_LOCATION/diagnostic.php`


Installing [wkhtmltopdf](http://wkhtmltopdf.org/)
-------------------------------------------------

Install instructions for Ubuntu 12.04 64bits:

1. Download the latest executable (0.12.2.1 as of 2015.02.03): `server$ wget http://download.gna.org/wkhtmltopdf/0.12/0.12.2.1/wkhtmltox-0.12.2.1_linux-precise-amd64.deb`

2. Install package: `server$ sudo dpkg --install wkhtmltox-0.12.2.1_linux-precise-amd64.deb`

3. Install the missing dependencies: `server$ sudo apt-get -f install`

4. Test: `server$ wkhtmltopdf --margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 https://www.rosariosis.org/quick-setup-guide/ RosarioSIS_Quick_Setup_Guide.pdf`

5. Set `$wkhtmltopdfPath` in RosarioSIS `config.inc.php` file: `$wkhtmltopdfPath = '/usr/local/bin/wkhtmltopdf';`


Activate PHP mail() function
----------------------------

Install instructions for Ubuntu 12.04 64bits:
	`server$ sudo apt-get install sendmail`


Additional Configuration
------------------------

[Quick Setup Guide](https://github.com/francoisjacquet/rosariosis/wiki/Quick-Setup-Guide)
