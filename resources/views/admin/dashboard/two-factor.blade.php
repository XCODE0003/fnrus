@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid" id="two-factor-page">
        <h1 class="h4 mb-4 text-gray-800">Двухфакторная защита (2FA)</h1>

        <div class="row">
            <div class="col-lg-8 col-xl-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Статус</h6>
                        <span class="badge ml-auto" id="tfa-status-badge"></span>
                    </div>
                    <div class="card-body">

                        <div id="tfa-not-enabled" style="display:none">
                            <p class="text-muted">
                                Включите 2FA, чтобы защитить административные действия одноразовым кодом
                                из приложения-аутентификатора (Google Authenticator, Yandex Key, Authy и т.п.).
                                Все коды генерируются по стандарту TOTP (RFC 6238) и работают офлайн.
                            </p>

                            <button class="btn btn-primary" id="tfa-start-setup">Начать настройку</button>

                            <div id="tfa-setup-block" class="mt-3" style="display:none">
                                <p>1. Введите в приложении-аутентификаторе следующий ключ
                                   (Google Authenticator / Yandex Key / Microsoft Authenticator
                                   поддерживают ручной ввод):</p>
                                <p class="text-muted small">
                                    <strong>Аккаунт:</strong> <code id="tfa-issuer">{{ config('admin.two_factor.issuer', config('app.name','App')) }}</code><br>
                                    <strong>Ключ:</strong>
                                    <code id="tfa-secret" style="font-size:1.1em;letter-spacing:.05em"></code>
                                    <button type="button" class="btn btn-sm btn-link p-0 ml-1" id="tfa-copy-secret">копировать</button>
                                </p>
                                <p class="text-muted small">
                                    Либо передайте URL приложению (длинный «otpauth://»):<br>
                                    <a id="tfa-otpauth" href="#" class="small text-break"></a>
                                </p>
                                <p>2. Введите 6-значный код из приложения:</p>
                                <div class="form-group d-flex" style="gap:.5rem;max-width:320px">
                                    <input type="text" class="form-control" id="tfa-enable-code" maxlength="6" inputmode="numeric" autocomplete="off" placeholder="123456">
                                    <button class="btn btn-success" id="tfa-enable-submit">Включить</button>
                                </div>
                            </div>
                        </div>

                        <div id="tfa-recovery-block" style="display:none" class="alert alert-warning">
                            <strong>Сохраните резервные коды!</strong>
                            <p class="small mb-2">Каждый код одноразовый. Понадобятся, если вы потеряете устройство.</p>
                            <pre id="tfa-recovery-codes" style="background:#1d2533;color:#fff;padding:1rem;border-radius:6px"></pre>
                            <button class="btn btn-sm btn-secondary" id="tfa-copy-recovery">Скопировать</button>
                        </div>

                        <div id="tfa-enabled" style="display:none">
                            <p>2FA включена. Введите 6-значный код из приложения, чтобы подтвердить
                               сессию (требуется раз в 12 часов).</p>
                            <div class="form-group d-flex" style="gap:.5rem;max-width:320px">
                                <input type="text" class="form-control" id="tfa-verify-code" maxlength="8" inputmode="numeric" autocomplete="off" placeholder="123456">
                                <button class="btn btn-primary" id="tfa-verify-submit">Подтвердить</button>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="tfa-recovery-mode">
                                <label for="tfa-recovery-mode" class="form-check-label small">Использовать резервный код</label>
                            </div>

                            <hr>
                            <details class="mt-3">
                                <summary style="cursor:pointer">Потерял устройство — сбросить через email</summary>
                                <p class="small text-muted mt-2">
                                    На указанный в профиле email будет отправлен одноразовый код.
                                    После ввода кода 2FA будет полностью сброшена и потребуется
                                    настройка заново. Запрашивать можно не чаще 1 раза в 10 минут.
                                </p>
                                <button class="btn btn-sm btn-outline-warning" id="tfa-email-request">Отправить код на email</button>
                                <div class="form-group d-flex mt-2" style="gap:.5rem;max-width:320px;display:none" id="tfa-email-confirm-wrap">
                                    <input type="text" class="form-control form-control-sm" id="tfa-email-code" maxlength="20" placeholder="Код из письма">
                                    <button class="btn btn-sm btn-warning" id="tfa-email-confirm">Сбросить 2FA</button>
                                </div>
                            </details>
                            <hr>
                            <details class="mt-3">
                                <summary class="text-danger" style="cursor:pointer">Отключить 2FA</summary>
                                <div class="form-group d-flex mt-2" style="gap:.5rem;max-width:320px">
                                    <input type="text" class="form-control form-control-sm" id="tfa-disable-code" maxlength="6" inputmode="numeric" placeholder="Текущий код">
                                    <button class="btn btn-sm btn-outline-danger" id="tfa-disable-submit">Отключить</button>
                                </div>
                            </details>
                        </div>

                        <div id="tfa-msg" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const $ = (id) => document.getElementById(id);
        const msg = (text, ok) => {
            $('tfa-msg').innerHTML = '<div class="alert alert-' + (ok ? 'success' : 'danger') + '">' + text + '</div>';
        };
        const apiBase = '/api/admin/2fa';
        // JWT is stored in the `session_token` cookie by the existing app
        // (see public/assets/js/main.js setAuthorization()).
        const getCookie = (name) => {
            const m = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'));
            return m ? decodeURIComponent(m[2]) : '';
        };
        const headers = () => {
            const t = getCookie('session_token');
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': t ? ('Bearer ' + t) : '',
            };
        };

        async function loadState() {
            const r = await fetch(apiBase + '/setup', { headers: headers(), credentials: 'same-origin' });
            const j = await r.json();
            if (!j.ok) { msg(j.description || 'Ошибка', false); return; }
            const enabled = !!j.result.enabled;
            $('tfa-status-badge').className = 'badge ml-auto badge-' + (enabled ? 'success' : 'warning');
            $('tfa-status-badge').textContent = enabled ? 'Включена' : 'Не настроена';
            $('tfa-enabled').style.display = enabled ? 'block' : 'none';
            $('tfa-not-enabled').style.display = enabled ? 'none' : 'block';

            if (!enabled) {
                $('tfa-secret').textContent = j.result.secret;
                $('tfa-otpauth').href = j.result.otpauth;
                $('tfa-otpauth').textContent = j.result.otpauth;
            }
        }

        $('tfa-start-setup').addEventListener('click', () => {
            $('tfa-setup-block').style.display = 'block';
        });
        $('tfa-copy-secret').addEventListener('click', () => {
            navigator.clipboard.writeText($('tfa-secret').textContent);
        });

        $('tfa-enable-submit').addEventListener('click', async () => {
            const code = $('tfa-enable-code').value.trim();
            const r = await fetch(apiBase + '/enable', { method: 'POST', headers: headers(), credentials: 'same-origin', body: JSON.stringify({ code }) });
            const j = await r.json();
            if (!j.ok) { msg(j.description || 'Ошибка', false); return; }
            msg('2FA включена.', true);
            $('tfa-recovery-block').style.display = 'block';
            $('tfa-recovery-codes').textContent = (j.result.recovery_codes || []).join('\n');
            await loadState();
        });

        $('tfa-copy-recovery').addEventListener('click', () => {
            navigator.clipboard.writeText($('tfa-recovery-codes').textContent);
        });

        $('tfa-verify-submit').addEventListener('click', async () => {
            const code = $('tfa-verify-code').value.trim();
            const recovery = $('tfa-recovery-mode').checked;
            const r = await fetch(apiBase + '/verify', { method: 'POST', headers: headers(), credentials: 'same-origin', body: JSON.stringify({ code, recovery }) });
            const j = await r.json();
            msg(j.description || (j.ok ? 'OK' : 'Ошибка'), j.ok);
            if (j.ok) {
                setTimeout(() => { window.location.href = '/{{ trim(config('admin.prefix','admin'),'/') }}/stats'; }, 800);
            }
        });

        $('tfa-disable-submit').addEventListener('click', async () => {
            const code = $('tfa-disable-code').value.trim();
            const r = await fetch(apiBase + '/disable', { method: 'POST', headers: headers(), credentials: 'same-origin', body: JSON.stringify({ code }) });
            const j = await r.json();
            msg(j.description || (j.ok ? 'OK' : 'Ошибка'), j.ok);
            if (j.ok) await loadState();
        });

        $('tfa-email-request').addEventListener('click', async () => {
            const r = await fetch(apiBase + '/recovery/email/request', { method: 'POST', headers: headers(), credentials: 'same-origin' });
            const j = await r.json();
            msg(j.description || (j.ok ? 'OK' : 'Ошибка'), j.ok);
            if (j.ok) $('tfa-email-confirm-wrap').style.display = 'flex';
        });

        $('tfa-email-confirm').addEventListener('click', async () => {
            const code = $('tfa-email-code').value.trim();
            const r = await fetch(apiBase + '/recovery/email/confirm', { method: 'POST', headers: headers(), credentials: 'same-origin', body: JSON.stringify({ code }) });
            const j = await r.json();
            msg(j.description || (j.ok ? 'OK' : 'Ошибка'), j.ok);
            if (j.ok) setTimeout(() => window.location.reload(), 1200);
        });

        loadState();
    })();
    </script>
@endsection
