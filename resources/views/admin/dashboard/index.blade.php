@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid" id="dashboard">
        <div class="row">
            <div class="col-xl-4 col-md-4 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center mx-3">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase">
                                    <span>Заработано</span>
                                    <select id="select_period_profits" class="custom-select-sm text-xs font-weight-bold text-uppercase cursor-pointer">
                                        <option value="1">сегодня</option>
                                        <option value="2">вчера</option>
                                        <option value="3">за 7 дней</option>
                                        <option value="4">за 30 дней</option>
                                        <option value="5">за все время</option>
                                    </select>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><span id="profits_value">0</span></div>
                            </div>
                            <div class="col-auto">
                                <i class="fal fa-coins fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-4 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center mx-3">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase">
                                    <span>Продаж</span>
                                    <select id="select_period_sales" class="custom-select-sm text-xs font-weight-bold text-uppercase cursor-pointer">
                                        <option value="1">сегодня</option>
                                        <option value="2">вчера</option>
                                        <option value="3">за 7 дней</option>
                                        <option value="4">за 30 дней</option>
                                        <option value="5">за все время</option>
                                    </select>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="sales_value">0</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fal fa-shopping-basket fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-4 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center mx-3">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase">
                                    <span>Пользователей</span>
                                    <select id="select_period_members" class="custom-select-sm text-xs font-weight-bold text-uppercase cursor-pointer">
                                        <option value="1">сегодня</option>
                                        <option value="2">вчера</option>
                                        <option value="3">за 7 дней</option>
                                        <option value="4">за 30 дней</option>
                                        <option value="5">за все время</option>
                                    </select>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="members_value">0</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fal fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

{{--            <div class="col-xl-4 col-md-4 mb-4">--}}
{{--                <div class="card shadow h-100 py-2">--}}
{{--                    <div class="card-body">--}}
{{--                        <div class="row no-gutters align-items-center mx-3">--}}
{{--                            <div class="col mr-2">--}}
{{--                                <div class="text-xs font-weight-bold text-uppercase mb-1">Осталось дней</div>--}}
{{--                                <div class="h5 mb-0 font-weight-bold text-gray-800">0</div>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto">--}}
{{--                                <i class="fal fa-calendar-alt fa-2x"></i>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
            {{-- Скрыто: бот временно не нужен
            <div class="col-xl-4 col-md-4 mb-4">
                <div class="card shadow h-100 py-1">
                    <div class="card-body py-3" style="padding-left: 36px!important;padding-right: 36px!important;">
                        <div class="row no-gutters align-items-center" style="height: 100%;">
                            <div class="col mr-2">
                                <div class="text-xs text-uppercase font-weight-bold" style="font-size: .8rem;">Состояние бота</div>
                            </div>
                            <div class="col-auto">
                                <button onclick="setStatus();return false;" id="btn-status-change" type="button" class="btn btn-success btn-sm btn-toggle mr-0">
                                    <div class="handle"></div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            --}}
            <div class="col-xl-12 col-md-12 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header border-0">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="far fa-fw fa-analytics mr-1 text-white"></i> Топ продаж
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="datatable" сlass="mdl-data-table w-100" id="top_sales">
                                <thead>
                                <tr>
                                    <th class="font-weight-normal text-uppercase">Товар</th>
                                    <th class="font-weight-normal text-uppercase">Заказов</th>
                                    <th class="font-weight-normal text-uppercase">Сумма</th>
                                    <th class="font-weight-normal text-uppercase">Просмотров</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
{{--            <div class="col-xl-4 col-md-4 mb-4">--}}
{{--                <div class="card shadow h-100">--}}
{{--                    <div class="card-header border-0">--}}
{{--                        <h6 class="m-0 font-weight-bold text-white">--}}
{{--                            <i class="far fa-fw fa-bullseye-arrow mr-1 text-white"></i> Партнеры--}}
{{--                        </h6>--}}
{{--                    </div>--}}
{{--                    <div class="card-body">--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
        </div>
    </div>
@endsection
