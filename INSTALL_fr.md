# INSTRUCTIONS D'INSTALLATION

## RosarioSIS Student Information System

Version 4.9.2
-------------

NOTE: Avant d'installer RosarioSIS, vous devez lire et accepter la [licence](LICENSE) incluse (en anglais).

RosarioSIS est une application web qui dépend d'un serveur web, du langage de script PHP et d'un serveur de base de données PostgreSQL.

Pour que RosarioSIS fonctionne, vous devrez d'abord avoir votre serveur web, PostgreSQL et PHP (extensions `pgsql`, `gettext`, `mbstring`, `gd`, `curl`, `xmlrpc` & `xml` incluses) en état de marche. L'installation et la configuration des ces derniers varie grandement selon votre architecture, système d'exploitation et distribution, aussi ne seront-elles pas couvertes ici.

RosarioSIS a été testé sur:

- Windows 7 x64 avec Apache 2.2.21, Postgres 9.1, et PHP 5.3.9
- Windows 10 x86 avec Apache 2.4.16, Postgres 9.3.6, et PHP 5.4.45
- Ubuntu 14.04 avec Apache 2.4.18, Postgres 9.3.10, et PHP 5.5.9
- Debian Jessie avec Apache 2.4.16, Postgres 9.4, et PHP 5.6.13
- Debian Stretch avec Apache 2.4.25, Postgres 9.6, et PHP 7.0.14
- Ubuntu 16.04 avec Apache 2.4.18, Postgres 9.5 et PHP 7.3.4
- Hébergement mutualisé avec cPanel, nginx, Postgres 8.4, et PHP 5.6.27
- à travers Mozilla Firefox
- à travers BrowserStack pour la compatibilité navigateurs (pas compatible avec Internet Explorer 8 et inférieur)

Minimum requis: **PHP 5.3.2** & **PostgreSQL 8**

[Instructions d'installation pour **Windows**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-Windows) (en anglais)

[Instructions d'installation pour **cPanel**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-cPanel) (en anglais)


Installer le paquet
-------------------

Décompressez l'archive de RosarioSIS dans un répertoire accessible depuis le navigateur. Éditez le fichier `config.inc.sample.php` afin de régler les variables de configuration en accord avec votre installation. Renommez le fichier `config.inc.php`.

- `$DatabaseServer` est le nom de l'hôte ou l'IP du serveur de base de données
- `$DatabaseUsername` est le nom d'utilisateur utilisé pour se connecter à la base de données
- `$DatabasePassword` est le mot de passe utilisé pour se connecter à la base de données
- `$DatabaseName` est le nom de la base de données
- `$DatabasePort` est le numéro du port utilisé pour accéder au serveur de base de données

- `$pg_dumpPath` est le chemin complet vers l'utilitaire d'export de base de donnée, pg_dump
- `$wkhtmltopdfPath` est le chemin complet vers wkhtmltopdf pour la génération de PDF

- `$DefaultSyear` est l'année scolaire par défaut, elle devrait correspondre à la base de donnée afin de pouvoir se connecter
- `$RosarioNotifyAddress` est l'adresse email à laquelle sont envoyées les notifications (nouvel administrateur)
- `$RosarioLocales` est une liste de noms de locales (packs de langue) séparées par des virgules (voir le dossier `locale/` pour les locales disponibles)

#### Variables optionelles

- `$RosarioPath` est le chemin complet vers l'installation de RosarioSIS, vous pouvez le définir statiquement ou utiliser la valeur dérivée de la constante magique `__FILE__` qui devrait fonctionner
- `$wkhtmltopdfAssetsPath` est le chemin d'accès au répertoire `assets/` pour wkhtmltopdf, peut-être différent du chemin utilisé par le navigateur, une chaîne vide signifie aucune traduction
- `$StudentPicturesPath` chemin vers les photos des élèves
- `$UserPicturesPath` chemin vers les photos des utilisateurs
- `$PortalNotesFilesPath` chemin vers les fichiers joints des notes du portail
- `$AssignmentsFilesPath` chemin vers les fichiers des devoirs des élèves
- `$FS_IconsPath` chemin vers les icônes de la cantine
- `$FileUploadsPath` chemin vers les fichiers uploadés
- `$LocalePath` chemin où les packs de langue sont stockés. Vous devez redémarrer Apache à chaque changement de ce répertoire.
- `$PNGQuantPath` chemin vers [PNGQuant](https://pngquant.org/) pour la compression des PNG.
- `$RosarioErrorsAddress` est l'adresse email à laquelle sont envoyées les erreurs (PHP fatal, base de donnée, tentatives de piratage)
- `$Timezone` défini le fuseau horaire par défaut utilisé par les fonctions de date/heure. Voir la [Liste des Fuseaux Horaires Supportés](http://php.net/manual/fr/timezones.php).
- `$ETagCache` réglez-le sur `false` pour désactiver le [cache ETag](https://fr.wikipedia.org/wiki/Balise-entit%C3%A9_ETag_HTTP) et le cache de session "privée". Voir [Sessions et security](https://secure.php.net/manual/fr/session.security.php).

  [Mode debug: ajouter la ligne suivante pour l'activer]
- `define( 'ROSARIO_DEBUG', true );`


Installation de la base de données
----------------------------------

Vous êtes maintenant prêt pour installer la base de données de RosarioSIS. Si vous avez à l'invite' de commande sur votre serveur, suivez ces instructions. Si vous utilisez phpPgAdmin ou un utilitaire similaire, importez le fichier `rosariosis.sql` inclus dans ce paquet.

1. Ouvrez une fenêtre d'invite de commande.

2. Connectez-vous avec l'utilisateur postgres:
    `server$ sudo -i -u postgres`

3. Passez sur l'invite de commande PostgreSQL:
    `server$ psql`

4. Créez l'utilisateur rosariosis:
    `postgres=# CREATE USER rosariosis_user WITH PASSWORD 'rosariosis_user_password';`

5. Créez la base de données rosariosis:
    `postgres=# CREATE DATABASE rosariosis_db WITH ENCODING 'UTF8' OWNER rosariosis_user;`

6. Déconnexion de PostgreSQL:
    `postgres=# \q` &
    `server$ exit`

7. Importez le fichier SQL de RosarioSIS:
    `server$ psql -f REPERTOIRE_DINSTALLATION/rosariosis.sql rosariosis_db rosariosis_user`

8. Importez le fichier SQL de traduction en français:
    `server$ psql -f REPERTOIRE_DINSTALLATION/rosariosis_fr.sql rosariosis_db rosariosis_user`

Aussi, vous devrez peut-être éditer le fichier [`pg_hba.conf`](http://www.postgresql.org/docs/current/static/auth-pg-hba-conf.html) afin d'autoriser la connexion d'utilisateur par mot de passe (`md5`):
```
# "local" is for Unix domain socket connections only
local   all             all                                     md5
```

C'est tout!... maintenant, pointez votre navigateur sur: `http://votredomaine.com/REPERTOIRE_DINSTALLATION/index.php`

et connectez-vous avec le nom d'utilisateur 'admin' et le mot de passe 'admin'. Avec cet utilisateur, vous pourrez créer de nouveaux utilisateurs, et modifier ou supprimer les trois utilisateurs type.


Problèmes d'installation
------------------------

Afin de vous aider à identifier les problèmes, pointez votre navigateur sur: `http://votredomaine.com/REPERTOIRE_DINSTALLATION/diagnostic.php`


Installer les extensions PHP
----------------------------

Instructions d'installation pour Ubuntu 16.04:
    `server$ sudo apt-get install php-pgsql php-gettext php-mbstring php-gd php-curl php-xmlrpc php-xml`


Installer d'autres langues
--------------------------

Instructions d'installation pour Ubuntu 16.04 et la locale _Espagnol_:
    `server$ sudo apt-get install language-pack-es`
Ensuite redémarrez le serveur.


Installer [wkhtmltopdf](http://wkhtmltopdf.org/)
------------------------------------------------

Instructions d'installation pour Ubuntu 16.04:
```
server$ wget https://downloads.wkhtmltopdf.org/0.12/0.12.5/wkhtmltox_0.12.5-1.xenial_amd64.deb
server$ sudo dpkg -i wkhtmltox_0.12.5-1.xenial_amd64.deb
```

Définir le chemin dans `config.inc.php`:

`$wkhtmltopdfPath = '/usr/local/bin/wkhtmltopdf';`


Activer la fonction PHP mail()
------------------------------

Instructions d'installation pour Ubuntu 16.04:
    `server$ sudo apt-get install sendmail`


Configuration additionnelle
---------------------------

[Guide de Configuration Rapide](https://www.rosariosis.org/fr/quick-setup-guide/)
