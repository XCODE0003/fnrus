@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0"><i class="far fa-list-alt mr-1"></i> Категории</h5>
                <a data-toggle="modal" data-target="#addCategory" href="#" class="btn btn-sm btn-color shadow px-3"><i class="far fa-plus"></i> <span class="text d-none d-lg-inline ml-1">Добавить</span></a>
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
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Название</th>
                            <th class="font-weight-normal text-uppercase">Категория</th>
{{--                            <th class="font-weight-normal text-uppercase">Тип</th>--}}
                            <th class="font-weight-normal text-uppercase">Товаров</th>
                            <th class="font-weight-normal text-uppercase">Просмотры</th>
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

    <div class="modal fade" id="addCategory" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-fw fa-plus mr-1" style="font-size: 16px"></i> Добавить категорию</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
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
                                    <label id="btn_upload_image" for="ac_image_upload_site" class="custom-file-upload text-center mb-0">
                                        <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                    </label>
                                    <input id="ac_image_upload_site" type="file" style="display: none;" name="file">
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
                                    <label id="btn_upload_image" for="ac_image_upload" class="custom-file-upload text-center mb-0">
                                        <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                    </label>
                                    <input id="ac_image_upload" type="file" style="display: none;" name="file">
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="title">Название</label>
                            <input id="title" class="form-control" placeholder="Напишите название" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="seo_description">Краткое описание (для SEO)</label>
                            <input id="seo_description" class="form-control" placeholder="Напишите краткое описание" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="seo_keywords">Ключевые слова (для SEO)</label>
                            <input id="seo_keywords" class="form-control" placeholder="Напишите ключевые слова" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="cid">Категория</label>
                            <select class="form-control" id="cid">
                                <option value="0">Без категории</option>
                            </select>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="text_message">Описание</label>
                            <div id="text_message_toolbar"></div>
                            <div id="text_message"></div>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="count_column">Кол-во колонок</label>
                            <input id="count_column" class="form-control" placeholder="Введите число от 1 до 5" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="alias">Короткий адрес</label>
                            <input id="alias" class="form-control" placeholder="Напишите адрес" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="visibility">Видимость</label>
                            <select class="form-control" id="visibility">
                                <option value="1">Общедоступно</option>
                                <option value="2">Только на сайте</option>
                                <option value="3">Только в боте</option>
                                <option value="0">Скрыто</option>
                            </select>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="display_products">Отображение товаров</label>
                            <select class="form-control" id="display_products">
                                <option value="0">В виде слайдера</option>
                                <option value="1">По категориям</option>
                            </select>
                        </div>
                        <div class="form-group text-left mt-4">
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="nc_disable_web_page_preview">
                                <label class="custom-control-label" for="nc_disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                            </div>
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="nc_has_spoiler" disabled>
                                <label class="custom-control-label" for="nc_has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="addCategory();return false;"><i class="far fa-save mr-1"></i> Сохранить</button>
                    </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCategory" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title m-0"><i class="far fa-fw fa-edit mr-1" style="font-size: 16px"></i> Изменение категории</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
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
                                    <label id="btn_upload_image" for="ec_image_upload_site" class="custom-file-upload text-center mb-0">
                                        <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                    </label>
                                    <input id="ec_image_upload_site" type="file" style="display: none;" name="file">
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
                                    <label id="btn_upload_image" for="ec_image_upload" class="custom-file-upload text-center mb-0">
                                        <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                    </label>
                                    <input id="ec_image_upload" type="file" style="display: none;" name="file">
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="title">Название</label>
                            <input id="title" class="form-control" placeholder="Напишите название" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="seo_description">Краткое описание (для SEO)</label>
                            <input id="seo_description" class="form-control" placeholder="Напишите краткое описание" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="seo_keywords">Ключевые слова (для SEO)</label>
                            <input id="seo_keywords" class="form-control" placeholder="Напишите ключевые слова" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="cid">Категория</label>
                            <select class="form-control" id="cid">
                                <option value="0">Без категории</option>
                            </select>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="text_message">Описание</label>
                            <div id="text_message_toolbar"></div>
                            <div id="text_message"></div>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="count_column">Кол-во колонок</label>
                            <input id="count_column" class="form-control" placeholder="Введите число от 1 до 5" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="alias">Короткий адрес</label>
                            <input id="alias" class="form-control" placeholder="Напишите адрес" />
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="visibility">Видимость</label>
                            <select class="form-control" id="visibility">
                                <option value="1">Общедоступно</option>
                                <option value="2">Только на сайте</option>
                                <option value="3">Только в боте</option>
                                <option value="0">Скрыто</option>
                            </select>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label for="display_products">Отображение товаров</label>
                            <select class="form-control" id="display_products">
                                <option value="0">В виде слайдера</option>
                                <option value="1">По категориям</option>
                            </select>
                        </div>
                        <div class="form-group text-left mt-4">
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="ec_disable_web_page_preview">
                                <label class="custom-control-label" for="ec_disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                            </div>
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="ec_has_spoiler">
                                <label class="custom-control-label" for="ec_has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="btn-save"><i class="far fa-save mr-1"></i> Сохранить</button>
                    </div>
            </div>
        </div>
    </div>
@endsection
