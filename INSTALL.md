# INSTALLATION DIRECTIONS

## RosarioSIS Student Information System

Version 3.0
-----------

NOTE: Before Installing RosarioSIS, you must read and agree to the included [license](LICENSE).

RosarioSIS is a web based application which relies on other facilities such as a web server, PHP server-side scripting, and PostgreSQL database server.

For RosarioSIS to work you must first have your web server working, PostgreSQL working, PHP working (including the `pgsql`, `gettext`, `mbstring`, `curl`, `xmlrpc` & `xml` extensions). Setting these up varies a lot with platform, operating system, and distribution so it is well beyond to scope of this brief install document.

RosarioSIS was tested on:

- Windows 7 x64 with Apache 2.2.21, Postgres 9.1, and PHP 5.3.9
- Windows 10 x86 with Apache 2.4.16, Postgres 9.3.6, and PHP 5.4.45
- Ubuntu 12.04 with Apache 2.2.22, Postgres 9.1, and PHP 5.3.10
- Ubuntu 14.04 with Apache 2.4.18, Postgres 9.3.10, and PHP 5.5.9
- Debian Jessie with Apache 2.4.16, Postgres 9.4, and PHP 5.6.13
- Shared hosting with cPanel, nginx, Postgres 8.4, and PHP 5.6.27
- through Mozilla Firefox
- through BrowserStack for cross-browser compatibility (not compatible with Internet Explorer 8 or lower)

Minimum requirements: **PHP 5.3.2** & **Postgres 8**

[Installation directions for **Windows**](https://github.com/francoisjacquet/rosariosis/wiki/How-to-install-RosarioSIS-on-Windows)

[Installation directions for **cPanel**](https://github.com/francoisjacquet/rosariosis/wiki/How-to-install-RosarioSIS-on-cPanel)


Installing the Package
----------------------

Unzip the RosarioSIS distribution to a directory that is accessible to your web browser. Edit the `config.inc.sample.php` file to set the configuration variables as appropriate for your installation. Rename the file to `config.inc.php`.

- `$DatabaseServer` is the host name or IP for the database server
- `$DatabaseUsername` is the username used for authenticating the database
- `$DatabasePassword` is the password used for authenticating the database
- `$DatabaseName` is the database name
- `$DatabasePort` is the socket port number for accessing the database server

- `$pg_dumpPath` is full path to the postgres database dump utility (pg_dump)
- `$wkhtmltopdfPath` full path to wkhtmltopdf for PDF 'printing'

- `$DefaultSyear` default school year, should match the database to be able to login
- `$RosarioNotifyAddress` is the email address to send error and new administrator notifications to
- `$RosarioLocales` is a comma separated list of the locale names of the translations (see `locale/` folder for available locales)

#### Optional variables

- `$RosarioPath` is full path to RosarioSIS installation, you can define it statically for your installation or the runtime value derived from the `__FILE__` magic constant should work
- `$wkhtmltopdfAssetsPath` is path where wkhtmltopdf will access the `assets/` directory, possibly different than how the user's web browser finds it, empty string means no translation
- `$StudentPicturesPath` path to student pictures
- `$UserPicturesPath` path to user pictures
- `$PortalNotesFilesPath` path to portal notes attached files
- `$AssignmentsFilesPath` path to student assignments files
- `$FS_IconsPath` path to food service icons
- `$LocalePath` path were the language packs are stored. You need to restart Apache at each change in this directory.
- `$Timezone` sets the default time zone used by date/time functions. See [List of Supported Timezones](http://php.net/manual/en/timezones.php).
- `$ETagCache` set to `false` to deactivate the [ETag cache](https://en.wikipedia.org/wiki/HTTP_ETag) and disable "private" session cache. See [Sessions and security](http://php.net/manual/it/session.security.php).

  [Debug mode: add the following line to activate]
- `define( 'ROSARIO_DEBUG', true );`

Now, you're ready to setup the RosarioSIS database. If you have access to the command prompt for your server, follow these instructions. If you're using phpPGAdmin or a similar tool, import the `rosariosis.sql` file included in this package.

1. Open a command prompt window.

2. Login as the postgres user:
	`server$ sudo -i -u postgres`

3. Get a PostgreSQL prompt:
	`server$ psql`

4. Create the rosariosis database:
	`postgres=# CREATE DATABASE rosariosis WITH ENCODING 'UTF8';`

5. Create the rosariosis user:
	`postgres=# CREATE USER rosariosis WITH PASSWORD 'rosariosis_password';`

6. Grant the user access to the database:
	`postgres=# GRANT ALL PRIVILEGES ON DATABASE rosariosis to rosariosis;`

7. Logout of PostgreSQL:
	`postgres=# \q` &
	`server$ exit`

8. Run the RosarioSIS SQL file:
	`server$ psql -f INSTALL_DIRECTORY/rosariosis.sql rosariosis rosariosis`

Also, the [`pg_hba.conf`](http://www.postgresql.org/docs/current/static/auth-pg-hba-conf.html) file may have to be altered to specify the server's TCP/IP address.

That's it!... now, point your browser to: `http://yourdomain.com/INSTALL_LOCATION/index.php`

and login as 'admin' password 'admin'.  With this login, you can create new users, and change and delete the three template users. Since students cannot be deleted the template student should be changed to a proper student.


Installation problems
---------------------

To help you spot problems, point your browser to: `http://yourdomain.com/INSTALL_LOCATION/diagnostic.php`


Installing PHP extensions
-------------------------

Install instructions for Ubuntu 16.04:
	`server$ sudo apt-get install php-pgsql php-gettext php-mbstring php-curl php-xmlrpc php-xml`


Installing other languages
--------------------------

Install instructions for Ubuntu 16.04 and the _Spanish_ locale:
	`server$ sudo apt-get install language-pack-es`


Installing [wkhtmltopdf](http://wkhtmltopdf.org/)
-------------------------------------------------

Install instructions for Ubuntu 16.04:
	`server$ sudo apt-get install wkhtmltopdf`


Activate PHP mail() function
----------------------------

Install instructions for Ubuntu 16.04:
	`server$ sudo apt-get install sendmail`


Additional Configuration
------------------------

[Quick Setup Guide](https://github.com/francoisjacquet/rosariosis/wiki/Quick-Setup-Guide)
