@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0"><i class="far fa-fw fa-tools mr-1"></i> Обслуживание</h5>
                <small class="text-muted">Все операции необратимы. Подтверждение фразой «СБРОСИТЬ».</small>
            </div>
            <div class="card-body p-4">
                <div class="row" id="maint_stats">
                    <div class="col-12"><small class="text-muted">Загрузка статистики…</small></div>
                </div>
            </div>
        </div>

        {{-- Analytics reset --}}
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0"><i class="far fa-chart-bar mr-1"></i> Сброс аналитики</h6></div>
            <div class="card-body p-4">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="reset_scope">Раздел</label>
                        <select id="reset_scope" class="form-control form-control-sm">
                            <option value="all">Вся аналитика</option>
                            <option value="products">Только товары</option>
                            <option value="categories">Только категории/игры</option>
                            <option value="coupons">Только промокоды</option>
                            <option value="senders">Только Telegram-рассылки</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="reset_confirm">Подтверждение</label>
                        <input id="reset_confirm" class="form-control form-control-sm" placeholder="Введите СБРОСИТЬ">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-danger btn-sm" onclick="maintResetAnalytics()">
                            <i class="far fa-trash-alt mr-1"></i> Сбросить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk cleanup: orders --}}
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0"><i class="far fa-receipt mr-1"></i> Массовая очистка: заказы</h6></div>
            <div class="card-body p-4">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="cl_orders_type">Тип</label>
                        <select id="cl_orders_type" class="form-control form-control-sm">
                            <option value="expired">Просроченные (status=4)</option>
                            <option value="paid">Оплаченные (status=2)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="cl_orders_limit">Кол-во</label>
                        <input id="cl_orders_limit" type="number" min="1" max="5000" value="100" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label for="cl_orders_confirm">Подтверждение</label>
                        <input id="cl_orders_confirm" class="form-control form-control-sm" placeholder="СБРОСИТЬ">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-danger btn-sm" onclick="maintCleanupOrders()">Удалить</button>
                    </div>
                </div>
                <small class="text-muted">Удаляются самые старые записи в указанном количестве (max 5000 за вызов).</small>
            </div>
        </div>

        {{-- Bulk cleanup: coupons --}}
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0"><i class="far fa-ticket-alt mr-1"></i> Массовая очистка: промокоды</h6></div>
            <div class="card-body p-4">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="cl_coupons_limit">Кол-во</label>
                        <input id="cl_coupons_limit" type="number" min="1" max="5000" value="100" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label for="cl_coupons_confirm">Подтверждение</label>
                        <input id="cl_coupons_confirm" class="form-control form-control-sm" placeholder="СБРОСИТЬ">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-danger btn-sm" onclick="maintCleanupCoupons()">Удалить</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk cleanup: files --}}
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0"><i class="far fa-file mr-1"></i> Массовая очистка: файлы</h6></div>
            <div class="card-body p-4">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="cl_files_type">Тип</label>
                        <select id="cl_files_type" class="form-control form-control-sm">
                            <option value="files">files</option>
                            <option value="covers">covers</option>
                            <option value="avatars">avatars</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="cl_files_limit">Кол-во</label>
                        <input id="cl_files_limit" type="number" min="1" max="5000" value="100" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label for="cl_files_confirm">Подтверждение</label>
                        <input id="cl_files_confirm" class="form-control form-control-sm" placeholder="СБРОСИТЬ">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-danger btn-sm" onclick="maintCleanupFiles()">Удалить</button>
                    </div>
                </div>
                <small class="text-muted">Удаляются и записи в БД, и файлы из storage/app/public.</small>
            </div>
        </div>

        {{-- Bulk cleanup: exports --}}
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0"><i class="far fa-file-export mr-1"></i> Массовая очистка: история экспорта</h6></div>
            <div class="card-body p-4">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="cl_exports_limit">Кол-во</label>
                        <input id="cl_exports_limit" type="number" min="1" max="5000" value="100" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label for="cl_exports_confirm">Подтверждение</label>
                        <input id="cl_exports_confirm" class="form-control form-control-sm" placeholder="СБРОСИТЬ">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-danger btn-sm" onclick="maintCleanupExports()">Удалить</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Audit log --}}
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h6 class="mr-auto m-0"><i class="far fa-history mr-1"></i> Журнал операций</h6>
                <button class="btn btn-sm btn-secondary" onclick="maintLoadLog()"><i class="far fa-sync"></i></button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm m-0">
                    <thead><tr><th>#</th><th>Когда</th><th>Админ</th><th>Действие</th><th>Цель</th><th>Затронуто</th></tr></thead>
                    <tbody id="maint_log_body"><tr><td colspan="6" class="text-center text-muted py-3">Загрузка…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="/assets/js/admin/maintenance.js?v={{ time() }}"></script>
@endsection
