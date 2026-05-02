@extends('admin.dashboard.layouts.main')
@section('content')
<div class="container-fluid" id="access-page">
    <h1 class="h4 mb-4 text-gray-800">Контроль доступа</h1>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">IP allow-list</h6></div>
                <div class="card-body">
                    <p class="small text-muted">
                        Статический список из <code>.env</code> (<code>ADMIN_ALLOWED_IPS</code>) перечислен в самом верху и редактируется через файл.
                        Динамические записи можно добавлять прямо отсюда (с TTL, опционально).
                    </p>

                    <div class="mb-3">
                        <strong>Из .env:</strong>
                        <code id="ips-static"></code>
                    </div>

                    <table class="table table-sm">
                        <thead><tr><th>CIDR</th><th>Заметка</th><th>Истекает</th><th></th></tr></thead>
                        <tbody id="ips-rows"></tbody>
                    </table>

                    <div class="form-row align-items-end">
                        <div class="col"><input type="text" class="form-control" id="new-cidr" placeholder="203.0.113.7 или 198.51.100.0/24"></div>
                        <div class="col"><input type="text" class="form-control" id="new-note" placeholder="Заметка (например: офис)"></div>
                        <div class="col-2"><input type="number" class="form-control" id="new-ttl" placeholder="TTL мин (0=всегда)" min="0"></div>
                        <div class="col-auto"><button class="btn btn-primary" id="add-ip">Добавить</button></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Снять блокировку логина</h6></div>
                <div class="card-body">
                    <p class="small text-muted">Сбросит счётчик неудач и разблокирует вход для пары (IP + username).</p>
                    <div class="form-row align-items-end">
                        <div class="col"><input type="text" class="form-control" id="unlock-ip" placeholder="IP"></div>
                        <div class="col"><input type="text" class="form-control" id="unlock-username" placeholder="username"></div>
                        <div class="col-auto"><button class="btn btn-warning" id="unlock-btn">Разблокировать</button></div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex">
                    <h6 class="m-0 font-weight-bold text-primary">Последние попытки входа</h6>
                    <button class="btn btn-sm btn-link ml-auto p-0" id="reload-attempts">обновить</button>
                </div>
                <div class="card-body" style="max-height:420px;overflow:auto">
                    <table class="table table-sm small">
                        <thead><tr><th>Время</th><th>IP</th><th>Username</th><th>Статус</th></tr></thead>
                        <tbody id="attempts-rows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="access-msg"></div>
</div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const apiBase = '/api/admin/access';
    const headers = (json) => {
        const t = localStorage.getItem('token') || localStorage.getItem('admin_token') || '';
        const h = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Authorization': t ? ('Bearer ' + t) : '',
        };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    };
    const msg = (text, ok) => {
        $('access-msg').innerHTML = '<div class="alert alert-' + (ok ? 'success' : 'danger') + '">' + text + '</div>';
        setTimeout(() => { $('access-msg').innerHTML = ''; }, 4000);
    };
    const escape = (s) => String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmtTs = (ts) => ts ? new Date(ts * 1000).toLocaleString('ru-RU') : '∞';

    async function loadIps() {
        const r = await fetch(apiBase + '/ips', { headers: headers(), credentials: 'same-origin' });
        const j = await r.json();
        if (!j.ok) { msg(j.description || 'Ошибка загрузки', false); return; }
        $('ips-static').textContent = (j.result.static_env && j.result.static_env.length) ? j.result.static_env.join(', ') : '— (не задан)';
        $('ips-rows').innerHTML = (j.result.dynamic || []).map((row) => {
            const exp = row.expires_at ? fmtTs(row.expires_at) : 'бессрочно';
            const expired = row.expires_at && row.expires_at < j.result.now;
            return '<tr' + (expired ? ' class="text-muted"' : '') + '>' +
                '<td><code>' + escape(row.cidr) + '</code></td>' +
                '<td>' + escape(row.note || '') + '</td>' +
                '<td>' + escape(exp) + (expired ? ' <span class="badge badge-secondary">истёк</span>' : '') + '</td>' +
                '<td><button class="btn btn-sm btn-outline-danger" data-del="' + row.id + '">×</button></td>' +
                '</tr>';
        }).join('') || '<tr><td colspan="4" class="text-muted small">Динамических записей нет</td></tr>';
    }

    async function loadAttempts() {
        const r = await fetch(apiBase + '/login/attempts?limit=50', { headers: headers(), credentials: 'same-origin' });
        const j = await r.json();
        if (!j.ok) return;
        $('attempts-rows').innerHTML = (j.result || []).map((row) => {
            const okBadge = row.successful ? '<span class="badge badge-success">OK</span>' : '<span class="badge badge-danger">' + escape(row.reason || 'fail') + '</span>';
            return '<tr><td>' + escape(row.created_at) + '</td><td><code>' + escape(row.ip) + '</code></td><td>' + escape(row.username) + '</td><td>' + okBadge + '</td></tr>';
        }).join('') || '<tr><td colspan="4" class="text-muted small">Нет записей</td></tr>';
    }

    $('add-ip').addEventListener('click', async () => {
        const body = JSON.stringify({
            cidr: $('new-cidr').value.trim(),
            note: $('new-note').value.trim(),
            ttl_minutes: parseInt($('new-ttl').value || '0', 10),
        });
        const r = await fetch(apiBase + '/ips', { method: 'POST', headers: headers(true), credentials: 'same-origin', body });
        const j = await r.json();
        msg(j.description || (j.ok ? 'OK' : 'Ошибка'), j.ok);
        if (j.ok) { $('new-cidr').value = $('new-note').value = $('new-ttl').value = ''; await loadIps(); }
    });

    $('ips-rows').addEventListener('click', async (e) => {
        const id = e.target.getAttribute('data-del');
        if (!id) return;
        if (!confirm('Удалить запись #' + id + '?')) return;
        const r = await fetch(apiBase + '/ips/' + id, { method: 'DELETE', headers: headers(), credentials: 'same-origin' });
        const j = await r.json();
        msg(j.description || (j.ok ? 'Удалено' : 'Ошибка'), j.ok);
        if (j.ok) await loadIps();
    });

    $('unlock-btn').addEventListener('click', async () => {
        const body = JSON.stringify({ ip: $('unlock-ip').value.trim(), username: $('unlock-username').value.trim() });
        const r = await fetch(apiBase + '/login/unlock', { method: 'POST', headers: headers(true), credentials: 'same-origin', body });
        const j = await r.json();
        msg(j.description || (j.ok ? 'OK' : 'Ошибка'), j.ok);
    });

    $('reload-attempts').addEventListener('click', loadAttempts);

    loadIps(); loadAttempts();
})();
</script>
@endsection
