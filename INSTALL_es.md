# INSTRUCCIONES DE INSTALACIÓN

## RosarioSIS Student Information System

Versión 4.9.2
-------------

NOTA: Antes de instalar RosarioSIS, debe leer y aprobar la [licencia](LICENSE) incluida.

RosarioSIS es una aplicación web que depende de un servidor web, el lenguaje de script PHP y un servidor de base de datos PostgreSQL.

Para que funcione RosarioSIS se debe primero tener el servidor web, PostgreSQL, y PHP (incluyendo las extensiones `pgsql`, `gettext`, `mbstring`, `gd`, `curl`, `xmlrpc` y `xml`) operativos. La implementación de aquellos varia con la plataforma, el sistema operativo y la distribución así que está fuera del alcance de este breve documento de instalación.

RosarioSIS ha sido probado en:

- Windows 7 x64 con Apache 2.2.21, Postgres 9.1, y PHP 5.3.9
- Windows 10 x86 con Apache 2.4.16, Postgres 9.3.6, y PHP 5.4.45
- Ubuntu 14.04 con Apache 2.4.18, Postgres 9.3.10, y PHP 5.5.9
- Debian Jessie con Apache 2.4.16, Postgres 9.4, y PHP 5.6.13
- Debian Stretch con Apache 2.4.25, Postgres 9.6, y PHP 7.0.14
- Ubuntu 16.04 con Apache 2.4.18, Postgres 9.5 y PHP 7.3.4
- Shared hosting con cPanel, nginx, Postgres 8.4, y PHP 5.6.27
- a traves de Mozilla Firefox
- con BrowserStack para la compatibilidad con otros navegadores (no compatible con Internet Explorer 8 o anterior)

Requerimientos mínimos: **PHP 5.3.2** y **PostgreSQL 8**

[Instrucciones de instalación para **Windows**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-Windows) (en inglés)

[Instrucciones de instalación para **cPanel**](https://gitlab.com/francoisjacquet/rosariosis/wikis/How-to-install-RosarioSIS-on-cPanel) (en inglés)


Instalar el paquete
-------------------

Descomprima el archivo de RosarioSIS en un directorio accesible con el navegador. Edita el archivo `config.inc.sample.php` para definir las variables de configuración apropiadas, de acuerdo con su instalación. Renombra el archivo `config.inc.php`.

- `$DatabaseServer` es el nombre del servidor o la dirección de IP del servidor de base de datos
- `$DatabaseUsername` es el nombre de usuario usado para conectarse a la base de datos
- `$DatabasePassword` es la contraseña usada para conectarse a la base de datos
- `$DatabaseName` es el nombre de la base de datos
- `$DatabasePort` es el número de puerto para acceder a la base de datos

- `$pg_dumpPath` es el camino completo hacia el utilitario de exportación de la base de datos postgres, pg_dump
- `$wkhtmltopdfPath` es el camino completo hacia wkhtmltopdf para la generación de PDF

- `$DefaultSyear` es el año escolar por defecto, debe corresponder a la base de datos para poder entrar
- `$RosarioNotifyAddress` es la dirección de email para recibir las notificaciones (nuevos administradores)
- `$RosarioLocales` es una lista separada por comas de nombres de locales (lenguajes, ver la carpeta `locale/` para la lista de locales)

#### Variables opcionales

- `$RosarioPath` es el camino completo hacia la instalación de RosarioSIS, se puede definir de manera estática o usando la constante mágica `__FILE__`
- `$wkhtmltopdfAssetsPath` es el camino para que wkhtmltopdf pueda acceder a la carpeta `assets/`, puede ser diferente de como el navegador la encuentra, una cadena vacía significa sin traducción
- `$StudentPicturesPath` camino hacia las fotos de los estudiantes
- `$UserPicturesPath` camino hacia las fotos de los usuarios
- `$PortalNotesFilesPath` camino hacia los archivos adjuntos a las notas del portal
- `$AssignmentsFilesPath` camino hacia los archivos de las tareas de los estudiantes
- `$FS_IconsPath` camino hacia los iconos del servicio de comida
- `$FileUploadsPath` camino hacia los archivos subidos
- `$LocalePath` camino en donde los lenguajes están almacenados. Se debe reiniciar Apache después de cada cambio.
- `$PNGQuantPath` camino hacia [PNGQuant](https://pngquant.org/) para la compresión de PNG.
- `$RosarioErrorsAddress` es la dirección de email para recibir los errores (PHP fatal, base de datos, intentos de pirateo)
- `$Timezone` define la zona horaria por defecto usada por la funciones de fecha y tiempo. Ver el [Listado de zonas horarias admitidas](http://php.net/manual/es/timezones.php).
- `$ETagCache` pasar a `false` para desactivar el [caché ETag](https://es.wikipedia.org/wiki/HTTP_ETag) y desactivar el caché de sesión "privada". Ver [Sesiones y seguridad](https://secure.php.net/manual/es/session.security.php).

  [Modo debug: añadir la linea siguiente para activarlo]
- `define( 'ROSARIO_DEBUG', true );`


Configuración de la base de datos
---------------------------------

Ahora, está listo para configurar la base de datos de RosarioSIS. Si tiene acceso al símbolo del sistema de su servidor, puede seguir estas instrucciones. Si usa una herramienta como phpPGAdmin o similar, importa el archivo `rosariosis.sql` incluido en este paquete.

1. Abra una ventana del símbolo del sistema.

2. Entra con el usuario postgres:
    `server$ sudo -i -u postgres`

3. Obtenga un símbolo PostgreSQL:
    `server$ psql`

4. Crea el usuario rosariosis:
    `postgres=# CREATE USER rosariosis_user WITH PASSWORD 'rosariosis_user_password';`

5. Crea la base de datos rosariosis:
    `postgres=# CREATE DATABASE rosariosis_db WITH ENCODING 'UTF8' OWNER rosariosis_user;`

6. Salga de PostgreSQL:
    `postgres=# \q` &
    `server$ exit`

7. Corra el archivo SQL de RosarioSIS:
    `server$ psql -f REPERTORIO_DE_INSTALACION/rosariosis.sql rosariosis_db rosariosis_user`

8. Corra el archivo SQL de traducción al español:
    `server$ psql -f REPERTORIO_DE_INSTALACION/rosariosis_es.sql rosariosis_db rosariosis_user`

También, el archivo [`pg_hba.conf`](http://www.postgresql.org/docs/current/static/auth-pg-hba-conf.html) puede ser editado para activar la conexión de usuarios con contraseña (`md5`):
```
# "local" is for Unix domain socket connections only
local   all             all                                     md5
```

Es todo!... ahora, apunte su navegador a: `http://sudominio.com/REPERTORIO_DE_INSTALACION/index.php`

y entra con el nombre de usuario 'admin' y la contraseña 'admin'. Con esta cuenta, podrá agregar nuevos usuarios, y modificar o suprimir los tres usuarios plantilla.


Problemas de instalación
------------------------

Para ayudarlo a detectar los problemas, apunte su navegador a: `http://sudominio.com/REPERTORIO_DE_INSTALACION/diagnostic.php`


Instalar las extensiones PHP
----------------------------

Instrucciones de instalación para Ubuntu 16.04:
    `server$ sudo apt-get install php-pgsql php-gettext php-mbstring php-gd php-curl php-xmlrpc php-xml`


Instalar otros lenguajes
------------------------

Instrucciones de instalación para Ubuntu 16.04 y la locale _Spanish_:
    `server$ sudo apt-get install language-pack-es`
Luego reinicia el servidor.


Instalar [wkhtmltopdf](http://wkhtmltopdf.org/)
-----------------------------------------------

Instrucciones de instalación para Ubuntu 16.04:
```
server$ wget https://downloads.wkhtmltopdf.org/0.12/0.12.5/wkhtmltox_0.12.5-1.xenial_amd64.deb
server$ sudo dpkg -i wkhtmltox_0.12.5-1.xenial_amd64.deb
```

Definir el camino en `config.inc.php`:

`$wkhtmltopdfPath = '/usr/local/bin/wkhtmltopdf';`


Activar la función PHP mail()
-----------------------------

Instrucciones de instalación para Ubuntu 16.04:
    `server$ sudo apt-get install sendmail`


Configuración adicional
-----------------------

[Guía de Configuración Rápida](https://www.rosariosis.org/es/quick-setup-guide/)
