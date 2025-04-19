# Symfony Task Manager

The following tool is an example backend for a task managing system. The basic concept is that a user can create and manage tasks to efficiently plan their days.

## Prerequisites
* Symfony 7.2
* PHP 8.2 installed, for this project I used XAMPP for PHP and other plugins.

The following modules were active during the development of this backend:
````
[PHP Modules]
bcmath
bz2
calendar
Core
ctype
curl
date
dom
exif
fileinfo
filter
ftp
gettext
hash
iconv
json
libxml
mbstring
mysqli
mysqlnd
openssl
pcre
PDO
pdo_mysql
pdo_pgsql # Required for connecting with the local postgress database.
pdo_sqlite
Phar
random
readline
Reflection
session
SimpleXML
sodium
SPL
standard
tokenizer
xdebug
xml
xmlreader
xmlwriter
zlib

[Zend Modules]
Xdebug
````

## Running the project

A few steps are required to be able to run the project, this includes setting up the database, running migrations and starting the webserver:

1. Run inside the root folder: `docker compose up -d`. This will start up the docker container that will host our task manager database via Postgress.
2. To install required packages: `composer install`
3. To run the migrations: `php bin/console doctrine:migrations:migrate`
4. Start the webserver with: `symfony serve` (Runs locally on `IP:PORT 127.0.0.1:8000`)
