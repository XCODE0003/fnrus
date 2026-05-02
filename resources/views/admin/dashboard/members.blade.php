@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-users mr-1"></i> Пользователи
                </h5>
                <a href="#" class="btn btn-sm btn-color shadow px-3 ml-2 mr-1">
                    <i class="far fa-upload"></i>
                    <span class="text d-none d-lg-inline ml-1">Импорт</span>
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
                        <select class="form-control form-control-sm my-1" id="input-role-id">
                            <option value="">Тип: Любой</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по пользователям">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Аккаунт</th>
                            <th class="font-weight-normal text-uppercase">Баланс</th>
                            <th class="font-weight-normal text-uppercase">Рефералов</th>
                            <th class="font-weight-normal text-uppercase">Реф. процент</th>
                            <th class="font-weight-normal text-uppercase">Вступил</th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editMember" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0"><i class="far fa-cog mr-1"></i> Изменение пользователя: <b id="m_user"></b></h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <label for="role_id" class="mt-0">Роль пользователя</label>
                    <div class="input-group mb-3">
                        <select class="form-control" id="role_id" aria-describedby="button-addon4"></select>
                        <div class="input-group-append" id="button-addon4">
                            <button class="btn btn-outline-primary" id="btn-save-role" type="button"><i class="fas fa-save"></i></button>
                        </div>
                    </div>
                    <label for="balance_value" class="mt-2">Баланс (<strong id="balance">0</strong>)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="balance_value" placeholder="Укажите целое число" aria-describedby="button-addon4">
                        <div class="input-group-append" id="button-addon4">
                            <button class="btn btn-outline-primary" id="btn-minus" type="button"><i class="fas fa-minus"></i></button>
                            <button class="btn btn-outline-primary" id="btn-plus" type="button"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <label for="ref_percent" class="mt-4">Реферальный процент</label>
                    <div class="input-group mb-3">
                        <input type="number" class="form-control" id="ref_percent" placeholder="Укажите целое число" aria-describedby="button-addon4">
                        <div class="input-group-append" id="button-addon4">
                            <button class="btn btn-outline-primary" id="btn-save" type="button"><i class="fas fa-save"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
