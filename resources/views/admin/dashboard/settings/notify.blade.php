@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-12 col-md-9">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 text-left"><i class="far fa-fw fa-bell mr-1"></i> Уведомления магазина</h5>
                    </div>
                    <div class="card-body p-5">
                        <form>
                            <div class="form-group text-left">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="notify_new_user" value="1" class="custom-control-input" id="customCheck1" checked="">
                                    <label class="custom-control-label" for="customCheck1" style="cursor: pointer;">Получать уведомления о новых участниках</label>
                                </div>
                            </div>
                            <div class="form-group text-left">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="notify_new_pay" value="1" class="custom-control-input" id="customCheck2" checked="">
                                    <label class="custom-control-label" for="customCheck2" style="cursor: pointer;">Получать уведомления о новых платежах</label>
                                </div>
                            </div>
                            <div class="alert alert-primary" role="alert">
                                Для получения уведомлений<br>запустите бота <a target="_blank" href="https://t.me/notify_telebiz_bot">@notify_telebiz_bot</a>
                            </div>
                            <button onclick="toggle_notify (this.form);return false;" class="btn col-sm-12 mb-1 btn-primary">Сохранить изменения</button>
                        </form>
                    </div>
                </div>

                {{-- Global status-broadcast template --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center">
                        <h5 class="m-0 text-left mr-auto"><i class="far fa-fw fa-paper-plane mr-1"></i> Шаблон сообщения при изменении статуса</h5>
                        <small class="text-muted">Используется когда у конкретного софта поле шаблона пустое</small>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-group text-left">
                            <label class="d-flex align-items-center justify-content-between">
                                <span>Текст сообщения</span>
                                <small class="text-muted">Поддерживается форматирование и эмодзи</small>
                            </label>
                            <div class="mb-2">
                                <small class="text-muted mr-2">Плейсхолдеры:</small>
                                <button type="button" class="btn btn-outline-secondary btn-sm mr-1 sb-placeholder" data-token="{game}">{game}</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm mr-1 sb-placeholder" data-token="{product}">{product}</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm sb-placeholder" data-token="{status}">{status}</button>
                            </div>
                            <textarea id="sb_template" rows="8" class="form-control" placeholder="Например: Изменение статуса чита&#10;├ Игра: {game}&#10;├ Чит: {product}&#10;└ Статус: {status}"></textarea>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Картинка к сообщению (по умолчанию)</label>
                            <div class="d-flex align-items-center">
                                <input type="file" id="sb_image_file" accept="image/png,image/jpeg,image/webp" class="d-none">
                                <button type="button" id="sb_image_pick" class="btn btn-secondary btn-sm">
                                    <i class="far fa-upload mr-1"></i> Загрузить
                                </button>
                                <button type="button" id="sb_image_remove" class="btn btn-link btn-sm text-danger ml-2 d-none">
                                    <i class="far fa-trash-alt mr-1"></i> Удалить
                                </button>
                                <small id="sb_image_status" class="text-muted ml-3"></small>
                            </div>
                            <input type="hidden" id="sb_image_path">
                            <div id="sb_image_preview" class="mt-2 d-none">
                                <img src="" alt="Default broadcast image" style="max-height:160px;border-radius:6px;">
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary mt-3" onclick="sbSave();">
                            <i class="far fa-save mr-1"></i> Сохранить шаблон
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="/assets/js/admin/status-broadcast-settings.js?v={{ time() }}"></script>
@endsection
