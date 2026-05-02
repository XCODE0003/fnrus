@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4" id="block_welcome">
                    <div class="card-header border-0">
                        <h6 class="m-0 font-weight-bold text-left">
                            <i class="far fa-fw fa-file-alt"></i> Приветственное сообщение
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-11">
                                <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Изображение загружено</div>
                            </div>
                            <div class="col-1">
                                <div class="m-0" id="btn_delete_image"></div>
                            </div>
                            <label id="btn_upload_image"  for="bw_image_upload" class="custom-file-upload text-center mb-0" style="margin: 0 12px;">
                                <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                            </label>
                            <input id="bw_image_upload" type="file" style="display: none;" name="file">
                        </div>
                        <div class="form-group text-left">
                            <div id="text_welcome_toolbar"></div>
                            <div id="text_welcome" style="height: 110px;"></div>
                            <div class="alert alert-primary mt-2" role="alert">
                                <b>{first_name}</b> - имя пользователя
                            </div>
                        </div>
                        <div class="form-group text-left py-2">
                        <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                            <input type="checkbox" class="custom-control-input d-block" id="disable_web_page_preview">
                            <label class="custom-control-label" for="disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                        </div>
                        <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                            <input type="checkbox" class="custom-control-input d-block" id="has_spoiler">
                            <label class="custom-control-label" for="has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                        </div>
                        </div>

                        <button onclick="editText('welcome');return false;" class="btn col-sm-12    btn-primary">
                            <i class="far fa-save mr-1"></i> Сохранить изменения </button>
                    </div>
                </div>
                <div class="card shadow mb-4">
                    <div class="card-header d-flex align-items-center py-3 px-4 border-0">
                        <h6 class="m-0 mr-auto font-weight-bold text-left">
                            <i class="far fa-fw fa-user-plus mr-1"></i> Обязательная подписка на канал
                        </h6>
                        <div class="d-block">
                            <div class="custom-control custom-switch d-block" style="">
                                <input type="checkbox" class="custom-control-input d-block" onclick="setChannelSubActive();" id="switch_join">
                                <label class="custom-control-label" for="switch_join"></label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body px-4 d-none" id="block_join">
                        <div class="form-group text-left">
                            <div id="text_join_toolbar"></div>
                            <div id="text_join" class="mb-3" style="height: 110px;"></div>
                        </div>
                        <button onclick="saveChannelSubSettings('text');" class="btn col-sm-12 mb-1 btn-primary"><i class="far fa-save mr-1"></i> Сохранить изменения </button>
                        <hr/>
                        <div class="form-group text-left mb-2">
                            <select class="form-control" onchange="saveChannelSubSettings('columns');" id="count_columns">
                                <option value="1">Кнопки в одну колонку</option>
                                <option value="2">Кнопки в две колонки</option>
                                <option value="3">Кнопки в три колонки</option>
                            </select>
                        </div>
                        <div class="row" style="margin: 0 -8px;" id="channels"> </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header border-0">
                        <h6 class="m-0 font-weight-bold text-left">
                            <i class="far fa-list-alt mr-1"></i> Кнопки меню
                        </h6>
                    </div>
                    <div class="card-body p-4" id="block_buttons">
                        <div class="form-group text-left mb-2 mt-0">
                            <select class="form-control" onchange="changeButtonSet('block_buttons', 'count_columns');" id="count_columns">
                                <option value="1">Кнопки в одну колонку</option>
                                <option value="2">Кнопки в две колонки</option>
                                <option value="3">Кнопки в три колонки</option>
                            </select>
                        </div>
                        <div class="row" style="margin: 0 -8px;" id="buttons"> </div>
                    </div>
                </div>
                <div class="card shadow mb-4">
                    <div class="card-header d-flex align-items-center py-3 px-4 border-0">
                        <h6 class="m-0 mr-auto font-weight-bold text-left">
                            <i class="far fa-fw fa-route-interstate mr-1"></i> Принудительное соглашение
                        </h6>
                        <div class="d-block">
                            <div class="custom-control custom-switch d-block" style="">
                                <input type="checkbox" onclick="setTextActive('agreement');" class="custom-control-input d-block" id="switch_text_terms">
                                <label class="custom-control-label" for="switch_text_terms"></label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-4 d-none" id="block_text_terms">
                        <div id="text_agreement_toolbar"></div>
                        <div id="text_agreement" style="height: 110px;"></div>
                        <button onclick="editText('agreement');" class="btn col-sm-12 mt-3 mb-1 btn-primary"><i class="far fa-save mr-1"></i> Сохранить изменения </button>
                    </div>
                </div>
                <div class="card shadow mb-4">
                    <div class="card-header d-flex align-items-center py-3 px-4 border-0">
                        <h6 class="m-0 mr-auto font-weight-bold text-left">
                            <i class="far fa-fw fa-shopping-bag mr-1"></i> Текст после покупки
                        </h6>
                        <div class="d-block">
                            <div class="custom-control custom-switch d-block" style="">
                                <input type="checkbox" onclick="setTextActive('after_payment');" class="custom-control-input d-block" id="switch_text_pay">
                                <label class="custom-control-label" for="switch_text_pay"></label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-4 d-none" id="block_text_pay">
                        <div id="text_after_payment_toolbar"></div>
                        <div id="text_after_payment" style="height: 110px;"></div>
                        <button onclick="editText('after_payment');" class="btn col-sm-12 mt-3 mb-1 btn-primary"><i class="far fa-save mr-1"></i> Сохранить изменения </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editButton" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel">Редактирование кнопки</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label>Изображение</label>
                        <div class="row">
                            <div class="col-11">
                                <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Изображение загружено</div>
                            </div>
                            <div class="col-1">
                                <div class="m-0" id="btn_delete_image"></div>
                            </div>
                            <div class="col-12">
                                <label id="btn_upload_image" for="eb_image_upload" class="custom-file-upload text-center mb-0">
                                    <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                </label>
                                <input id="eb_image_upload" type="file" style="display: none;" name="file">
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="title">Название кнопки</label>
                        <input id="title" class="form-control" placeholder="Напишите название">
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="text_page">Текст кнопки</label>
                        <div id="text_message_toolbar"></div>
                        <div id="text_message" style="height: 110px;"></div>
                        <div class="alert alert-primary mt-2" role="alert"> Максимальное кол-во символов с фото: <b>1024</b>
                            <br> Максимальное кол-во символов без фото: <b>4096</b>
                        </div>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="visible">Видимость</label>
                        <select class="form-control" id="visible">
                            <option value="0">Скрыто</option>
                            <option value="1" selected>Общедоступно</option>
                        </select>
                    </div>
                    <div class="form-group text-left mt-4" id="block_buttons" style="">
                        <label class="form-label d-block">Кнопки</label>
                        <div id="blocks_buttons"></div>
                        <button class="btn btn-outline-secondary w-100 mb-2" id="btn-add-button" onclick="addBlockButton(2,'editButton')"><i class="far fa-plus mr-1"></i> Добавить кнопку</button>
                    </div>
                    <div class="form-group text-left mx-2 mt-4">
                        <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                            <input type="checkbox" class="custom-control-input d-block" id="eb_disable_web_page_preview">
                            <label class="custom-control-label" for="eb_disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                        </div>
                        <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                            <input type="checkbox" class="custom-control-input d-block" id="eb_has_spoiler">
                            <label class="custom-control-label" for="eb_has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btn-save"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addButton" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel">Новая кнопка</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label>Изображение</label>
                        <div class="row">
                            <div class="col-11">
                                <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Изображение загружено</div>
                            </div>
                            <div class="col-1">
                                <div class="m-0" id="btn_delete_image"></div>
                            </div>
                            <div class="col-12">
                                <label id="btn_upload_image" for="eb_image_upload" class="custom-file-upload text-center mb-0">
                                    <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                </label>
                                <input id="eb_image_upload" type="file" style="display: none;" name="file">
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="title">Название кнопки</label>
                        <input id="title" class="form-control" placeholder="Напишите название ">
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="text_page">Текст кнопки</label>
                        <div id="text_message_toolbar"></div>
                        <div id="text_message" style="height: 110px;"></div>
                        <div class="alert alert-primary mt-2" role="alert"> Максимальное кол-во символов с фото: <b>1024</b>
                            <br> Максимальное кол-во символов без фото: <b>4096</b>
                        </div>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="visible">Видимость</label>
                        <select class="form-control" id="visible">
                            <option value="0">Скрыто</option>
                            <option value="1" selected>Общедоступно</option>
                        </select>
                    </div>
                    <div class="form-group text-left mt-4" id="block_buttons" style="">
                        <label class="form-label d-block">Кнопки</label>
                        <div id="blocks_buttons"></div>
                        <button class="btn btn-outline-secondary w-100 mb-2" id="btn-add-button" onclick="addBlockButton(2,'addButton')"><i class="far fa-plus mr-1"></i> Добавить кнопку</button>
                    </div>
                    <div class="form-group text-left mx-2 mt-4">
                        <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                            <input type="checkbox" class="custom-control-input d-block" id="nb_disable_web_page_preview">
                            <label class="custom-control-label" for="nb_disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                        </div>
                        <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                            <input type="checkbox" class="custom-control-input d-block" id="nb_has_spoiler" disabled>
                            <label class="custom-control-label" for="nb_has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="saveButton('addButton');"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addChannel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form>
                    <div class="modal-header">
                        <h5 class="modal-title m-0" id="exampleModalLabel">Новый канал</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-primary" role="alert">Вам необходимо добавить Вашего бота в канал и выдать права на добавление участников.</div>
                        <div class="form-group text-left">
                            <label for="channel_id">ID канала</label>
                            <input id="channel_id" class="form-control" placeholder="Вставьте ID канала">
                        </div>
                        <div class="form-group text-left">
                            <label for="channel_title">Название</label>
                            <input id="channel_title" class="form-control" placeholder="Напишите название">
                        </div>
                        <div class="form-group text-left">
                            <label for="channel_link">Ссылка на канал</label>
                            <input id="channel_link" class="form-control" placeholder="Вставьте ссылку на канал">
                        </div>
                        <div class="form-group text-left mt-3" style="margin: auto 1px">
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="nc_is_active">
                                <label class="custom-control-label" for="nc_is_active" style="margin-left: 20px;">Включено</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="checkSaveChannel('addChannel', 0);return false;">
                            <i class="far fa-save mr-1"></i> Проверить и сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editChannel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form>
                    <div class="modal-header">
                        <h5 class="modal-title m-0" id="exampleModalLabel">Редактирование канала</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-primary" role="alert">Вам необходимо добавить Вашего бота в канал и выдать права на добавление участников.</div>
                        <div class="form-group text-left">
                            <label for="channel_title">Название</label>
                            <input id="channel_title" class="form-control" placeholder="Напишите название">
                        </div>
                        <div class="form-group text-left">
                            <label for="channel_link">Ссылка на канал</label>
                            <input id="channel_link" class="form-control" placeholder="Вставьте ссылку на канал">
                        </div>
                        <div class="form-group text-left mt-3" style="margin: auto 1px">
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="ec_is_active">
                                <label class="custom-control-label" for="ec_is_active" style="margin-left: 20px;">Включено</label>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="btn-save"><i class="far fa-save mr-1"></i> Проверить и сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editButtonCheck" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel">Редактирование кнопки</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="button_check">Название</label>
                        <input id="button_check" class="form-control" placeholder="Напишите название кнопки">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="saveButtonCheck();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
@endsection
