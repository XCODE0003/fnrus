@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-user-tag mr-1"></i> Управление ролями
                </h5>
                <a data-toggle="modal" data-target="#addRole" href="#" class="btn btn-sm btn-color shadow px-3">
                    <i class="far fa-plus"></i>
                    <span class="text d-none d-lg-inline ml-1">Добавить</span>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Название</th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editRole" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0"><i class="far fa-user-tag mr-1" style="font-size: 16px"></i> Редактирование прав</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left mt-0">
                        <label for="title">Название</label>
                        <input id="title" class="form-control" placeholder="Введите название">
                        <button class="btn btn-primary w-100 mt-2" onclick="changeRole();"><i class="far fa-save mr-1"></i> Сохранить</button>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="permissions" class="mb-3">Разрешения</label>
                        <div class="form-group mx-2" id="permissions"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addRole" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0"><i class="far fa-user-tag mr-1" style="font-size: 16px"></i> Создание роли</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left mt-0">
                        <label for="title">Название</label>
                        <input id="title" class="form-control" placeholder="Введите название">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="createRole();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
@endsection
