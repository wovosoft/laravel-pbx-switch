# Laravel PBX Switch

## Introduction

This project is a PBX (Private Branch Exchange) system built on the Laravel framework. It uses FreeSWITCH to handle the telephony services.

## Features

*   User management
*   Call routing
*   Voicemail
*   And more!

## Requirements

*   PHP 8.2 or higher
*   Composer
*   Node.js
*   NPM
*   FreeSWITCH

## Installation

1.  Clone the repository: `git clone https://github.com/wovosoft/laravel-pbx.git`
2.  Install PHP dependencies: `composer install`
3.  Install Node.js dependencies: `npm install`
4.  Build assets: `npm run build`
5.  Copy `.env.example` to `.env` and configure your environment variables.
6.  Generate an application key: `php artisan key:generate`
7.  Run database migrations: `php artisan migrate`

## Configuration

1.  Configure your database in `.env`.
2.  Configure FreeSWITCH connection details in `.env`.

## Usage

Start the development server:

```bash
php artisan serve
```

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is licensed under the MIT License.
