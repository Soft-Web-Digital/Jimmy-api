# Jimmy Xchange

[![Actions Status](https://github.com/Soft-Web-Digital/Jimmy-api/workflows/CI/badge.svg)](https://github.com/Soft-Web-Digital/Jimmy-api/actions)

Backend REST API on PHP (8.1) + Laravel(10) + MySQL(8.0).

## Quick Start to run locally
- Clone repository
- Run composer install
- copy .env.example file to .env and .env.testing
- Open .env/.env.testing and setup database connection
- Run `composer install`
- Run `php artisan key:generate`
- Run `php artisan migrate`
- Finally, run `php artisan serve`

## Running Tests

```
php artisan test
```

**Note:** Make sure you set up the test variables in the `.env.testing` file
