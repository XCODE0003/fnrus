@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0"><i class="far fa-fw fa-envelope-open-text mr-1"></i> Email-рассылки</h5>
                <div id="audience_pill" class="text-muted mr-3"><small>Аудитория: …</small></div>
                <a data-toggle="modal" data-target="#createBroadcast" href="#" class="btn btn-sm btn-color shadow px-3">
                    <i class="far fa-plus"></i>
                    <span class="text d-none d-lg-inline ml-1">Новая рассылка</span>
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm m-0">
                    <thead><tr>
                        <th>#</th><th>Тема</th><th>Статус</th><th>Аудитория</th>
                        <th>Отправлено</th><th>Ошибок</th><th>Создано</th><th></th>
                    </tr></thead>
                    <tbody id="bcast_list"><tr><td colspan="8" class="text-center text-muted py-3">Загрузка…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Create / edit modal --}}
    <div class="modal fade" id="createBroadcast" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0"><i class="far fa-paper-plane mr-1"></i> <span id="bcast_modal_title">Новая рассылка</span></h5>
                    <button class="close" type="button" data-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="bcast_id" value="">

                    <div class="form-group text-left">
                        <label for="bcast_subject">Тема</label>
                        <input id="bcast_subject" class="form-control" placeholder="Тема письма" maxlength="255">
                    </div>

                    <div class="form-group text-left mt-3">
                        <label class="d-flex align-items-center justify-content-between">
                            <span>Тело письма</span>
                            <small class="text-muted">Плейсхолдер: <code>{email}</code></small>
                        </label>
                        <textarea id="bcast_body" rows="10" class="form-control"></textarea>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" style="font-size:13px">
                        Рассылка отправляется только пользователям с подтверждённым email и включённой опцией маркетинга
                        (<code>email_notify_marketing=1</code>). В письмо автоматически добавляется ссылка на отписку.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="bcastPreview()"><i class="far fa-eye mr-1"></i> Превью</button>
                    <button class="btn btn-primary" onclick="bcastSaveDraft()"><i class="far fa-save mr-1"></i> Сохранить черновик</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview modal --}}
    <div class="modal fade" id="previewBroadcast" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0">Превью</h5>
                    <button class="close" type="button" data-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <iframe id="bcast_preview_frame" style="width:100%;height:520px;border:1px solid #2a2a2a;border-radius:6px;background:#fff"></iframe>
                </div>
            </div>
        </div>
    </div>

    {{-- Send confirmation modal --}}
    <div class="modal fade" id="sendBroadcast" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0 text-danger"><i class="far fa-exclamation-triangle mr-1"></i> Подтверждение отправки</h5>
                    <button class="close" type="button" data-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <p>Эта операция запустит рассылку <strong id="send_subject_label"></strong> на <strong id="send_audience_label">…</strong> подписчиков.</p>
                    <p>Введите <code>ОТПРАВИТЬ</code> для подтверждения:</p>
                    <input id="send_confirm" class="form-control">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button class="btn btn-danger" onclick="bcastSendConfirm()"><i class="far fa-paper-plane mr-1"></i> Отправить</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="/assets/js/admin/email-broadcasts.js?v={{ time() }}"></script>
@endsection
