# fnrus

Laravel 9 + JWT админка магазина Fnrus.

## Стек
- PHP 8.4, Laravel 9
- MariaDB 11.x (таблицы с префиксом `new_`)
- JWT (php-open-source-saver/jwt-auth)
- 2FA TOTP (RFC 6238, своя реализация без внешних зависимостей)

## Запуск локально
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan serve --port=8002
```

## Безопасность
См. [`docs/SECURITY.md`](docs/SECURITY.md) — полное описание реализованных мер
(2FA, brute-force protection, IP allow-list, security headers, audit logs).

## Тесты
```bash
vendor/bin/phpunit
```
