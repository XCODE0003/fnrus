@extends('dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <form name="editSenderForm" id="edit_sender" class="row">
            <div class="col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-left">
                            <i class="far fa-fw fa-file-alt"></i> Основная информация
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-group text-left">
                            <label>Название рассылки <span class="text-danger">*</span>
                            </label>
                            <input name="name" class="form-control" placeholder="Напишите название" value="Новая рассылка">
                        </div>
                        <div class="form-group text-left">
                            <label>Получатели <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" name="type">
                                <option value="0" selected="">Все пользователи магазина</option>
                            </select>
                        </div>
                        <div class="form-group text-left">
                            <label for="text_page">Текст рассылки <span class="text-danger">*</span>
                            </label>
                            <div id="text_message_toolbar"></div>
                            <div id="text_message" style="height: 110px;"></div>
                            <div class="alert alert-primary mt-2" role="alert">
                                <b class="d-block">
                                    <i class="far fa-bullseye-arrow mr-1"></i> Таргетированная рассылка: </b>
                                <hr class="my-2">
                                <b>[first_name]</b> - указать имя пользователя
                            </div>
                        </div>
                        <div class="form-group text-left">
                            <label>Когда отправить <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" name="type_send" id="date_type" onchange="showTime();">
                                <option value="0">Сейчас</option>
                                <option value="1">Позже</option>
                            </select>
                        </div>
                        <div class="form-group text-left" id="date_send" style="display: none;">
                            <label>Дата и время запуска <span class="text-danger">*</span>
                            </label>
                            <input name="date_send" class="form-control" value="05.01.2022 17:01" placeholder="Укажите дату и время запуска">
                        </div>
                        <div class="form-group text-left">
                            <label>Кнопка</label>
                            <select class="form-control" name="type_button" id="button_type" onchange="showTypeButton();">
                                <option value="0">Нет</option>
                                <option value="1">Ссылка на сторонний сайт</option>
                                <option value="2">Открыть существующую страницу</option>
                            </select>
                        </div>
                        <div class="form-group text-left" id="block_name_button" style="display: none;">
                            <label>Название кнопки</label>
                            <input name="name_button" class="form-control" placeholder="Напишите название" value="">
                        </div>
                        <div class="form-group text-left" id="block_link_button" style="display: none;">
                            <label>Ссылка кнопки</label>
                            <input name="act_button" class="form-control" placeholder="Вставьте ссылку" value="">
                        </div>
                        <div class="form-group text-left" id="block_page_button" style="display: none;">
                            <label>Направить на страницу</label>
                            <select class="form-control" name="act_button">
                                <option disabled="">- Товары</option>
                            </select>
                        </div>
                        <div class="form-group text-center"> Обязательное поле <span class="text-danger">*</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mb-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-left">
                            <i class="far fa-image mr-2"></i> Изображение
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-group text-left">
                            <input type="hidden" id="file" name="photo" value="https://telegra.ph/file/803001721fbef1a472211.jpg">
                            <div id="filename_sender">
                                <img style="border-radius: 15px;width: 100%" src="https://telegra.ph/file/803001721fbef1a472211.jpg">
                            </div>
                            <center>
                                <label for="file-upload" class="custom-file-upload">
                                    <i class="far fa-upload mr-1" style="font-size: 15px"></i> Выберите изображение </label>
                                <input id="file-upload" type="file" style="display: none;" name="file">
                                <div id="btn_del">
                                    <a href="#" class="d-block mt-3 text-danger" onclick="del_photo();">Удалить изображение</a>
                                </div>
                            </center>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fixed-bottom text-right mb-4 px-1" style="z-index:12;">
                <button class="btn btn-primary font-weight-bold py-2 px-4 mr-4" onclick="edit_sender (this.form);return false;">
                    <i class="far fa-save mr-1"></i> Сохранить</button>
            </div>
        </form>
    </div>
@endsection
