@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="padding">
            <div class="row justify-content-center mt-5">

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="card shadow mb-4">
                            <div class="card-body p-5 bg-primary">
                                <h6 class="card-price text-center font-weight-bold text-white"><span style="font-size: 40px;position: relative;bottom:10px;right:5px">$</span>9.90</h6>
                                <span class="period text-white">ежемесячно</span>
                                <hr class="mb-2" style="border-color:#02adf0">
                                <ul class="text-center ml-0 pl-0 mb-4 text-gray-900">
                                    <li class="d-block text-white">Отключение рекламы в боте</li>
                                    <hr class="my-2" style="border-color:#02adf0">
                                    <li class="d-block text-white m-0">Функция обязательной подписка</li>
                                    <hr class="my-2" style="border-color:#02adf0">
                                    <li class="d-block text-white m-0">10 способов оплаты</li>
                                    <hr class="my-2" style="border-color:#02adf0">
                                    <li class="d-block text-white m-0">Поддержка 24/7</li>
                                </ul>
                                <a style="color:#fff;cursor: pointer;" href="invoice/6385cead75e101405c6efc1" class="btn btn-block btn-light text-primary font-weight-bold mt-4 mb-4"><i class="far fa-credit-card mr-1"></i> Оплатить</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="card shadow mb-4">
                            <div class="card-body p-5">
                                <h6 class="card-price text-center font-weight-bold text-white"><span style="font-size: 40px;position: relative;bottom:10px;right:5px">$</span>89.90</h6>
                                <span class="period text-gray-900">ежегодно</span>
                                <hr class="mb-2">
                                <ul class="text-center ml-0 pl-0 mb-4 text-gray-900">
                                    <li class="d-block">Отключение рекламы в боте</li>
                                    <hr class="my-2">
                                    <li class="d-block m-0">Функция обязательной подписка</li>
                                    <hr class="my-2">
                                    <li class="d-block m-0">10 способов оплаты</li>
                                    <hr class="my-2">
                                    <li class="d-block m-0">Поддержка 24/7</li>
                                </ul>
                                <a style="color:#fff;cursor: pointer;" href="invoice/ea33c939dbbcf23acdb45a6c1ed0882f" class="btn btn-block btn-primary font-weight-bold mt-4 mb-4"><i class="far fa-credit-card mr-1"></i> Оплатить</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
