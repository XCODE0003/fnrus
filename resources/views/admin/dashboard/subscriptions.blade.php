@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="m-0">
                    <i class="far fa-fw fa-list-alt mr-1"></i> Мои подписки
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Действует от</th>
                            <th class="font-weight-normal text-uppercase">Истекает</th>
                            <th class="font-weight-normal text-uppercase">Цена</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
