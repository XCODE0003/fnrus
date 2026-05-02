@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-file mr-1"></i> Инструкции
                </h5>
                <a data-toggle="modal" data-target="#createInstruction" href="#" class="btn btn-sm btn-color shadow px-3">
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
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по инструкциям">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Название</th>
                            <th class="font-weight-normal text-uppercase">Просмотры</th>
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
    <div class="modal fade" id="createInstruction" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-file mr-1" style="font-size: 16px"></i> Новая инструкция</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="pids">Товары</label>
                        <select class="selectpicker" style="padding: 14px 24px" id="pids" data-live-search="true" data-selected-text-format="count>0" data-width="100%" multiple></select>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="title">Название</label>
                        <input id="title" class="form-control"  onchange="getInstructionAlias('#createInstruction');"  placeholder="Введите название">
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="text_instruction">Текст инструкции</label>
                        <div id="text_instruction_toolbar"></div>
                        <div id="text_instruction"></div>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="alias">Короткий адрес</label>
                        <input id="alias" class="form-control" placeholder="Введите короткий адрес">
                    </div>
                    <div class="form-group text-left mt-4" id="block_buttons" style="">
                        <label class="form-label d-block">Кнопки</label>
                        <div id="blocks_buttons"></div>
                        <button class="btn btn-outline-secondary w-100 mb-2" id="btn-add-button" onclick="addBlockButton(2,'createInstruction')"><i class="far fa-plus mr-1"></i> Добавить кнопку</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="createInstruction();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="changeInstruction" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-edit mr-1" style="font-size: 16px"></i> Редактировать инструкцию</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="pids">Товары</label>
                        <select class="selectpicker" style="padding: 14px 24px" id="pids" data-live-search="true" data-selected-text-format="count>0" data-width="100%" multiple></select>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="title">Название</label>
                        <input id="title" class="form-control" placeholder="Введите название">
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="text_instruction">Текст инструкции</label>
                        <div id="text_instruction_toolbar"></div>
                        <div id="text_instruction"></div>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="alias">Короткий адрес</label>
                        <input id="alias" class="form-control" placeholder="Введите короткий адрес">
                    </div>
                    <div class="form-group text-left mt-4" id="block_buttons" style="">
                        <label class="form-label d-block">Кнопки</label>
                        <div id="blocks_buttons"></div>
                        <button class="btn btn-outline-secondary w-100 mb-2" id="btn-add-button" onclick="addBlockButton(2,'changeInstruction')"><i class="far fa-plus mr-1"></i> Добавить кнопку</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="changeInstruction();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
@endsection
