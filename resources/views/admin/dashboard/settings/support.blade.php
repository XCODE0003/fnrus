@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10 col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 text-left"><i class="far fa-fw fa-life-ring mr-1"></i> Техподдержка</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">Настройки окна техподдержки в личном кабинете пользователя. Текст и до 3 кнопок со ссылками.</p>

                        <div class="form-group">
                            <label for="support_text">Текст сообщения</label>
                            <textarea class="form-control" id="support_text" rows="3" placeholder="Если у вас возникли вопросы, обратитесь в поддержку:"></textarea>
                        </div>

                        <hr class="my-4">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-link mr-1"></i> Кнопка 1</h6>
                        <div class="form-group">
                            <label for="support_btn1_text">Текст кнопки</label>
                            <input type="text" class="form-control" id="support_btn1_text" placeholder="Telegram">
                        </div>
                        <div class="form-group">
                            <label for="support_btn1_url">Ссылка</label>
                            <input type="text" class="form-control" id="support_btn1_url" placeholder="https://t.me/...">
                        </div>

                        <hr class="my-4">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-link mr-1"></i> Кнопка 2 <span class="text-muted font-weight-normal">(необязательно)</span></h6>
                        <div class="form-group">
                            <label for="support_btn2_text">Текст кнопки</label>
                            <input type="text" class="form-control" id="support_btn2_text" placeholder="">
                        </div>
                        <div class="form-group">
                            <label for="support_btn2_url">Ссылка</label>
                            <input type="text" class="form-control" id="support_btn2_url" placeholder="https://...">
                        </div>

                        <hr class="my-4">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-link mr-1"></i> Кнопка 3 <span class="text-muted font-weight-normal">(необязательно)</span></h6>
                        <div class="form-group">
                            <label for="support_btn3_text">Текст кнопки</label>
                            <input type="text" class="form-control" id="support_btn3_text" placeholder="">
                        </div>
                        <div class="form-group">
                            <label for="support_btn3_url">Ссылка</label>
                            <input type="text" class="form-control" id="support_btn3_url" placeholder="https://...">
                        </div>

                        <hr class="my-4">
                        <button onclick="saveSupport();" class="btn btn-primary col-sm-12">
                            <i class="far fa-save mr-1"></i> Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
