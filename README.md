[![Tests](https://github.com/stefandenijs/php-symfony-task-manager/actions/workflows/symfony-tests.yml/badge.svg?branch=main)](https://github.com/stefandenijs/php-symfony-task-manager/actions/workflows/symfony-tests.yml)

# Symfony Task Manager

**Symfony Task Manager** is a backend API for managing tasks, subtasks, tags, and task lists.  
Users can create, manage, and organize their tasks to efficiently plan their days.

This project was built as a solo effort to learn Symfony from scratch, and to challenge myself to design a complete, tested, and secure API.

---

## 🚀 Tech Stack

- **Framework**: Symfony 7.2
- **Language**: PHP 8.2
- **Database**: PostgreSQL (via Docker)
- **Authentication**: JWT (LexikJWTAuthenticationBundle)
- **Testing**: PHPUnit (76 tests, 223 assertions)
- **Continuous Integration**: GitHub Actions (automated test runs on push/PR)
- **Documentation**: NelmioApiDocBundle (OpenAPI 3)

---

## 📦 Prerequisites

- PHP 8.2 (XAMPP or native installation)
- Composer
- Symfony CLI
- Docker & Docker Compose
- PostgreSQL driver (pdo_pgsql) and other PHP extensions

The following PHP modules were active during development:

<details>
<summary>PHP Modules List</summary>

```text
bcmath, bz2, calendar, Core, ctype, curl, date, dom, exif, fileinfo, filter, ftp,
gettext, hash, iconv, json, libxml, mbstring, mysqli, mysqlnd, openssl, pcre, 
PDO, pdo_mysql, pdo_pgsql, pdo_sqlite, Phar, random, readline, Reflection, 
session, SimpleXML, sodium, SPL, standard, tokenizer, xdebug, xml, xmlreader, 
xmlwriter, zlib

[Zend Modules]
Xdebug
```
</details>

## ⚙️ Running the Project Locally

A few steps are required to be able to run the project, this includes setting up the database, running migrations, and starting the webserver:

1. Start the database container:
```
docker compose up -d
```
2. Install the required PHP packages:
```
composer install
```
3. Run the database migrations:
```
php bin/console doctrine:migrations:migrate
```
4. Start the Symfony local server:
```
symfony serve
```

## 🧪 Testing
* The project contains 76 tests and 223 assertions.
* Tests are run against a dedicated PostgreSQL test database — no mocking is used.
* GitHub Actions automatically runs the full test suite on every push or pull request to the main branch.

To run tests locally:
```
php bin/phpunit
```

## 📖 API Documentation
* Full API documentation is available using NelmioApiDocBundle (OpenAPI 3).
* After running the server, access the documentation at:
```
http://127.0.0.1:8000/api/doc
```
