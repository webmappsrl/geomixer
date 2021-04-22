# Geomixer

Webmapp code for the Geomixer GEOBOX component. This software is developed and mantained by WEBMAPP TEAM (see authors).
Please fill free to contact us (info@webmapp.it) for any question.

## 1 Getting Started

### 1.1 Prerequisites

To develop this project make sure you have:

- php 7.4.x (built on 7.4.14)
- composer 2.0.11
- git
- PostgreSQL 13.1
- npm LTS (built on 12.x)
- Cypress v7.x

### 1.2 Installation

To install the project you will need to:

- clone the repository

`git clone git@github.com:webmappsrl/geomixer.git`

- install the dependencies

`composer install`

- create the local database

`createdb [database_name]`

- configure the project environment:
    - `cp .env.example .env`
    - set the local database configuration (the `DB_*` variables)
    - set the HOQU variables (`HOQU_*`)
    - set the GEOHUB variables (`GEOHUB_*`)

- run the migrations

`php artisan migrate`

- run the project in a local environment

`php artisan serve`

If you have Valet installed then you can skip the last step and configure Valet instead

## 2 Tests

### 2.1 Unit tests

#### 2.1.1 Configuring the environment

To run the test is advisable to create a testing environment. The easiest way is to copy the .env file in a new testing

`cp .env .env.testing`

In the new environment change the database name and create the new testing database. Then run

`php artisan migrate --env=testing`

#### 2.1.2 Run the tests

To run the test simply run the command

`php artisan test`

### 2.2 End-to-end tests

#### 2.1.1 Configuring the environment

The end-to-end tests uses Cypress which can be configured changing the cypress.json file in the root directory.

#### 2.1.2 Run the tests

To run the end-to-end tests simply run

`npx cypress open`

and run all the tests from the Cypress GUI

## 3 Development

All the development work must be done in the develop branch following the GitFlow Workflow

## 4 Built With

- MacOS MacOS Big Sur 11.2.x
- [Laravel](https://laravel.com)
- [Laravel Nova](https://nova.laravel.com)

## 5 Contributing

To contribute to this project please contact one of the [Authors](#7-authors)

## 6 Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see
the [tags on this repository](https://github.com/webmappsrl/wm-app/tags).

## 7 Authors

- **Alessio Piccioli** - _CTO_ - [Webmapp](https://github.com/piccioli)
- **Antonella Puglia** - _UX designer_ - [Webmapp](https://github.com/antonellapuglia)
- **Davide Pizzato** - _App developer_ - [Webmapp](https://github.com/dvdpzzt-webmapp)
- **Marco Barbieri** - _Map maker_ - [Webmapp](https://github.com/marchile)
- **Pedram Katanchi** - _Web developer_ - [Webmapp](https://github.com/padramkat)

See also the list of [contributors](https://github.com/webmappsrl/wm-app/graphs/contributors) who participated in this
project.

## 8 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
