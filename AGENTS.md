# fnrus

Laravel + JWT интернет-магазин читов (storefront + Filament-админка).

## Текущая задача
Редизайн витрины (storefront). Новый дизайн внедряется **поблочно**: пользователь
присылает скрин блока + ресурсы (иконки, картинки), задача — сверстать **один в один**
с аккуратной, чистой вёрсткой. Пиксель-перфект обязателен.

## Стек
- PHP 8.1+, Laravel 10 (`laravel/framework: ^10.0` в composer.json — README устарел)
- MariaDB 11.x, таблицы с префиксом `new_`
- JWT (`php-open-source-saver/jwt-auth`), 2FA TOTP (своя реализация)
- Filament 3 — админка (`app/Filament/`, ветка `filament-migration`)
- Vite заявлен, но для витрины фактически не используется (`resources/css/app.css` пустой)

## Структура витрины (то, что редизайним)
- **Шаблоны:** `resources/views/user/` — Blade. Страницы наследуют
  `resources/views/user/layouts/main.blade.php` через `@extends` + `@section('content')`.
  - `index.blade.php` — главная; `templates/header.blade.php`, `templates/footer.blade.php`;
    `partials/`, `components/`.
- **CSS:** `public/assets/css/style.min.css` — единственный источник стилей витрины.
  Несмотря на имя, файл **не минифицирован** (~8600 строк, читаемый) и правится руками.
  Подключается в `main.blade.php` через `<link href="/assets/css/style.min.css?v=...">`.
- **JS:** `public/assets/js/scripts.min.js` + либы из `public/assets/libs/`
  (Swiper, simplebar, GSAP, jQuery, MaskedInput, Smooth).
- **Иконки / картинки:** `public/assets/img/` (~120 файлов, svg + png).
  Новые ресурсы от пользователя кладём сюда.
- **Шрифты:** Mazzard H / Mazzard M, локально в `public/assets/fonts/Mazzard/`
  (eot/woff/woff2/ttf), `@font-face` в начале `style.min.css`.

## Конвенции вёрстки
- Именование классов — BEM (`block__element`, `block__element--modifier`),
  напр. `main-section1__inner`, `section-category__icon`.
- Тексты — через i18n: `{{ __('site.ключ') }}` / `{!! __('site.ключ') !!}`.
  Ключи в `lang/ru/site.php` и `lang/en/site.php` — при добавлении текста заводить в обоих.
- При новом блоке: разметка в нужный `.blade.php`, стили дописывать в `style.min.css`,
  бампать `?v=` у `<link>`/`<script>` в `main.blade.php` для сброса кеша.

## Передача дизайна из Figma
- Подключён Figma desktop MCP-сервер (`.mcp.json`, `figma-desktop`,
  `http://127.0.0.1:3845/mcp`). Требует запущенного Figma desktop с включённым
  Dev Mode MCP server. Через него читаются точные размеры/цвета/шрифты выделенного фрейма.
- Пользователь дополнительно присылает по каждому блоку 3 вещи: скрин 2x,
  спеку из Dev Mode, ассеты (SVG-иконки, PNG/WebP-картинки).

## Релевантные скиллы для редизайна
- `frontend-design:frontend-design` — продакшн-вёрстка интерфейсов.
- `gsap-core` — анимации (GSAP уже подключён в `public/assets/libs/gsap`).
- `pr-review-toolkit:*` / `/review` — ревью перед коммитом.
- Vue/React-скиллы **не применимы** — витрина на Blade + jQuery.

## Запуск
```bash
composer install
php artisan serve --port=8002
```

## Прочее
- Безопасность: `docs/SECURITY.md`.
- Тесты: `vendor/bin/phpunit`.
