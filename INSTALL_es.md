# INSTRUCCIONES DE INSTALACIÓN

## RosarioSIS Student Information System

RosarioSIS es una aplicación web que depende de un servidor web, el lenguaje de script PHP y un servidor de base de datos PostgreSQL o MySQL/MariaDB.

Para que funcione RosarioSIS se debe primero tener el servidor web, PostgreSQL (o MySQL/MariaDB), y PHP (incluyendo las extensiones `pgsql`, `mysqli`, `gettext`, `intl`, `mbstring`, `gd`, `curl`, `xml` y `zip`) operativos. La implementación de aquellos varia con el sistema operativo así que está fuera del alcance de este breve documento de instalación.

RosarioSIS ha sido probado en:

- Windows 10 x86 con Apache 2.4.16, Postgres 9.3.6, y PHP 7.1.18
- macOS Monterey con Apache 2.4.54, Postgres 14.4, y PHP 8.0.21
- Ubuntu 22.04 con Apache 2.4.52, MariaDB 10.6.12, y PHP 5.6.40
- Ubuntu 22.04 con Apache 2.4.57, Postgres 14.9, y PHP 8.1.2
- Debian Bullseye con Apache 2.4.54, Postgres 13.7, MariaDB 10.5.15, y PHP 8.2.6
- Shared hosting con cPanel, nginx, Postgres 9.2, y PHP 7.2
- a traves de Mozilla Firefox y Google Chrome
- con BrowserStack para la compatibilidad de navegadores (no compatible con Internet Explorer)

Requerimientos mínimos: **PHP 5.5.9** y **PostgreSQL 8.4** o **MySQL 5.6**/MariaDB

Instrucciones de instalación:

- [**Windows**](https://gitlab.com/francoisjacquet/rosariosis/wikis/Instalar-RosarioSIS-en-Windows)
- [**Mac**](https://gitlab.com/francoisjacquet/rosariosis/-/wikis/How-to-install-RosarioSIS-on-Mac-(macOS,-OS-X)) (en inglés)
- [**cPanel**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-cPanel) (en inglés)
- [**Softaculous**](https://gitlab.com/francoisjacquet/rosariosis/-/wikis/How-to-install-RosarioSIS-with-Softaculous) (en inglés)
- [**Docker**](https://github.com/francoisjacquet/docker-rosariosis) (en inglés)
- **Ubuntu** (o cualquier distribución Linux basada en Debian), ver abajo


Instalar el paquete
-------------------

Descomprima el archivo de RosarioSIS, o clona el repositorio usando git en un directorio accesible con el navegador. Edita el archivo `config.inc.sample.php` para definir las variables de configuración apropiadas, y renombralo `config.inc.php`.

- `$DatabaseType` Tipo del servidor de base de datos: mysql o postgresql.
- `$DatabaseServer` Nombre o dirección IP del servidor de base de datos.
- `$DatabaseUsername` Nombre de usuario para conectarse a la base de datos.
- `$DatabasePassword` Contraseña para conectarse a la base de datos.
- `$DatabaseName` Nombre de la base de datos.

- `$DatabaseDumpPath` Camino completo hacia el utilitario de exportación de base de datos, pg_dump (PostgreSQL) o mysqldump (MySQL).
- `$wkhtmltopdfPath` Camino completo hacia el utilitario de generación de PDF, wkhtmltopdf.

- `$DefaultSyear` Año escolar por defecto. Solo cambiar después de haber corrido el programa _Transferir_.
- `$RosarioNotifyAddress` Dirección de email para las notificaciones (nuevo administrador, nuevo estudiante / usuario, nueva inscripción).
- `$RosarioLocales` Lista separada por comas de códigos de lenguajes. Ver la carpeta `locale/` para los códigos disponibles.

#### Variables opcionales

- `$DatabasePort` Número de puerto para acceder a la base de datos. Por defecto: 5432 para PostgreSQL y 3306 para MySQL.
- `$RosarioPath` Camino completo hacia la instalación de RosarioSIS.
- `$StudentPicturesPath` Camino hacia las fotos de los estudiantes.
- `$UserPicturesPath` Camino hacia las fotos de los usuarios.
- `$PortalNotesFilesPath` Camino hacia los archivos adjuntos a las notas del portal.
- `$AssignmentsFilesPath` Camino hacia los archivos de las tareas de los estudiantes.
- `$FS_IconsPath` Camino hacia los iconos del servicio de comida.
- `$FileUploadsPath` Camino hacia los archivos subidos.
- `$LocalePath` Camino hacia los lenguajes. Reinicie Apache después de cambiarlo.
- `$PNGQuantPath` Camino hacia [PNGQuant](https://pngquant.org/) (compresión de las imagenes PNG).
- `$RosarioErrorsAddress` Dirección de email para los errores (PHP fatal, base de datos, intentos de pirateo).
- `$Timezone` Zona horaria usada por la funciones de fecha y tiempo. [Listado de zonas horarias admitidas](http://php.net/manual/es/timezones.php).
- `$ETagCache` Pasar a `false` para desactivar el [caché ETag](https://es.wikipedia.org/wiki/HTTP_ETag) y desactivar el caché de sesión "privada". Ver [Sesiones y seguridad](https://secure.php.net/manual/es/session.security.php).
- `define( 'ROSARIO_POST_MAX_SIZE_LIMIT', 16 * 1024 * 1024 );` Limitar el tamaño de `$_POST` (16MB por defecto). Detalles [acá](https://gitlab.com/francoisjacquet/rosariosis/-/blob/mobile/Warehouse.php#L290).
- `define( 'ROSARIO_DEBUG', true );` Modo debug activado.
- `define( 'ROSARIO_DISABLE_ADDON_UPLOAD', true );` Desactivar el upload de complementos (módulos y plugins).
- `define( 'ROSARIO_DISABLE_ADDON_DELETE', true );` Desactivar la posibilidad de eliminar complementos (modules & plugins).


Crear la base de datos
----------------------

Ahora, está listo para configurar la base de datos de RosarioSIS. Si tiene acceso al símbolo del sistema de su servidor, abra una ventana del terminal, y sigua estas instrucciones.

Las instrucciones siguientes aplican para **PostgreSQL** (ver abajo para MySQL):

1. Entra a PostgreSQL con el usuario postgres:
```bash
server$ sudo -u postgres psql
```
2. Crea el usuario rosariosis:
```bash
postgres=# CREATE USER rosariosis_user WITH PASSWORD 'rosariosis_user_password';
```
3. Crea la base de datos rosariosis:
```bash
postgres=# CREATE DATABASE rosariosis_db WITH ENCODING 'UTF8' OWNER rosariosis_user;
```
4. Salga de PostgreSQL:
```bash
postgres=# \q
```

También, el archivo [`pg_hba.conf`](http://www.postgresql.org/docs/current/static/auth-pg-hba-conf.html) puede ser editado para activar la conexión de usuarios con contraseña (`md5`):
```
# "local" is for Unix domain socket connections only
local   all             all                                     md5
```

---------------------------------------------

Las instrucciones siguientes aplican para **MySQL** (RosarioSIS versión 10 o superior):

1. Entra a MySQL con el usuario root:
```bash
server$ sudo mysql
```
o
```bash
server$ mysql -u root -p
```
2. Permitir la creación de funciones:
```bash
mysql> SET GLOBAL log_bin_trust_function_creators=1;
```
3. Crea el usuario rosariosis:
```bash
mysql> CREATE USER 'rosariosis_user'@'localhost' IDENTIFIED BY 'rosariosis_user_password';
```
4. Crea la base de datos rosariosis:
```bash
mysql> CREATE DATABASE rosariosis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
mysql> GRANT ALL PRIVILEGES ON rosariosis_db.* TO 'rosariosis_user'@'localhost';
```
5. Salga de MySQL:
```bash
mysql> \q
```


Instalar la base de datos
-------------------------

Para instalar la base de datos, apunte su navegador a: `http://sudominio.com/REPERTORIO_DE_INSTALACION/InstallDatabase.php`

Es todo!... ahora, apunte su navegador a: `http://sudominio.com/REPERTORIO_DE_INSTALACION/index.php`

y entra con el nombre de usuario 'admin' y la contraseña 'admin'. Con esta cuenta, podrá agregar nuevos usuarios, y modificar o suprimir los tres usuarios de ejemplo.


Problemas
---------

Para ayudarlo a detectar problemas de instalación, apunte su navegador a: `http://sudominio.com/REPERTORIO_DE_INSTALACION/diagnostic.php`


Extensiones PHP
---------------

Instrucciones de instalación para Ubuntu 22.04:
```bash
server$ sudo apt-get install php-pgsql php-mysql php-intl php-mbstring php-gd php-curl php-xml php-zip
```


php.ini
-------

Configuración PHP recomendada. Editar el archivo [`php.ini`](https://www.php.net/manual/es/ini.list.php) como sigue:
```
; Maximum time in seconds a PHP script is allowed to run
max_execution_time = 240

; Maximum accepted input variables ($_GET, $_POST)
; 4000 allows submitting lists of up to 1000 elements, each with multiple inputs
max_input_vars = 4000

; Maximum memory (RAM) allocated to a PHP script
memory_limit = 512M

; Session timeout: 1 hour
session.gc_maxlifetime = 3600

; Maximum allowed size for uploaded files
upload_max_filesize = 50M

; Must be greater than or equal to upload_max_filesize
post_max_size = 51M
```
Reiniciar PHP y Apache.


Otros lenguajes
---------------

Instrucciones de instalación para Ubuntu 22.04. Instalar el lenguaje español:
```bash
server$ sudo apt-get install language-pack-es
```
Luego reinicie el servidor.


[wkhtmltopdf](http://wkhtmltopdf.org/)
--------------------------------------

Instrucciones de instalación para Ubuntu 22.04 (jammy):
```bash
server$ wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox_0.12.6.1-2.jammy_amd64.deb
server$ sudo apt install ./wkhtmltox_0.12.6.1-2.jammy_amd64.deb
```

Definir el camino en el archivo `config.inc.php`:
    `$wkhtmltopdfPath = '/usr/local/bin/wkhtmltopdf';`


Envio de email
--------------

Instrucciones de instalación para Ubuntu 22.04. Activar la función PHP `mail()`:
```bash
server$ sudo apt-get install sendmail
```


Configuración adicional
-----------------------

[Guía de Configuración Rápida](https://www.rosariosis.org/es/quick-setup-guide/)

[Asegurar RosarioSIS](https://gitlab.com/francoisjacquet/rosariosis/-/wikis/Secure-RosarioSIS)
