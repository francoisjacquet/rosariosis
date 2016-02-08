The `locale/` folder contains the localization files.
The structure of the subfolders and files must be as follow:
For example, for a French translation:

```
locale/
	fr_FR.utf8/
		LC_MESSAGES/
			rosariosis.mo
			rosariosis.po
```

Note: "fr_FR.utf8" is the name of the French locale. Do not forget to install the locale first (on Ubuntu):
`server$ locale -a`
`server$ sudo apt-get install language-pack-fr`

Note 2: The localization system is gettext, and the `*.po` files can be edited using [Poedit](http://poedit.net/). To start (or complete) a translation, please follow [this tutorial](https://github.com/francoisjacquet/rosariosis/wiki/Localizing,-translate-RosarioSIS-with-Poedit).