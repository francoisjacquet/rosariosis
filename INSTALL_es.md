# INSTRUCCIONES DE INSTALACIÓN

## RosarioSIS Student Information System

Versión 5.1
-------------

RosarioSIS es una aplicación web que depende de un servidor web, el lenguaje de script PHP y un servidor de base de datos PostgreSQL.

Para que funcione RosarioSIS se debe primero tener el servidor web, PostgreSQL, y PHP (incluyendo las extensiones `pgsql`, `gettext`, `mbstring`, `gd`, `curl`, `xmlrpc` y `xml`) operativos. La implementación de aquellos varia con el sistema operativo así que está fuera del alcance de este breve documento de instalación.

RosarioSIS ha sido probado en:

- Windows 7 x64 con Apache 2.2.21, Postgres 9.1, y PHP 5.3.9
- Windows 10 x86 con Apache 2.4.16, Postgres 9.3.6, y PHP 5.4.45
- Ubuntu 14.04 con Apache 2.4.18, Postgres 9.3.10, y PHP 5.5.9
- Debian Jessie con Apache 2.4.16, Postgres 9.4, y PHP 5.6.13
- Debian Stretch con Apache 2.4.25, Postgres 9.6, y PHP 7.0.14
- Ubuntu 16.04 con Apache 2.4.18, Postgres 9.5, y PHP 7.3.4
- Debian Buster con Apache 2.4.38, Postgres 11.5, y PHP 7.3.8
- Shared hosting con cPanel, nginx, Postgres 8.4, y PHP 5.6.27
- a traves de Mozilla Firefox
- con BrowserStack para la compatibilidad de navegadores (no compatible con Internet Explorer 8 o anterior)

Requerimientos mínimos: **PHP 5.3.2** y **PostgreSQL 8.4**

Instrucciones de instalación (en inglés):

- [**Windows**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-Windows)
- [**cPanel**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-cPanel)


Instalar el paquete
-------------------

Descomprima el archivo de RosarioSIS en un directorio accesible con el navegador. Edita el archivo `config.inc.sample.php` para definir las variables de configuración apropiadas, y renombralo `config.inc.php`.

- `$DatabaseServer` Nombre o dirección IP del servidor de base de datos.
- `$DatabaseUsername` Nombre de usuario para conectarse a la base de datos.
- `$DatabasePassword` Contraseña para conectarse a la base de datos.
- `$DatabaseName` Nombre de la base de datos.
- `$DatabasePort` Número de puerto para acceder a la base de datos.

- `$pg_dumpPath` Camino completo hacia el utilitario de exportación de base de datos, pg_dump.
- `$wkhtmltopdfPath` Camino completo hacia el utilitario de generación de PDF, wkhtmltopdf.

- `$DefaultSyear` Año escolar por defecto. Solo cambiar después de haber corrido el programa _Transferir_.
- `$RosarioNotifyAddress` Dirección de email para las notificaciones (nuevos administradores).
- `$RosarioLocales` Lista separada por comas de códigos de lenguajes. Ver la carpeta `locale/` para los códigos disponibles.

#### Variables opcionales

- `$RosarioPath` Camino completo hacia la instalación de RosarioSIS.
- `$wkhtmltopdfAssetsPath` Camino a la carpeta `assets/` para wkhtmltopdf. Puede ser diferente de como el navegador la encuentra. Una cadena vacía significa sin traslado.
- `$StudentPicturesPath` Camino hacia las fotos de los estudiantes.
- `$UserPicturesPath` Camino hacia las fotos de los usuarios.
- `$PortalNotesFilesPath` Camino hacia los archivos adjuntos a las notas del portal.
- `$AssignmentsFilesPath` Camino hacia los archivos de las tareas de los estudiantes.
- `$FS_IconsPath` Camino hacia los iconos del servicio de comida.
- `$FileUploadsPath`Ccamino hacia los archivos subidos.
- `$LocalePath` Camino hacia los lenguajes. Reinicie Apache después de cambiarlo.
- `$PNGQuantPath` Camino hacia [PNGQuant](https://pngquant.org/) (compresión de las imagenes PNG).
- `$RosarioErrorsAddress` Dirección de email para los errores (PHP fatal, base de datos, intentos de pirateo).
- `$Timezone` Zona horaria usada por la funciones de fecha y tiempo. [Listado de zonas horarias admitidas](http://php.net/manual/es/timezones.php).
- `$ETagCache` Pasar a `false` para desactivar el [caché ETag](https://es.wikipedia.org/wiki/HTTP_ETag) y desactivar el caché de sesión "privada". Ver [Sesiones y seguridad](https://secure.php.net/manual/es/session.security.php).
- `define( 'ROSARIO_DEBUG', true );` Modo debug activado.


Base de datos
-------------

Ahora, está listo para configurar la base de datos de RosarioSIS. Si tiene acceso al símbolo del sistema de su servidor, puede seguir estas instrucciones.

1. Abra una ventana del terminal.

2. Entra a PostgreSQL con el usuario postgres:
```console
server$ sudo -u postgres psql
```
3. Crea el usuario rosariosis:
```console
postgres=# CREATE USER rosariosis_user WITH PASSWORD 'rosariosis_user_password';
```
4. Crea la base de datos rosariosis:
```console
postgres=# CREATE DATABASE rosariosis_db WITH ENCODING 'UTF8' OWNER rosariosis_user;
```
5. Salga de PostgreSQL:
```console
postgres=# \q
```

También, el archivo [`pg_hba.conf`](http://www.postgresql.org/docs/current/static/auth-pg-hba-conf.html) puede ser editado para activar la conexión de usuarios con contraseña (`md5`):
```
# "local" is for Unix domain socket connections only
local   all             all                                     md5
```

Para instalar la base de datos, apunte su navegador a: `http://sudominio.com/REPERTORIO_DE_INSTALACION/InstallDatabase.php`

Es todo!... ahora, apunte su navegador a: `http://sudominio.com/REPERTORIO_DE_INSTALACION/index.php`

y entra con el nombre de usuario 'admin' y la contraseña 'admin'. Con esta cuenta, podrá agregar nuevos usuarios, y modificar o suprimir los tres usuarios de ejemplo.


Problemas
---------

Para ayudarlo a detectar problemas de instalación, apunte su navegador a: `http://sudominio.com/REPERTORIO_DE_INSTALACION/diagnostic.php`


Extensiones PHP
---------------

Instrucciones de instalación para Ubuntu 16.04:
```console
server$ sudo apt-get install php-pgsql php-gettext php-mbstring php-gd php-curl php-xmlrpc php-xml
```

Otros lenguajes
---------------

Instrucciones de instalación para Ubuntu 16.04. Instalar el lenguaje español:
```console
server$ sudo apt-get install language-pack-es
```
Luego reinicie el servidor.


[wkhtmltopdf](http://wkhtmltopdf.org/)
--------------------------------------

Instrucciones de instalación para Ubuntu 16.04:
```console
server$ wget https://downloads.wkhtmltopdf.org/0.12/0.12.5/wkhtmltox_0.12.5-1.xenial_amd64.deb
server$ sudo dpkg -i wkhtmltox_0.12.5-1.xenial_amd64.deb
```

Definir el camino en el archivo `config.inc.php`:
    `$wkhtmltopdfPath = '/usr/local/bin/wkhtmltopdf';`


Envio de email
--------------

Instrucciones de instalación para Ubuntu 16.04. Activar la función PHP `mail()`:
```console
server$ sudo apt-get install sendmail
```


Configuración adicional
-----------------------

[Guía de Configuración Rápida](https://www.rosariosis.org/es/quick-setup-guide/)
