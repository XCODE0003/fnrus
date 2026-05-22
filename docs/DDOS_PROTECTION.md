# Защита от DDoS и оптимизация — рекомендации (ТЗ §5–§6)

Этот документ — рекомендации по пунктам ТЗ 5 (оптимизация) и 6 (защита от
DDoS). Сами по себе это инфраструктурные задачи: их нельзя полноценно решить
кодом приложения, нужна настройка сервера. Ниже — конкретный план.

## 1. Защита от DDoS (§6)

Приоритет — решения, работающие в РФ.

### 1.1. Внешний слой (рекомендуется)
- **Cloudflare** (бесплатный план уже даёт L3/L4-защиту и базовый WAF) —
  работает в РФ. Включить «Under Attack Mode» при атаке.
- Альтернативы для РФ: **DDoS-Guard**, **StormWall**, **Selectel/Servicepipe** —
  если Cloudflare нежелателен.
- Спрятать реальный IP сервера (firewall: принимать 80/443 только с IP
  прокси-защиты).

### 1.2. Уровень nginx (встроенные средства сервера)
- Rate limiting:
  ```nginx
  limit_req_zone $binary_remote_addr zone=site:10m rate=20r/s;
  limit_conn_zone $binary_remote_addr zone=conn:10m;
  server {
      limit_req zone=site burst=40 nodelay;
      limit_conn conn 20;
  }
  ```
- Отдельный, более строгий лимит на тяжёлые/публичные POST-эндпоинты:
  `/api/search`, `/api/cheats/search`, `/api/reviews/submit`, формы логина.
- `client_max_body_size` разумно ограничить, таймауты (`client_body_timeout`,
  `send_timeout`) снизить.

### 1.3. fail2ban
- Джейл на повторные 4xx/429 и на брутфорс логина (лог Laravel / nginx
  access log) — бан IP на уровне iptables.

### 1.4. Уровень приложения
- Laravel `throttle` middleware на публичных роутах. Сейчас публичные:
  `POST /api/search`, `POST /api/cheats/search`, `POST /api/reviews/submit`.
  Рекомендуется обернуть их в `->middleware('throttle:30,1')`.
- Капча на форме отзыва и регистрации (hCaptcha уже интегрирована —
  `CAPTCHA_ENABLED`), включить в проде.

## 2. Оптимизация (§5)

### Уже сделано в коде
- `loading="lazy"` на карточках каталога и аватарах отзывов.
- Иконочные фоновые картинки оставлены, тяжёлые декоративные фоны убраны.
- JivoChat подгружается лениво (`requestIdleCallback`).
- `scrollbar-gutter: stable` — нет дёрганья layout.

### Рекомендации по серверу
- Включить HTTP/2, gzip/brotli для css/js/svg.
- Кеш-заголовки на `/assets/*` (`Cache-Control: public, max-age=31536000,
  immutable`) — версии файлов уже бьются через `?v=`.
- Минифицировать `style.min.css` и `scripts.min.js` на проде (сейчас
  `style.min.css` фактически не минифицирован — ~11k строк).
- `php artisan config:cache route:cache view:cache` на проде.
- OPcache включить в `php.ini`.
- Конвертировать крупные PNG в WebP, отдавать через `<picture>`.
- Индексы БД на часто фильтруемых полях (`new_categories.cid`,
  `new_products.cid`, `new_materials.pid/tid/status`, `new_orders.hash`).

## 3. Что требует доступа к серверу
Пункты 1.1–1.3 и серверная часть раздела 2 выполняются на хостинге/VPS и
согласовываются отдельно — у приложения нет доступа к конфигурации nginx,
firewall и php.ini.
