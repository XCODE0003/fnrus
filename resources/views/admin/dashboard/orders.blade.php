@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-0"></div>
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-clipboard-list mr-1"></i> Заказы
                </h5>
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
                        <select class="form-control form-control-sm my-1" id="input-status">
                            <option value="">Статус: Любой</option>
                            <option value="1">Статус: В процессе</option>
                            <option value="2">Статус: Оплачен</option>
                            <option value="3">Статус: Отменен</option>
                            <option value="4">Статус: Истек срок</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control form-control-sm my-1" id="input-method-payment">
                            <option value="">Способ оплаты: Любой</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по заказам">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th class="font-weight-normal text-uppercase">ID</th>
                            <th class="font-weight-normal text-uppercase">Товар</th>
                            <th class="font-weight-normal text-uppercase">Покупатель</th>
                            <th class="font-weight-normal text-uppercase">Способ</th>
                            <th class="font-weight-normal text-uppercase">Сумма</th>
                            <th class="font-weight-normal text-uppercase">Дата</th>
                            <th class="font-weight-normal text-uppercase">Статус</th>
                            <th></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="getOrder" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form>
                    <div class="modal-header">
                        <h5 class="modal-title m-0"><i class="far fa-fw fa-file-invoice-dollar mr-1" style="font-size: 16px"></i> Получение заказа: <b id="o_id"></b></h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group text-left">
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
                </form>
            </div>
        </div>
    </div>

@endsection
