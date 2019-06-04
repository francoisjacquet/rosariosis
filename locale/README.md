LOCALE
======

The `locale/` folder contains the localization files used to translate RosarioSIS strings.


Folders structure
-----------------
The structure of the subfolders and files _must_ be as follow.
Example for the French translation:
```
locale/
	fr_FR.utf8/
		LC_MESSAGES/
			rosariosis.mo
			rosariosis.po
			help.mo
			help.po
```
Note: **fr_FR.utf8** is the French locale code.


Install
-------
Install the locale first on your server.
Example for Ubuntu:
```
server$ locale -a
server$ sudo apt-get install language-pack-fr
```


Gettext & Poedit
----------------
The localization system is `gettext`, and the `*.po` files can be edited using [Poedit](http://poedit.net/). To start (or complete) a translation, please follow [this tutorial](https://gitlab.com/francoisjacquet/rosariosis/wikis/Localizing,-translate-RosarioSIS-with-Poedit).

`.pot` files are available in `locale/en_US.utf8/LC_MESSAGES/`


Reference
---------
A reference of the currently available translations, including their code, language, country, completion and other useful information is available in the [REFERENCE.md](REFERENCE.md) file.


Help texts
----------
The `Help_en.php` file serves as a reference to generate the Gettext `help.pot` / `help.po` files
and translate Help texts to your language.
The Catalog should only reference the Help_en.php file and detect the `_help` function / source keyword.


Flag icons
----------
Place a `flag.png` file inside your locale folder. It will be displayed for language selection on the Login screen.

[World Flag icons](http://www.customicondesign.com/free-icons/flag-icon-set/all-in-one-country-flag-icon-set/)
