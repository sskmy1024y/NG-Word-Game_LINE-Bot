# Word Wolf LineBot

## How to setup
```bash
$ composer install
$ touch database/database.sqlite
$ cp .env.example .env
$ php artisan key:generate
$ php artisan migrate
```

## Run
```bash
$ ./ngrok http 8000
```
