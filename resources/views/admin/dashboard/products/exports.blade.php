@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-0">
        </div>
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="m-0"><i class="far fa-fw fa-cloud-download mr-1"></i> История экспорта</h5>
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
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по экспорту">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Товар</th>
                            <th class="font-weight-normal text-uppercase">Тариф</th>
                            <th class="font-weight-normal text-uppercase">Кол-во</th>
                            <th class="font-weight-normal text-uppercase">Наличие</th>
                            <th class="font-weight-normal text-uppercase">Дата</th>
                            <th style="width: 15px"></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
