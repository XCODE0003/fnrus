@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-file mr-1"></i> Страницы
                </h5>
                <a data-toggle="modal" data-target="#createPage" href="#" class="btn btn-sm btn-color shadow px-3">
                    <i class="far fa-plus"></i>
                    <span class="text d-none d-lg-inline ml-1">Создать</span>
                </a>
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
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по страницам">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Название</th>
                            <th class="font-weight-normal text-uppercase">Просмотров</th>
                            <th class="font-weight-normal text-uppercase">Дата создания</th>
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
    <div class="modal fade" id="createPage" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-file mr-1" style="font-size: 16px"></i> Новая страница</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="title">Название</label>
                        <input id="title" class="form-control" placeholder="Введите название">
                    </div>
                    <div class="form-group text-left">
                        <label for="meta_description">Краткое описание (для SEO)</label>
                        <input id="meta_description" class="form-control" placeholder="Введите краткое описание">
                    </div>
                    <div class="form-group text-left">
                        <label for="meta_keywords">Ключевые слова (для SEO)</label>
                        <input id="meta_keywords" class="form-control" placeholder="Введите ключевые слова">
                    </div>
                    <div class="form-group text-left">
                        <label for="text_message">Текст</label>
                        <div id="text_message_toolbar"></div>
                        <div id="text_message"></div>
                    </div>
                    <div class="form-group text-left">
                        <label for="shortname">Короткий адрес</label>
                        <input id="shortname" class="form-control" placeholder="Введите короткий адрес">
                    </div>
                    <div class="form-group text-left">
                        <label class="mb-1">Изображение</label>
                        <div class="row">
                            <div class="col-10">
                                <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Загружено</div>
                            </div>
                            <div class="col-2">
                                <div class="ml-3" id="btn_delete_image"></div>
                            </div>
                            <div class="col-12">
                                <label id="btn_upload_image" for="cp_image_upload" class="custom-file-upload text-center mb-0">
                                    <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                </label>
                                <input id="cp_image_upload" type="file" style="display: none;" name="file">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="savePage();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editPage" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-edit mr-1" style="font-size: 16px"></i> Редактировать страницу</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="title">Название</label>
                        <input id="title" class="form-control" placeholder="Введите название">
                    </div>
                    <div class="form-group text-left">
                        <label for="meta_description">Краткое описание (для SEO)</label>
                        <input id="meta_description" class="form-control" placeholder="Введите краткое описание">
                    </div>
                    <div class="form-group text-left">
                        <label for="meta_keywords">Ключевые слова (для SEO)</label>
                        <input id="meta_keywords" class="form-control" placeholder="Введите ключевые слова">
                    </div>
                    <div class="form-group text-left">
                        <label for="text_message">Текст</label>
                        <div id="text_message_toolbar"></div>
                        <div id="text_message"></div>
                    </div>
                    <div class="form-group text-left">
                        <label for="shortname">Короткий адрес</label>
                        <input id="shortname" class="form-control" placeholder="Введите короткий адрес">
                    </div>
                    <div class="form-group text-left">
                        <label class="mb-1">Изображение</label>
                        <div class="row">
                            <div class="col-10">
                                <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Загружено</div>
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
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="savePage();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
@endsection
