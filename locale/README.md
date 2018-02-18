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
```
Note: **fr_FR.utf8** is the name of the French locale.


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
The localization system is `gettext`, and the `*.po` files can be edited using [Poedit](http://poedit.net/). To start (or complete) a translation, please follow [this tutorial](https://github.com/francoisjacquet/rosariosis/wiki/Localizing,-translate-RosarioSIS-with-Poedit).

`.pot` file available in `locale/en_US.utf8/LC_MESSAGES/rosariosis.pot`


Reference
---------
A reference of the currently available translations, including their code, language, country, completion and other useful information is available in the [REFERENCE.md](REFERENCE.md) file.
