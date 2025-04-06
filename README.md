# Symfony Task Manager

The following tool is an example backend for a task managing system. The basic concept is that a user can create and manage tasks to efficiently plan their days.

## Prerequisites

PHP installed, for this project I used XAMMP for PHP and other plugins.

## Running the project

A few steps are required to be able to run the project, this includes setting up the database, running migrations and starting the webserver:

1. Run inside the root folder: `docker compose up -d`. This will start up the docker container that will host our task manager database via Postgress.
2. To install required packages: `composer install`
3. To run the migrations: `php bin/console doctrine:migrations:migrate`
4. Start the webserver with: `Symfony serve` (Runs locally on `IP:PORT 127.0.0.1:8000`)
