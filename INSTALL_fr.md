# INSTRUCTIONS D'INSTALLATION

## RosarioSIS Student Information System

Version 5.1
-------------

RosarioSIS est une application web qui dépend d'un serveur web, du langage de script PHP et d'un serveur de base de données PostgreSQL.

Pour que RosarioSIS fonctionne, vous devrez d'abord avoir votre serveur web, PostgreSQL et PHP (extensions `pgsql`, `gettext`, `mbstring`, `gd`, `curl`, `xmlrpc` & `xml` incluses) en état de marche. L'installation et la configuration des ces derniers varie selon votre système d'exploitation aussi ne seront-elles pas couvertes ici.

RosarioSIS a été testé sur:

- Windows 7 x64 avec Apache 2.2.21, Postgres 9.1, et PHP 5.3.9
- Windows 10 x86 avec Apache 2.4.16, Postgres 9.3.6, et PHP 5.4.45
- Ubuntu 14.04 avec Apache 2.4.18, Postgres 9.3.10, et PHP 5.5.9
- Debian Jessie avec Apache 2.4.16, Postgres 9.4, et PHP 5.6.13
- Debian Stretch avec Apache 2.4.25, Postgres 9.6, et PHP 7.0.14
- Ubuntu 16.04 avec Apache 2.4.18, Postgres 9.5, et PHP 7.3.4
- Debian Buster avec Apache 2.4.38, Postgres 11.5, et PHP 7.3.8
- Hébergement mutualisé avec cPanel, nginx, Postgres 8.4, et PHP 5.6.27
- à travers Mozilla Firefox
- à travers BrowserStack pour la compatibilité navigateurs (incompatible avec Internet Explorer 8 et inférieur)

Minimum requis: **PHP 5.3.2** & **PostgreSQL 8.4**

Instructions d'installation pour (en anglais):

- [**Windows**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-Windows)
- [**cPanel**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-cPanel)


Installer le paquet
-------------------

Décompressez l'archive de RosarioSIS dans un répertoire accessible depuis le navigateur. Éditez le fichier `config.inc.sample.php` afin de régler les variables de configuration et renommez-le `config.inc.php`.

- `$DatabaseServer` Nom de l'hôte ou IP du serveur de base de données.
- `$DatabaseUsername` Nom d'utilisateur pour se connecter à la base de données.
- `$DatabasePassword` Mot de passe pour se connecter à la base de données.
- `$DatabaseName` Nom de la base de données.
- `$DatabasePort` Numéro du port pour accéder au serveur de base de données.

- `$pg_dumpPath` Chemin complet vers l'utilitaire d'export de base de donnée, pg_dump.
- `$wkhtmltopdfPath` Chemin complet vers l'utilitaire de génération de PDF, wkhtmltopdf.

- `$DefaultSyear` Année scolaire par défaut. Ne changer qu'après avoir lancé le programme _Report Final_.
- `$RosarioNotifyAddress` Adresse email pour les notifications (nouvel administrateur).
- `$RosarioLocales` Liste des codes de langues séparées par des virgules. Voir le dossier `locale/` pour les codes disponibles.

#### Variables optionelles

- `$RosarioPath` Chemin complet vers l'installation de RosarioSIS.
- `$wkhtmltopdfAssetsPath` Chemin du répertoire `assets/` pour wkhtmltopdf. Peut-être différent du chemin utilisé par le navigateur. Une chaîne vide signifie aucune translation.
- `$StudentPicturesPath` Chemin vers les photos des élèves.
- `$UserPicturesPath` Chemin vers les photos des utilisateurs.
- `$PortalNotesFilesPath` Chemin vers les fichiers joints des notes du portail.
- `$AssignmentsFilesPath` Chemin vers les fichiers des devoirs des élèves.
- `$FS_IconsPath` Chemin vers les icônes de la cantine.
- `$FileUploadsPath` Chemin vers les fichiers uploadés.
- `$LocalePath` Chemin vers les packs de langue. Redémarrer Apache après modification.
- `$PNGQuantPath` Chemin vers [PNGQuant](https://pngquant.org/) (compression des images PNG).
- `$RosarioErrorsAddress` Adresse email pour les erreurs (PHP fatal, base de donnée, tentatives de piratage).
- `$Timezone` Fuseau horaire utilisé par les fonctions de date/heure. [Liste des Fuseaux Horaires Supportés](http://php.net/manual/fr/timezones.php).
- `$ETagCache` Réglez sur `false` pour désactiver le [cache ETag](https://fr.wikipedia.org/wiki/Balise-entit%C3%A9_ETag_HTTP) et le cache de session "privée". Voir [Sessions et security](https://secure.php.net/manual/fr/session.security.php).
- `define( 'ROSARIO_DEBUG', true );` Mode debug activé.


Base de données
---------------

Vous êtes maintenant prêt pour configurer la base de données de RosarioSIS. Si vous avez accès à l'invite de commande sur votre serveur, suivez ces instructions.

1. Ouvrez une fenêtre de terminal.

2. Connectez-vous à PostgreSQL avec l'utilisateur postgres:
```console
server$ sudo -u postgres psql
```
3. Créez l'utilisateur rosariosis:
```console
postgres=# CREATE USER rosariosis_user WITH PASSWORD 'rosariosis_user_password';
```
4. Créez la base de données rosariosis:
```console
postgres=# CREATE DATABASE rosariosis_db WITH ENCODING 'UTF8' OWNER rosariosis_user;
```
5. Déconnexion de PostgreSQL:
```console
postgres=# \q
```

Aussi, vous devrez peut-être éditer le fichier [`pg_hba.conf`](http://www.postgresql.org/docs/current/static/auth-pg-hba-conf.html) afin d'autoriser la connexion d'utilisateur par mot de passe (`md5`):
```
# "local" is for Unix domain socket connections only
local   all             all                                     md5
```

Pour installer de la base de données, pointez votre navigateur sur: `http://votredomaine.com/REPERTOIRE_DINSTALLATION/InstallDatabase.php`

C'est tout!... maintenant, pointez votre navigateur sur: `http://votredomaine.com/REPERTOIRE_DINSTALLATION/index.php`

et connectez-vous avec le nom d'utilisateur 'admin' et le mot de passe 'admin'. Avec cet utilisateur, vous pourrez créer de nouveaux utilisateurs, et modifier ou supprimer les trois utilisateurs type.


Problèmes
---------

Afin de vous aider à identifier les problèmes, pointez votre navigateur sur: `http://votredomaine.com/REPERTOIRE_DINSTALLATION/diagnostic.php`


Extensions PHP
--------------

Instructions d'installation pour Ubuntu 16.04:
```console
server$ sudo apt-get install php-pgsql php-gettext php-mbstring php-gd php-curl php-xmlrpc php-xml
```


Autres langues
--------------

Instructions d'installation pour Ubuntu 16.04 et la locale _Espagnol_:
```console
server$ sudo apt-get install language-pack-es
```
Ensuite redémarrez le serveur.


[wkhtmltopdf](http://wkhtmltopdf.org/)
--------------------------------------

Instructions d'installation pour Ubuntu 16.04:
```console
server$ wget https://downloads.wkhtmltopdf.org/0.12/0.12.5/wkhtmltox_0.12.5-1.xenial_amd64.deb
server$ sudo dpkg -i wkhtmltox_0.12.5-1.xenial_amd64.deb
```

Définir le chemin dans le fichier `config.inc.php`:
    `$wkhtmltopdfPath = '/usr/local/bin/wkhtmltopdf';`


Envoi d'email
-------------

Instructions d'installation pour Ubuntu 16.04. Activer la fonction `mail()` de PHP:
```console
server$ sudo apt-get install sendmail
```


Configuration additionnelle
---------------------------

[Guide de Configuration Rapide](https://www.rosariosis.org/fr/quick-setup-guide/)
