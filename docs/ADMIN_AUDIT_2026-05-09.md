# Admin Panel Audit — 2026-05-09

Read-only audit. No code was modified. Findings are grouped by area and prioritized **CRITICAL / HIGH / MEDIUM / LOW**. File and line references are included so each finding can be acted on directly.

Auditor note: every CRITICAL claim below was verified by reading the source. Some HIGH/MEDIUM findings are derived from agent exploration and should be re-read before fixing.

---

## 1. Access perimeter (routes, middleware, 2FA, JWT)

### CRITICAL

**A1. `AdminAccessController::guard()` does not enforce super-admin.**
`app/Http/Controllers/AdminAccessController.php:132` — IP-whitelist endpoints are protected only by `role_id >= MAIN_ADMIN_ROLE_ID` (default 2). Any regular admin can edit the IP allowlist, including locking out other admins.
*Fix:* require explicit super-admin role (3) for IP-whitelist mutations.

**A2. 2FA email-recovery resets the secret without TOTP confirmation.**
`app/Http/Controllers/TwoFactorController.php:193-228` — `confirmEmailRecovery()` clears `two_factor_secret` after only the email code. Anyone who controls the admin's email mailbox can disable 2FA.
*Fix:* require either (a) password re-entry plus current TOTP, or (b) a second factor (e.g. backup-code prompt) before clearing the secret.

### HIGH

**A3. JWT is not invalidated on logout.**
`app/Http/Controllers/AuthController.php:366-384` clears session keys; `AdminWebGuard::reauthenticateViaJwt()` (`app/Http/Middleware/AdminWebGuard.php:59-84`) will then re-bootstrap a session from the still-valid `session_token` cookie. Window: until JWT TTL (currently 15 days).
*Fix:* set `session_token` cookie expired in the logout response **and** add a server-side revocation list (e.g. `jwt_revoked_at` per user, or a token-id blacklist).

**A4. `2fa_passed_at` grace window is 12 hours with no activity log.**
`app/Http/Middleware/RequireTwoFactor.php:20-78` — once verified, no re-prompt for 12 hours. Combined with A3, a stolen JWT yields up to a 12-hour exploitation window with no audit trail.
*Fix:* shorten to ≤2 hours; add a Log entry on every miss/hit; require fresh TOTP for sensitive actions (IP-whitelist, role changes, 2FA disable).

**A5. `admin/2fa/disable` does not require TOTP.**
`routes/api.php:437-445` — `disable` is behind `['auth','admin']` only, not `'2fa'`. Inside the 12-hour grace, an attacker with the cookie can disable 2FA without proving possession of the device.
*Fix:* require TOTP for the `disable` route (parity with `enable`).

**A6. `POST /api/admin/2fa/verify` has no rate limit.**
`routes/api.php:440` — login throttling does not extend here. 6-digit TOTP = 1M brute-force space; the current rotation window allows hundreds of attempts before the code rotates.
*Fix:* attach `'login.throttle'` or a stricter custom throttle (e.g. 5/minute, 30/hour per user+IP).

### MEDIUM

**A7. Sidebar API `GET /api/sidebar` is reachable without 2FA.**
`routes/api.php:65-67` leaks the admin menu structure to anyone with a valid JWT but no 2FA.

**A8. 2FA failures return HTTP 423 (Locked).** `RequireTwoFactor.php:74` — non-standard for SPAs; 401/403 is more conventional and easier to handle on the client.

**A9. CORS for the file-upload subdomain is not strictly verified.**
`routes/api.php:42-46` — `domain('fnrus.com')` is a routing constraint, not a CORS check. Verify that the CORS middleware does explicit Origin allow-listing.

**A10. `IPWhitelist` middleware trusts `request->ip()`.**
`app/Http/Middleware/IPWhitelist.php:40-42` — relies on TrustProxies being configured for *every* hop. Operationally fragile. Document the proxy chain in deploy notes.

### LOW

**A11. No audit log for 2FA enable/disable/verify operations.**

---

## 2. Controller logic

### CRITICAL

**C1. Inverted validation guard in `AdminController::create/login`.**
`app/Http/Controllers/AdminController.php:24-25,50-51` — `if($request->filled($username)) {return error}` uses the *value* of `$username` as a field key. The condition is effectively dead: it never returns the "No username" / "No password" error.

```php
// current — wrong: $username is the value, not a key
if($request->filled($username)){return response()->json(['ok' => false, 'description' => 'No username!'], 200);}

// intended
if(!$request->filled('username')){return response()->json(['ok' => false, 'description' => 'No username!'], 200);}
```

The previous `$request->validate([...])` already throws on missing fields, so the guards are redundant — easiest fix is to delete lines 24-25 and 50-51 outright.

**C2. `AdminController::login` returns no body on bad credentials.**
`app/Http/Controllers/AdminController.php:55-59` — only the success branch has a `return`. A failed `Hash::check` falls through to the end of the function and returns HTTP 200 with an empty body. The frontend can't distinguish "wrong password" from "successful empty response".
*Fix:* add an explicit `{ok: false, description: 'Invalid credentials'}` return at the end.

**C3. Same inverted-guard bug in `MemberController::delete`.**
`app/Http/Controllers/MemberController.php:699` — `if($request->filled($request->id)){throw new Exception('ID not found!');}` — guard never triggers regardless of input. Note: this does **not** delete random users (line 704 still requires a matching id), but the explicit "missing id" guard is dead.
*Fix:* `if(!$request->filled('id')) throw new Exception('ID not found!');`

> Suggestion: grep the codebase for `$request->filled($request->` — same anti-pattern is likely repeated.

### HIGH

**C4. N+1 in `OrderController::all`.**
`app/Http/Controllers/OrderController.php:932-963` — per-row calls to `Member::getByID`, `Product::where`, `HackStatus::getByID`. Replace with eager loading (`->with(['member','product.hackStatus'])`) or one batched fetch.

**C5. `StatusCheatController::create/update` reads request input by property access after `validate()`.**
`app/Http/Controllers/StatusCheatController.php:162-204` — `$request->validated()` (or typed FormRequest casts) is safer than `$request->title`.

**C6. Coupon usage counter has no row lock.**
`CouponController` — `count_uses` is read-modify-written without `DB::transaction` + `lockForUpdate`. Two parallel redemptions can both see the last available use.

**C7. Status-change Telegram broadcast runs synchronously.**
`StatusCheatController.php:269-286` — calls `SenderController::create` from within an HTTP request. A slow/failing Telegram API will stall the admin response.
*Fix:* dispatch a queued Job (`dispatch(new BroadcastStatusChange(...))`).

**C8. `MemberController::update_balance` lacks transactional row lock.**
`MemberController.php:744-801` — balance read and write without `lockForUpdate`. Concurrent ops can diverge.

### MEDIUM

**C9. Status-text translation is duplicated** in `StatusCheatController.php:240-251`, `routes/web.php:268-279`, and `OrderController`. Centralize in a single class/enum.

**C10. No logging on destructive admin actions** (member delete, order purge, balance adjust). Admins should leave an audit trail.

**C11. Magic numbers everywhere** (`status = 1..3`, `role_id >= 2`, `is_endless = 0/1`). Promote to constants/enums.

---

## 3. UI / Blade views (admin/dashboard/*)

### CRITICAL

**V1. AJAX requests omit CSRF token.**
e.g. `resources/views/admin/dashboard/access.blade.php:135` — `fetch('/api/admin/ips/' + id, {method:'DELETE'})` with no `X-CSRF-TOKEN`. Mitigated only by JWT-bearer auth; if CSRF middleware is dropped and session cookie auth is reactivated, this becomes exploitable.

**V2. Inline `onclick="fn('+e.id+')"` patterns in `public/assets/js/main.js`.** Even when ids are integers today, this pattern invites XSS the moment a string field is templated in. Switch to `addEventListener` + `data-id` attributes.

**V3. Destructive operations gated only by `window.confirm()`.** Browser confirm is easy to ignore and not styled. Replace with explicit modal confirmations for deletes / resets / cleanups (especially for the new "сброс аналитики" / "массовая очистка" features in the upcoming blocks).

### HIGH

**V4. Hardcoded Russian strings throughout views and JS** (~80 occurrences). Move to `lang/ru/*.php`. Required if i18n is on the roadmap.

**V5. Missing `alt` attributes on `<img>`** in `reviews.blade.php`, `settings/token.blade.php`, etc.

**V6. jQuery 3.4.1 (2019) shipped in `public/assets/jquery/`.** Known prototype-pollution and selector XSS issues. Bump to 3.7+ or migrate to vanilla.

### MEDIUM

**V7. Duplicate DOM ids across modals.** `resources/views/admin/dashboard/statuses.blade.php:30-91` reuses `id="title"`, `id="game_id"`, `id="status"` in both `#createCheat` and `#changeCheat`. Querying by id picks the first match, producing silent UI bugs.

**V8. Inline styles in 70+ places.** Move to CSS classes for consistency and easier theming.

**V9. Missing `<label for>` / `aria-label` for dynamic inputs** in `access.blade.php`.

### LOW

**V10. `display:none` used heavily instead of conditional rendering.** Acceptable for a Blade-only stack; revisit if migrating to a SPA framework.

---

## 4. Data layer & operational readiness

### Models present (relevant)

| Model | Table | Key fields | Soft-deletes | `$guarded=[]` |
|-------|-------|------------|:---:|:---:|
| User / Member | users | tid, sid, email, password, balance_main, role_id, is_ban | ❌ | ❌ |
| Order | orders | sid, pid, tid, bid, status, hash, payment_at, expired_at | ❌ | ❌ |
| Product | products | sid, cid, count_sales, count_all, count_views | ❌ | ❌ |
| Coupon | coupons | sid, code, sale, count_uses_max | ❌ | ❌ |
| CouponUse | coupons_uses | promo_id, chat_id, shop_id | ❌ | ❌ |
| Material | materials | pid, tid, oid, status (1/2/4) | ❌ | ❌ |
| ChannelSub | channels_sub | sid, title, link, is_active | ❌ | ❌ |
| ShopSettings | shops_settings | booking_time, ref_percent, tg_notify_* | ❌ | ❌ |

### Missing infrastructure (relative to the new TZ blocks)

| Need | Status | Note |
|------|:--:|------|
| Queue driver | ⚠️ `sync` | Email blasts and Telegram posts will block requests. Move to `database` or `redis`. |
| Mail driver (prod) | ⚠️ Dev only | `.env.example` points at `mailhog`. Production SMTP credentials still required. |
| `Mailable` classes | ❌ | None in `app/Mail/`. Needed for the Email-broadcast block. |
| Soft-deletes | ❌ | "Mass cleanup" will permanently destroy rows. Add `deleted_at` or an `is_deleted` flag where reversibility matters. |
| Foreign keys / cascades | ❌ | Migrations use raw `DB::statement`. Order deletion will leave orphan `materials`, `coupons_uses`, export rows. |
| Telegram channels model | ❌ | Token + chat_id live in `ShopSettings` / `Order.php` (`Crypt`). For the "list of channels with quick toggle" UX, we need a `telegram_channels` table. |
| Statistics table | ❌ | Counters live on rows (`count_sales`, `count_views`, …); "сброс аналитики" will need targeted update statements per counter. |
| Console commands | ⚠️ Minimal | Only `CleanupBackupFiles`. New commands needed: `admin:reset-stats`, `admin:cleanup`, `admin:force-password-reset`. |

### MEDIUM

**D1. `User.password` is in `$fillable`** without an attribute mutator. Any future `User::update($request->all())` is a takeover risk. Either add a mutator that hashes, or remove `password` from `$fillable` and require explicit assignment.

**D2. `coupons_uses` has no primary key.** Mass cleanup will full-scan. Add a synthetic PK.

**D3. Telegram token via `Crypt::decryptString` per request.** Cache the decoded token (memoized + invalidated on settings save).

---

## 5. Status-софта tab — current vs. target

**Today (`resources/views/admin/dashboard/statuses.blade.php`, `StatusCheatController`):**
- Four statuses: undetected (0), recommended (1), not-recommended (2), updating (3).
- CRUD over title + status + game_id only.
- Telegram broadcast on change is fire-and-forget through `SenderController`, synchronous, single hardcoded sender.
- Email notification to users exists in code but is currently disabled (`StatusCheatController.php:257-267`).

**Target (per new TZ):**
- Editable message **template** (per-status or per-event).
- WYSIWYG editor (TinyMCE chosen).
- Auto-substituted placeholders: `{game}`, `{product}`, `{status}` (names TBD).
- Free text + emoji.
- Image upload, posted with the message.
- List of connected Telegram channels/groups with per-channel quick on/off toggle.
- (Recommended additions, not in TZ but cheap: scheduled posting, dry-run preview, post history.)

**Gap summary for the next session:**

1. New table `notification_templates` (or one row per status in `shops_settings`) for the edited message body.
2. New table `telegram_channels` (id, sid, title, chat_id, is_active, sort_order).
3. Mailable / Notification refactor not strictly required for this block — Telegram-only.
4. Move broadcast onto a queued Job; switch `QUEUE_CONNECTION` to `database` first.
5. Add an upload endpoint for the post image (size/MIME validation, storage in `storage/app/public/status-posts/`).
6. WYSIWYG: include TinyMCE community core via CDN or local copy; sanitize HTML server-side before sending to Telegram (Telegram needs a constrained HTML/Markdown subset).
7. Frontend: rebuild `statuses.blade.php` modal — fix duplicate DOM ids (V7) while we're in there.

---

## 6. Recommended fix order across blocks

| # | Block | Why this order |
|---|-------|----------------|
| 1 | C1, C2, C3 (inverted-guard bugs) | Trivial, isolated, ship as one fix-PR before any feature work. |
| 2 | A2, A3, A5 (2FA holes) | Real exposure given the recent 2FA work. Small surface. |
| 3 | Status-софта block (TZ #1) | Largest feature; needs queue + new tables; biggest user value. |
| 4 | Sброс аналитики + массовые очистки (TZ #3-#4) | Touches data layer; do after we understand the cleanup blast radius. |
| 5 | Email-рассылки (TZ #5) | Depends on Mailable + queue work that #4 unlocks. |
| 6 | Принудительный сброс пароля (TZ #8) | Small; fold into the same security PR as the 2FA fixes if timing allows. |
| 7 | UI consistency pass (V1-V9) | Best done after each feature ships, not as one giant rewrite. |

---

## 7. Out of scope for this audit

- Performance benchmarking under load.
- Penetration testing of the 2FA email-recovery flow.
- Frontend accessibility against WCAG 2.1 AA checklist (only spot-checks here).
- Review of all non-admin controllers (only those reachable from admin routes were touched).

---

*Generated by Claude Code on 2026-05-09. Re-read each finding's referenced file before applying changes — line numbers shift.*
