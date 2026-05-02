@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-question-circle mr-1"></i> Вопрос-ответ
                </h5>
                <a data-toggle="modal" data-target="#createFaq" href="#" class="btn btn-sm btn-color shadow px-3">
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
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по FAQ">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Вопрос</th>
                            <th class="font-weight-normal text-uppercase">Расположение</th>
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
    <div class="modal fade" id="createFaq" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-file mr-1" style="font-size: 16px"></i> Новый вопрос-ответ</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="in_id">Расположение</label>
                        <select class="form-control" id="in_id">
                            <option value="0">По умолчанию</option>
                        </select>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="question">Вопрос</label>
                        <input id="question" class="form-control" placeholder="Укажите вопрос">
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="text_answer">Ответ</label>
                        <div id="text_answer_toolbar"></div>
                        <div id="text_answer"></div>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="visibility">Видимость</label>
                        <select class="form-control" id="visibility">
                            <option value="1" selected>Общедоступно</option>
                            <option value="0">Скрыто из общего доступа</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="createFaq();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="changeFaq" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-edit mr-1" style="font-size: 16px"></i> Редактировать вопрос-ответ</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="in_id">Расположение</label>
                        <select class="form-control" id="in_id">
                            <option value="0">По умолчанию</option>
                        </select>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="question">Вопрос</label>
                        <input id="question" class="form-control" placeholder="Укажите вопрос">
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="text_answer">Ответ</label>
                        <div id="text_answer_toolbar"></div>
                        <div id="text_answer"></div>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="visibility">Видимость</label>
                        <select class="form-control" id="visibility">
                            <option value="1" selected>Общедоступно</option>
                            <option value="0">Скрыто из общего доступа</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="changeFaq();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
@endsection
