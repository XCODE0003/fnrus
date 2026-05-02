@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header d-flex align-items-center py-3">
                        <h5 class="m-0 mr-auto text-left"><i class="far fa-fw fa-address-card mr-1"></i> Элементы страницы «О нас»</h5>
                        <button class="btn btn-sm btn-primary" onclick="showAddAboutItem();" id="btn_add_about_item">
                            <i class="far fa-plus mr-1"></i> Добавить
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-primary" role="alert">
                            Максимум 5 элементов. Каждый элемент содержит иконку, текст и ссылку.
                        </div>
                        <div id="about_items_list"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="aboutItemModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="aboutItemModalTitle">Элемент</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="about_item_id" value="0">
                    <div class="form-group">
                        <label for="about_icon">Иконка</label>
                        <select class="form-control" id="about_icon">
                            <option value="telegram">Telegram</option>
                            <option value="discord">Discord</option>
                            <option value="vk">VK</option>
                            <option value="youtube">YouTube</option>
                            <option value="instagram">Instagram</option>
                            <option value="link">Ссылка</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="about_label_ru">Название (RU)</label>
                        <input type="text" class="form-control" id="about_label_ru" placeholder="Telegram канал">
                    </div>
                    <div class="form-group">
                        <label for="about_label_en">Название (EN)</label>
                        <input type="text" class="form-control" id="about_label_en" placeholder="Telegram Channel">
                    </div>
                    <div class="form-group">
                        <label for="about_url">Ссылка (URL)</label>
                        <input type="text" class="form-control" id="about_url" placeholder="https://t.me/...">
                    </div>
                    <div class="form-group">
                        <label for="about_url_text">Текст ссылки</label>
                        <input type="text" class="form-control" id="about_url_text" placeholder="@username">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btn_save_about_item" onclick="saveAboutItem();">
                        <i class="far fa-save mr-1"></i> Сохранить
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
