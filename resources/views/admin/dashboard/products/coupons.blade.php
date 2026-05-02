@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0"><i class="far fa-badge-percent mr-1"></i> Купоны</h5>
                <a data-toggle="modal" data-target="#newCoupon  " href="#" class="btn btn-sm btn-color shadow px-3"><i class="far fa-plus"></i> <span class="text d-none d-lg-inline ml-1">Создать</span></a>
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
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по купонам">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase" style="width: 150px">Товар</th>
                            <th class="font-weight-normal text-uppercase">Скидка</th>
                            <th class="font-weight-normal text-uppercase">Купон</th>
                            <th class="font-weight-normal text-uppercase">Мин. лимит</th>
                            <th class="font-weight-normal text-uppercase">Лимит</th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newCoupon" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title m-0"><i class="far fa-fw fa-plus mr-1"></i> Новый купон</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group text-left">
                            <label for="gids">Товары</label>
                            <select class="selectpicker" style="padding: 14px 24px" id="gids" data-actions-box="true" data-live-search="true" data-selected-text-format="count>0" data-width="100%" multiple></select>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Код купона</label>
                            <input id="code" class="form-control" placeholder="Введите код купона"/>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Скидка </label>
                        </div>
                        <div class="input-group text-left">
                            <div class="input-group-prepend">
                                <input id="sale" type="number" class="form-control" placeholder="Укажите число" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;" />
                            </div>
                            <select id="sale_type" class="custom-select" style="border-left: 2px solid #171E2A;">
                                <option value="0">процентов</option>
                                <option value="1">долларов</option>
                            </select>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Минимальная сумма</label>
                            <input id="min_sum" type="number" class="form-control" placeholder="Введите число"/>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Минимальный лимит</label>
                        </div>
                        <div class="input-group text-left mb-3">
                            <div class="input-group-prepend">
                                <input id="count_uses_min" type="number" class="form-control" placeholder="Укажите число" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;" value="1" />
                            </div>
                            <select id="count_uses_type" class="custom-select" style="border-left: 2px solid #171E2A;">
                                <option value="0" selected>штук</option>
                                <option value="1">долларов</option>
                            </select>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Лимит использований</label>
                            <input id="count_uses_max" type="number" class="form-control" placeholder="Введите число"/>
                        </div>

                        <div class="form-group text-left mt-4">
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="add_is_new_users">
                                <label class="custom-control-label" for="add_is_new_users" style="margin-left: 20px;">Только для новых пользователей</label>
                            </div>
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="add_is_one_time">
                                <label class="custom-control-label" for="add_is_one_time" style="margin-left: 20px;">Единоразовое использование</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="createCoupon();return false;"><i class="far fa-save mr-1"></i> Сохранить</button>
                    </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCoupon" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title m-0"><i class="far fa-fw fa-edit mr-1" style="font-size: 16px"></i> Изменение купона</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group text-left">
                            <label for="gids">Товары</label>
                            <select class="selectpicker" style="padding: 14px 24px" id="gids" data-actions-box="true" data-live-search="true" data-selected-text-format="count>0" data-width="100%" multiple></select>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Код купона</label>
                            <input id="code" class="form-control" placeholder="Введите код купона"/>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Скидка </label>
                        </div>
                        <div class="input-group text-left">
                            <div class="input-group-prepend">
                                <input id="sale" type="number"  class="form-control" placeholder="Укажите число" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;" />
                            </div>
                            <select id="sale_type" class="custom-select" style="border-left: 2px solid #171E2A;">
                                <option value="0">процентов</option>
                                <option value="1">долларов</option>
                            </select>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Минимальная сумма</label>
                            <input id="min_sum" type="number" class="form-control" placeholder="Введите число"/>
                        </div>

                        <div class="form-group text-left mt-4">
                            <label>Минимальный лимит</label>
                        </div>
                        <div class="input-group text-left mb-3">
                            <div class="input-group-prepend">
                                <input id="count_uses_min" type="number" class="form-control" placeholder="Укажите число" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;" value="1" />
                            </div>
                            <select id="count_uses_type" class="custom-select" style="border-left: 2px solid #171E2A;">
                                <option value="0" selected>штук</option>
                                <option value="1">долларов</option>
                            </select>
                        </div>
                        <div class="form-group text-left mt-4">
                            <label>Лимит использований</label>
                            <input id="count_uses_max" type="number" class="form-control" placeholder="Введите число"/>
                        </div>
                        <div class="form-group text-left mt-4">
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="edit_is_new_users">
                                <label class="custom-control-label" for="edit_is_new_users" style="margin-left: 20px;">Только для новых пользователей</label>
                            </div>
                            <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                <input type="checkbox" class="custom-control-input d-block" id="edit_is_one_time">
                                <label class="custom-control-label" for="edit_is_one_time" style="margin-left: 20px;">Единоразовое использование</label>
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
