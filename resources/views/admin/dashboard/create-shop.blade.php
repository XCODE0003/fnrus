@extends('admin.dashboard.layouts.no_main')
@section('content')
    <div class="container-fluid">
        <div class="padding">
            <div class="row justify-content-center">

                <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-header border-0 pt-4" data-id="one">
                                <h5 class="m-0 p-0 text-center"><i class="far fa-door-open mr-1"></i> Добро пожаловать</h5>
                            </div>
                            <div class="card-header border-0 pt-4 d-none" data-id="two">
                                <h5 class="m-0 p-0 text-center"><i class="far fa-robot mr-1"></i> Подключение бота</h5>
                            </div>
                            <div class="card-header border-0 pt-4 d-none" data-id="three">
                                <h5 class="m-0 p-0 text-center"><i class="far fa-shopping-basket mr-1"></i> Поздравляем</h5>
                            </div>
                            <div class="card-body p-4 text-center" data-id="one">
                                <p class="p-2 mb-2">
                                    Вам начислены <b>3 бонусных дня</b> для того вы смогли оценить полный функционал нашего онлайн-сервиса.
                                </p>
                                <button class="btn btn-primary mt-3 mb-2" onclick="nextStep('one','two');">Начать работу</button>
                            </div>
                            <div class="card-body p-4 d-none" data-id="two">
                                <p class="p-2 mb-2">
                                    Для начала необходимо создать бота в <a target="_blank" href="https://t.me/botFather">BotFather</a>,
                                    скопировать полученный токен и вставить в поле ниже.
                                    <a class="d-block" href="#">Инструкция: Как создать бота в BotFather</a>
                                </p>

                                    <div clss="form-group">
                                        <input type="text" class="form-control" id="token" placeholder="Вставьте секретный токен" autofocus>
                                    </div>
                                <button class="btn btn-primary w-100 mt-3 mb-2" onclick="createShop();">Продолжить</button>
                            </div>
                            <div class="card-body p-4 text-center d-none" data-id="three">
                                <p class="p-2 mb-2">
                                    Ваш магазин успешно создан<br>Осталось только добавить товары и способы оплаты!
                                </p>
                                <a class="btn btn-primary w-100 mt-3 mb-2" href="/products">Добавить товары</a>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection
