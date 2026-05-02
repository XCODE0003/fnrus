@extends('admin.dashboard.layouts.main')
@section('content')
    <style>
        #addProduct #description.ql-container,
        #addProduct #description .ql-editor,
        #editProduct #description.ql-container,
        #editProduct #description .ql-editor {
            color: #fff;
        }
    </style>
    <div class="container-fluid">
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0"><i class="far fa-fw fa-shopping-bag mr-1"></i> Товары</h5>
                <a data-toggle="modal" data-target="#addProduct" href="#" class="btn btn-sm btn-color shadow px-3"><i class="far fa-plus"></i> <span class="text d-none d-lg-inline ml-1">Добавить</span></a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <select class="form-control form-control-sm my-1" id="input-length">
                            <option value="10">Показать: 10</option>
                            <option value="25">Показать: 25</option>
                            <option value="50">Показать: 50</option>
                            <option value="100">Показать: 100</option>
                            <option value="500">Показать: 500</option>
                            <option value="1000">Показать: 1000</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control form-control-sm my-1" id="input-cat-id">
                            <option value="">Категория: Любая</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по товарам">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Название</th>
                            <th class="font-weight-normal text-uppercase">Категория</th>
                            <th class="font-weight-normal text-uppercase">Цена</th>
                            <th class="font-weight-normal text-uppercase">Продано</th>
                            <th class="font-weight-normal text-uppercase">Остаток</th>
                            <th class="font-weight-normal text-uppercase">Просмотры</th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exportMaterials" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title m-0"><i class="far fa-cloud-download mr-1" style="font-size: 16px"></i> Экспорт: <span id="p_title"></span></h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group text-left">
                            <label for="tid">Тариф</label>
                            <select class="form-control" id="tid">
                            </select>
                        </div>
                        <div class="form-group text-left">
                            <label>Сколько экспортировать</label>
                            <input id="count" type="number" class="form-control" placeholder="Введите число" />
                        </div>
                        <div class="form-group text-left">
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="remove_from_stock">
                                <label class="custom-control-label" for="remove_from_stock" style="margin-left: 20px;">Убрать из наличия</label>
                            </div>
                        </div>
                        <div class="form-group text-left d-none" id="block-response">
                            <textarea id="result-body" class="form-control" style="height: 185px"></textarea>
                            <div class="form-row mt-2">
                                <div class="col p-1">
                                    <a class="btn btn-dark w-100" onclick="copy('result-body',1)" href="javascript:;"><i class="far fa-copy mr-1"></i> Скопировать</a>
                                </div>
                                <div class="col p-1">
                                    <button class="btn btn-success w-100" id="btn-download"><i class="far fa-download mr-1"></i> Скачать</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="btn-save"><i class="far fa-cloud-download mr-1"></i> Выгрузить</button>
                    </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addProduct" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 800px" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title m-0"><i class="far fa-fw fa-plus mr-1" style="font-size: 16px"></i> Добавить товар</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group text-left" id="image_site">
                                <label>Обложка для сайта</label>
                                <div class="row">
                                    <div class="col-10">
                                        <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                        <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Изображение загружено</div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-3" id="btn_delete_image"></div>
                                    </div>
                                    <div class="col-12">
                                        <label id="btn_upload_image" for="ap_image_upload_site" class="custom-file-upload text-center mb-0">
                                            <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                        </label>
                                        <input id="ap_image_upload_site" type="file" style="display: none;" name="file">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group text-left mt-4" id="image_bot">
                                <label>Обложка для бота</label>
                                <div class="row">
                                    <div class="col-10">
                                        <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                        <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Изображение загружено</div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-3" id="btn_delete_image"></div>
                                    </div>
                                    <div class="col-12">
                                        <label id="btn_upload_image" for="ap_image_upload" class="custom-file-upload text-center mb-0">
                                            <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                        </label>
                                        <input id="ap_image_upload" type="file" style="display: none;" name="file">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Галерея изображений</label>
                                <form id="uploader" action="/api/attachments/image/upload" class="dropzone custom-file-upload text-center">
                                </form>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Название</label>
                                <input id="title" class="form-control" placeholder="Напишите название" />
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Краткое описание (для SEO)</label>
                                <input id="seo_description" class="form-control" placeholder="Напишите краткое описание" />
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Ключевые слова (для SEO)</label>
                                <input id="seo_keywords" class="form-control" placeholder="Напишите ключевые слова" />
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Категория</label>
                                <select class="form-control" id="cid">
                                </select>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label for="description">Описание</label>
                                <div id="description_toolbar"></div>
                                <div id="description" style="height: 100px;"></div>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label for="advantages">Приемущества</label>
                                <textarea id="advantages" class="form-control" placeholder="Напишите приемущества"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group text-left mt-4">
                                <label for="functional" class="form-label d-block">Функционал</label>
                                <div id="blocks_functional"></div>
                                <button class="btn btn-primary w-100" id="btn-add-functional" onclick="addBlockFunctional(2,'addProduct')"><i class="fas fa-plus me-1"></i> Добавить блок</button>
                            </div>

                            <div id="block_tariff" class="form-group text-left mt-4">
                                <label for="tariff" class="form-label d-block">Тарифы</label>
                                <div class="row" data-id="1">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <input type="number" class="form-control" id="days" placeholder="Кол-во дней">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <input type="number" class="form-control" id="price" placeholder="Цена">
                                        </div>
                                    </div>
                                </div>
                                <div id="blocks_tariffs"></div>
                                <button class="btn btn-primary w-100" id="btn-add-tariff" onclick="addBlockTariff('addProduct');"><i class="fas fa-plus me-1"></i> Добавить тариф</button>
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="system_versions">Поддерживаемые языка</label>
                                <input id="system_versions" class="form-control" placeholder="Напишите языки через запятую" />
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="system_auth">Авторизация в софте</label>
                                <input id="system_auth" class="form-control" placeholder="Напишите через что происходит авторизация" />
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="status">Статус софта</label>
                                <select class="form-control" id="status">
                                    <option value="0">Не выбран</option>
                                </select>
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="link_video" class="form-label">Ссылка на видеообзор</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="link_video" placeholder="Вставьте ссылку">
                                    <div class="input-group-append">
                                        <label class="btn btn-outline-primary mb-0" style="cursor:pointer">
                                            <i class="far fa-upload"></i>
                                            <input type="file" class="video-upload-input" accept="video/mp4,video/webm,video/quicktime" style="display:none">
                                        </label>
                                    </div>
                                </div>
                                <small class="form-text text-muted video-upload-status"></small>
                            </div>

                            <div class="form-group text-left mt-4">
                                <label>Короткий адрес</label>
                                <input id="alias" class="form-control" placeholder="Напишите короткий адрес" />
                            </div>

                            <div class="form-group text-left mt-4">
                                <label>Видимость</label>
                                <select class="form-control" id="visibility">
                                    <option value="1">Общедоступно</option>
                                    <option value="2">Только на сайте</option>
                                    <option value="3">Только в боте</option>
                                    <option value="0">Скрыто</option>
                                </select>
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="hack_status">Взлом</label>
                                <select class="form-control" id="hack_status">
                                    <option value="0">Не требуется</option>
                                    <option value="1">Jailbreak</option>
                                    <option value="2">Без Jailbreak</option>
                                    <option value="3">Root</option>
                                    <option value="4">Без Root</option>
                                    <option value="5">С обходом</option>
                                    <option value="6">Без обхода</option>
                                </select>
                            </div>

                            <div class="form-group text-left mx-2 mt-4">
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="ap_disable_web_page_preview">
                                    <label class="custom-control-label" for="ap_disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                                </div>
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="ap_has_spoiler">
                                    <label class="custom-control-label" for="ap_has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                                </div>
                                <hr />
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="ap_edit_count_max">
                                    <label class="custom-control-label" for="ap_edit_count_max" style="margin-left: 20px;">Разрешить покупку только один раз</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="addProduct();return false;"><i class="far fa-save mr-1"></i> Сохранить</button>
                    </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProduct" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 800px" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title m-0"><i class="far fa-fw fa-edit mr-1" style="font-size: 16px"></i> Изменение товара</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group text-left" id="image_site">
                                <label>Обложка для сайта</label>
                                <div class="row">
                                    <div class="col-10">
                                        <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                        <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Изображение загружено</div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-3" id="btn_delete_image"></div>
                                    </div>
                                    <div class="col-12">
                                        <label id="btn_upload_image" for="ep_image_upload_site" class="custom-file-upload text-center mb-0">
                                            <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                        </label>
                                        <input id="ep_image_upload_site" type="file" style="display: none;" name="file">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group text-left mt-4" id="image_bot">
                                <label>Обложка для бота</label>
                                <div class="row">
                                    <div class="col-10">
                                        <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                        <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Изображение загружено</div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-3" id="btn_delete_image"></div>
                                    </div>
                                    <div class="col-12">
                                        <label id="btn_upload_image" for="ep_image_upload" class="custom-file-upload text-center mb-0">
                                            <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                        </label>
                                        <input id="ep_image_upload" type="file" style="display: none;" name="file">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Галерея изображений</label>
                                <form id="uploader" action="/api/attachments/image/upload" class="dropzone custom-file-upload text-center">
                                </form>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Название</label>
                                <input id="title" class="form-control" placeholder="Напишите название" />
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Краткое описание (для SEO)</label>
                                <input id="seo_description" class="form-control" placeholder="Напишите краткое описание" />
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Ключевые слова (для SEO)</label>
                                <input id="seo_keywords" class="form-control" placeholder="Напишите ключевые слова" />
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Категория</label>
                                <select class="form-control" id="cid">
                                </select>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label for="description">Описание</label>
                                <div id="description_toolbar"></div>
                                <div id="description" style="height: 100px;"></div>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label for="advantages">Приемущества</label>
                                <textarea id="advantages" class="form-control" placeholder="Напишите приемущества"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group text-left mt-4">
                                <label for="functional" class="form-label d-block">Функционал</label>
                                <div id="blocks_functional"></div>
                                <button class="btn btn-primary w-100" id="btn-add-functional" onclick="addBlockFunctional(99,'editProduct')"><i class="fas fa-plus me-1"></i> Добавить блок</button>
                            </div>

                            <div id="block_tariff" class="form-group text-left mt-4">
                                <label for="tariff" class="form-label d-block">Тарифы</label>
                                <div id="blocks_tariffs"></div>
                                <button class="btn btn-primary w-100" id="btn-add-tariff" onclick="addBlockTariff('editProduct');"><i class="fas fa-plus me-1"></i> Добавить тариф</button>
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="system_versions">Поддерживаемые языка</label>
                                <input id="system_versions" class="form-control" placeholder="Напишите языки через запятую" />
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="system_auth">Авторизация в софте</label>
                                <input id="system_auth" class="form-control" placeholder="Напишите через что происходит авторизация" />
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="status">Статус софта</label>
                                <select class="form-control" id="status">
                                    <option value="0">Не выбран</option>
                                </select>
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="link_video" class="form-label">Ссылка на видеообзор</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="link_video" placeholder="Вставьте ссылку">
                                    <div class="input-group-append">
                                        <label class="btn btn-outline-primary mb-0" style="cursor:pointer">
                                            <i class="far fa-upload"></i>
                                            <input type="file" class="video-upload-input" accept="video/mp4,video/webm,video/quicktime" style="display:none">
                                        </label>
                                    </div>
                                </div>
                                <small class="form-text text-muted video-upload-status"></small>
                            </div>

                            <div class="form-group text-left mt-4">
                                <label>Короткий адрес</label>
                                <input id="alias" class="form-control" placeholder="Напишите короткий адрес" />
                            </div>

                            <div class="form-group text-left mt-4">
                                <label>Видимость</label>
                                <select class="form-control" id="visibility">
                                    <option value="1">Общедоступно</option>
                                    <option value="2">Только на сайте</option>
                                    <option value="3">Только в боте</option>
                                    <option value="0">Скрыто</option>
                                </select>
                            </div>

                            <div class="form-group text-left mt-4">
                                <label for="hack_status">Взлом</label>
                                <select class="form-control" id="hack_status">
                                    <option value="0">Не требуется</option>
                                    <option value="1">Jailbreak</option>
                                    <option value="2">Без Jailbreak</option>
                                    <option value="3">Root</option>
                                    <option value="4">Без Root</option>
                                    <option value="5">С обходом</option>
                                    <option value="6">Без обхода</option>
                                </select>
                            </div>

                            <div class="form-group text-left mx-2 mt-4">
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="ep_disable_web_page_preview">
                                    <label class="custom-control-label" for="ep_disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                                </div>
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="ep_has_spoiler">
                                    <label class="custom-control-label" for="ep_has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                                </div>
                                <hr />
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="ep_edit_count_max">
                                    <label class="custom-control-label" for="ep_edit_count_max" style="margin-left: 20px;">Разрешить покупку только один раз</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="btn-save"><i class="far fa-save mr-1"></i> Сохранить</button>
                    </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addMaterial" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title m-0"><i class="far fa-fw fa-plus mr-1" style="font-size: 16px"></i> Добавить материал: <span id="p_title"></span></h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group text-left">
                            <label for="tid">Тариф</label>
                            <select class="form-control" id="tid">
                            </select>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="body">Содержимое</label>
                            <textarea id="body" onkeyup="countLines('#addMaterial');" class="form-control" placeholder="Вставьте материал (один в строчку)" style="min-width: 251px;height: 150px;"></textarea>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label>Файл (txt)</label>
                            <div class="d-none" id="block_upload_file" data-attach=""></div>
                            <label for="file_upload" class="custom-file-upload text-center m-0">
                                <i class="far fa-upload mr-1" style="font-size:15px"></i> Выбрать файл
                            </label>
                            <input id="file_upload" type="file" style="display: none;" name="file">
                            <div id="btn_delete_file"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="btn-save"><i class="far fa-save mr-1"></i> Сохранить</button>
                    </div>
            </div>
        </div>
    </div>

@endsection
