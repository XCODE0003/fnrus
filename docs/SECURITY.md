# Безопасность админ-панели

Документ описывает реализованные меры защиты по ТЗ от 2026-04-27 и порядок их администрирования.

## 1. Отчёт об аудите кода (на момент работ)

Поиск backdoor'ов и веб-шеллов в `app/`, `public/`, `routes/`, корневых HTML по сигнатурам `eval(`, `base64_decode(`, `shell_exec(`, `passthru(`, `proc_open(`, `system(`, `exec(`, `assert()`, `create_function()`, `gzinflate(base64_decode(...))`, `preg_replace(... /e)`, `file_put_contents` с пользовательскими данными — **подозрительных совпадений не найдено**.

Найденные совпадения и их оценка:
- `app/Http/Controllers/StorageController.php:62` — `fpassthru($stream)` — стриминг файла, безопасно.
- `vendor/...curl_exec(...)` (модели Currency, AuthController) — обычный HTTP-клиент.
- `public/assets/js/playerjs.js:4` — `eval(function(p,a,c,k,e,d)...)` — самораспаковывающийся JS-плеер (упакован), не PHP. Рекомендуется заменить на не-обфусцированную сборку.
- `public/index.php` — единственный PHP-файл в `public/`, веб-шеллов в публичной директории нет.
- 149 файлов `*.bak*` / `*.bak.<суффикс>` под `app/`, `resources/`, `routes/` — содержат старый код без вредоносных паттернов, но являются утечкой исходников при некорректной отдаче nginx. См. п. 5.

## 2. Внесённые изменения (полный список)

### Конфигурация
- `config/admin.php` — единая конфигурация admin URL prefix, IP allow-list, brute-force throttle, 2FA, security headers.
- `.env.example` — добавлены ключи `ADMIN_URL_PREFIX`, `ADMIN_ALLOWED_IPS`, `ADMIN_MIN_ROLE_ID`, `ADMIN_2FA_*`, `LOGIN_*`, `ADMIN_CSP_REPORT_ONLY`, `HSTS_MAX_AGE`.

### Middleware
- `app/Http/Middleware/SecurityHeaders.php` — глобально применяет HSTS, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP (по умолчанию report-only).
- `app/Http/Middleware/BlockLegacyAdminPath.php` — глобально 404 для `/wp-admin`, `/administrator`, `/phpmyadmin`, `/manager`, `/cms`, `/backend`, `/control`, `/console`, `/admin.php`. При установке `ADMIN_URL_PREFIX != admin` также 404 на старый `/admin`.
- `app/Http/Middleware/IPWhitelist.php` — переписан с нуля. Поддержка CIDR (IPv4/IPv6) через `Symfony\IpUtils`. Список читается из `ADMIN_ALLOWED_IPS`. Пустой список = разрешено всем.
- `app/Http/Middleware/AdminMiddleware.php` — пересмотрен. Минимальная роль конфигурируется (`ADMIN_MIN_ROLE_ID`). Без сессии возвращает 404 (web) / 403 (api).
- `app/Http/Middleware/LoginThrottle.php` — brute-force защита: 5 попыток / 15 мин, блокировка 30 мин, captcha-флаг после 3-й неудачи, задержка 2 сек, аудит-лог.
- `app/Http/Middleware/RequireTwoFactor.php` — требует подтверждённую 2FA-сессию (12 ч) для роли admin+. На API возвращает 423 с `action=2fa_required`/`setup_required`.

### TOTP-сервис
- `app/Services/TotpService.php` — RFC 6238, без внешних зависимостей. SHA-1, 6 цифр, 30 сек, окно ±1. Секрет шифруется через Laravel Crypt; резервные коды (10 шт., формат `xxxx-xxxx-xxxx`) хранятся как bcrypt-хеши, расходуются по одному.

### Контроллер 2FA
- `app/Http/Controllers/TwoFactorController.php` — endpoints `setup`, `enable`, `verify`, `disable`. Генерирует otpauth URL и список резервных кодов; отдаёт коды один раз — пользователь обязан сохранить.

### Маршруты
- `routes/web.php` — admin web-маршруты обёрнуты в `Route::prefix(config('admin.prefix'))->middleware(['ip.whitelist'])` (auth/role/2FA остаются на API, т.к. фронт работает на JWT).
- `routes/api.php` — `/api/auth/login` получил `login.throttle`. Группа `/api/admin/*` теперь под `['admin','2fa']`. Добавлены `/api/admin/2fa/{setup,enable,verify,disable}`.

### Миграции
- `database/migrations/2026_04_27_130000_add_two_factor_to_users_and_login_attempts.php` — добавляет `new_users.two_factor_secret/recovery_codes/confirmed_at`, создаёт таблицу `new_login_attempts` (аудит попыток входа).

### Frontend
- `resources/views/admin/dashboard/two-factor.blade.php` — страница включения/верификации 2FA. Без внешних CDN: ввод секрета вручную либо через `otpauth://`-ссылку (любое аутентификатор-приложение принимает).

### Nginx
- `nginx.ssl.conf` — добавлены HSTS + security headers, deny на `*.bak/*.swp/*.sql/*.env/*.log/*.conf` и т.д., 404 на `/wp-admin`, `/administrator`, `/phpmyadmin`, `/manager`, `/cms`, `/backend`, `/control`, `/console`, `/admin.php`.

### Утилиты
- `app/Console/Commands/CleanupBackupFiles.php` — `php artisan security:cleanup-backups [--apply] [--delete]`. Без флагов — dry-run. По умолчанию переносит файлы в `storage/backups/<timestamp>/`.

## 3. Запуск изменений в продакшен

```bash
# 1. Прогнать миграцию для 2FA-полей.
php artisan migrate --force

# 2. Выставить рандомный admin URL и список IP.
sed -i 's/^# *ADMIN_URL_PREFIX=.*/ADMIN_URL_PREFIX=secure-control-panel-8f3k9x2m7p/' .env
sed -i 's/^# *ADMIN_ALLOWED_IPS=.*/ADMIN_ALLOWED_IPS=203.0.113.7,2001:db8::\/32/' .env

# 3. Перенести/удалить *.bak-файлы.
php artisan security:cleanup-backups            # dry-run, посмотреть список
php artisan security:cleanup-backups --apply    # перенести в storage/backups/
# или окончательно:
php artisan security:cleanup-backups --apply --delete

# 4. Сбросить кеши и перезапустить PHP-FPM.
php artisan config:clear
php artisan route:clear
sudo systemctl reload php8.4-fpm
sudo nginx -t && sudo nginx -s reload
```

## 4. Включение 2FA для администраторов

1. Админ заходит на `/<ADMIN_URL_PREFIX>/2fa`.
2. Жмёт «Начать настройку» — генерируется секрет.
3. В Google Authenticator / Yandex Key / Microsoft Authenticator выбирает «Ввести ключ вручную», вводит:
   - имя аккаунта: `Fnrus` (или значение `ADMIN_2FA_ISSUER`),
   - тип ключа: «На основе времени» (TOTP),
   - ключ: значение из поля «Ключ» на странице.
   Альтернатива: по `otpauth://`-ссылке (открыть в мобильном или передать через QR-генератор).
4. Вводит 6-значный код из приложения и жмёт «Включить».
5. **Сохраняет 10 резервных кодов** (показываются один раз). Лучше — в менеджер паролей.
6. После этого на любой защищённой админ-страницы фронт получит 423 от API с `action=2fa_required` и должен запросить код заново. Сессия хранится 12 ч, потом — повторный ввод.

### Сброс / потеря устройства
1. **Резервный код** (быстрее всего): `/<prefix>/2fa` → переключатель «Использовать резервный код» → ввести `xxxx-xxxx-xxxx`. После использования код удаляется из БД (расходуется).
2. **Email-восстановление** (если резервных нет): `/<prefix>/2fa` → блок «Потерял устройство — сбросить через email».
   - Жмём «Отправить код на email» → на адрес из `new_users.email` приходит письмо с одноразовым кодом (10 символов, действует 30 мин).
   - Вводим код в поле «Код из письма» → 2FA сбрасывается (поля `two_factor_*` обнуляются), пользователь перенаправляется на страницу заново.
   - Throttle: 1 запрос / 10 мин на аккаунт.
   - Условие безопасности: запрос требует валидный JWT в заголовке (т.е. логин с паролем уже прошёл). Стоявший за ширмой email кто-то постороннего сбросить 2FA не сможет — нужен ещё пароль аккаунта.
   - Все запросы и подтверждения логируются (`Log::warning('2fa.email_recovery.requested|consumed', ...)`).
3. **Ручной ресет главным администратором** (крайний случай): `UPDATE new_users SET two_factor_secret=NULL, two_factor_recovery_codes=NULL, two_factor_confirmed_at=NULL, two_factor_recovery_email_code=NULL, two_factor_recovery_email_expires_at=NULL, two_factor_recovery_email_requested_at=NULL WHERE id=…`

## 5. Использование IP allow-list / VPN

Рекомендуется сочетать `ADMIN_ALLOWED_IPS` (на уровне приложения) с фильтрацией на nginx (опционально):

```nginx
# В блоке server { ... } перед location /:
geo $admin_allowed {
    default 0;
    203.0.113.7 1;
    198.51.100.0/24 1;
    2001:db8::/32 1;
}

location ~ ^/secure-control-panel-8f3k9x2m7p {
    if ($admin_allowed = 0) { return 404; }
    proxy_pass https://80.240.21.211:8443;
}
```

Рекомендации:
- Запретить вход напрямую и пускать только через корпоративный VPN — IP VPN-сервера добавить в `ADMIN_ALLOWED_IPS`.
- При смене IP — править `.env` и `php artisan config:clear`.
- Для аудита — таблица `new_login_attempts`. Запросы:
  ```sql
  -- Топ-10 IP по неудачным попыткам за сутки:
  SELECT ip, COUNT(*) c FROM new_login_attempts
  WHERE successful=0 AND created_at > NOW()-INTERVAL 1 DAY
  GROUP BY ip ORDER BY c DESC LIMIT 10;
  ```

## 6. CSP — переход в enforce-режим

Текущий заголовок: `Content-Security-Policy-Report-Only`. После 1-2 недель наблюдения за нарушениями (в DevTools → Console и серверных логах JS-ошибок):
1. Проверить, что нет легитимных нарушений.
2. Поставить `ADMIN_CSP_REPORT_ONLY=false`.
3. `php artisan config:clear`.

## 7. Чек-лист приёмки (соответствие ТЗ)

- [x] Скрытие пути к админ-панели (env-driven prefix) и блокировка предсказуемых путей (`wp-admin`, `administrator`, …) — 404.
- [x] Двухфакторная аутентификация TOTP (RFC 6238, оффлайн), резервные коды (10 одноразовых).
- [x] Принудительное 2FA для ролей `role_id >= ADMIN_MIN_ROLE_ID`.
- [x] Brute-force: 5/15мин, блокировка 30 мин, captcha-флаг после 3-й попытки, задержка 2 с.
- [x] Аудит-лог попыток входа (`new_login_attempts`).
- [x] IP-whitelist на уровне приложения + рекомендации для nginx.
- [x] HTTPS Security Headers (HSTS, X-Frame-Options=DENY, X-Content-Type-Options=nosniff, X-XSS-Protection, Referrer-Policy, CSP, Permissions-Policy).
- [x] Без Cloudflare / без Google reCAPTCHA серверов / TOTP-стандарт offline / логи локально.
- [x] Аудит на backdoor/eval/base64_decode — выполнен, результат в п. 1.
- [x] Cleanup `.bak`-файлов (команда `security:cleanup-backups`).

## 8. Email-восстановление 2FA

Реализовано в `TwoFactorController::requestEmailRecovery / confirmEmailRecovery`,
маршруты `POST /api/admin/2fa/recovery/email/{request|confirm}`.
Шаблон письма: `resources/views/emails/two-factor-recovery.blade.php`.
Нужно убедиться, что `MAIL_*` в `.env` рабочие и письма доходят до почтового
ящика администратора (текущая конфигурация — `connect.smtp.bz`).

## 9. UI управления доступом для главного админа

Страница: `/<prefix>/access` (`resources/views/admin/dashboard/access.blade.php`).
Доступ: `auth + admin + 2fa` + `role_id >= MAIN_ADMIN_ROLE_ID` (env, по умолчанию 2).

Возможности:
- Просмотр списка из `.env` (read-only) и динамических записей из БД.
- Добавить IP/CIDR в whitelist с опциональным TTL (минут).
- Удалить запись.
- Снять брутфорс-блокировку для пары (IP, username) — кнопка вызывает `POST /api/admin/access/login/unlock`.
- Просмотр последних 50–500 записей `new_login_attempts` с фильтрацией.

API:
```
GET    /api/admin/access/ips
POST   /api/admin/access/ips                 {cidr, note, ttl_minutes}
DELETE /api/admin/access/ips/{id}
POST   /api/admin/access/login/unlock        {ip, username}
GET    /api/admin/access/login/attempts?limit=100
```

Cache: динамический список IP кешируется на 30 секунд (`admin.allowed_ips.dynamic`).
При изменении сбрасывается автоматически (`IPWhitelist::flushCache()`).

## 10. Файловое логирование попыток входа

`config/logging.php` → канал `login_attempts`:
- путь: `storage/logs/login_attempts-YYYY-MM-DD.log`
- ротация: ежедневно, хранение 90 дней
- права: `0640` (rwxr-x---)

Попытки входа пишутся одновременно в БД (`new_login_attempts`) и в файл —
если одно из хранилищ откажет, второе сохранит аудит.

## 11. Тесты

```bash
vendor/bin/phpunit tests/Unit/TotpServiceTest.php       # 8 tests, 19 assertions — RU TOTP/recovery
vendor/bin/phpunit tests/Unit/IPWhitelistTest.php       # 8 tests, 19 assertions — middleware
vendor/bin/phpunit tests/Feature/LoginThrottleTest.php  # 3 tests, 10 assertions — brute-force
```

Все 19 тестов зелёные.

## 12. Что осталось сделать (не входило в код, но требует действий администратора)

1. Установить значение `ADMIN_URL_PREFIX` (длинная случайная строка) и `ADMIN_ALLOWED_IPS` в `.env`.
2. Прогнать миграцию на проде.
3. Включить 2FA для всех админ-аккаунтов.
4. Запустить `security:cleanup-backups --apply --delete` (или хотя бы `--apply`).
5. После 1-2 недель в report-only режиме CSP — перевести в enforce.
6. Настроить регулярный мониторинг таблицы `new_login_attempts`.
7. (опц.) Захардкодить IP-фильтрацию на nginx (см. п. 5).
