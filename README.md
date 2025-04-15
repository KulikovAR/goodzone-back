# Goodzone Backend

- demo: https://example-app.pisateli-studio/

## Dev environment

- run `cp .env.example .env`
- run `docker-compose up -d --build`
- run `docker-compose exec app php artisan migrate`
- run `docker-compose exec app php artisan l5-swagger:generate`

## Tests

- run `php artisan test`
- with coverage `XDEBUG_MODE=coverage php artisan test --coverage-html tests/reports/coverage`

## Api Docs

[https://example-app.pisateli-studio/api/documentation](https://example-app.pisateli-studio/documentation)
