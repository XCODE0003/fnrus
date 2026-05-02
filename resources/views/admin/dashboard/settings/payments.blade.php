@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
            <div class="card mb-4" id="payments">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mr-auto m-0">
                        <i class="far fa-fw fa-credit-card mr-1"></i> Настройки оплаты
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row" id="methods"></div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editMethod" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0"><i class="far fa-credit-card mr-1" style="font-size: 16px"></i> Способ оплаты: <span id="title"></span></h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body pb-4 pt-3">
                    <div class="m-0" id="method_qw">
                        <div id="block_phone">
                            <div class="form-group">
                                <label for="phone">Номер телефона</label>
                                <input type="text" class="form-control" id="phone" placeholder="Введите номер телефона">
                            </div>
                            <div class="form-group mt-4">
                                <label for="password">Пароль</label>
                                <input type="password" class="form-control" id="password" placeholder="Введите пароль">
                            </div>
                            <div class="form-group mt-4">
                                <label for="link_widget">Ссылка на виджет</label>
                                <input type="text" class="form-control" id="link_widget" placeholder="Вставьте ссылку на виджет">
                            </div>
                            <div class="alert alert-primary mt-3" role="alert">
                                <b>Внимание!</b> Мы не храним логин и пароль от вашего кошелька, он используется лишь только для создания ключей
                            </div>
                        </div>
                        <div id="block_keys">
                            <div class="form-group">
                                <label for="public_key">Публичный ключ</label>
                                <input type="text" class="form-control" id="public_key">
                            </div>
                            <div class="form-group mt-4">
                                <label for="secret_key">Секретный ключ</label>
                                <input type="text" class="form-control" id="secret_key">
                            </div>
                            <div class="form-group mt-4">
                                <label for="theme_code">Код виджета</label>
                                <input type="text" class="form-control" id="theme_code">
                            </div>
                        </div>
                    </div>
                    <div class="m-0" id="method_ym">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/ym">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">Номер кошелька</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте номер кошелька">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный ключ</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный ключ">
                        </div>
                    </div>
                    <div class="m-0" id="method_bt">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/bt">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_key">Публичный ключ</label>
                            <input type="text" class="form-control" id="public_key" placeholder="Вставьте публичный ключ">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный ключ</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный ключ">
                        </div>
                        <div class="form-group mt-4">
                            <label>Платежные системы</label>
                            <div class="row" id="assets"></div>
                        </div>
                    </div>
                    <div class="m-0" id="method_bn">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/bn">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_key">Публичный ключ</label>
                            <input type="text" class="form-control" id="public_key" placeholder="Вставьте публичный ключ">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный ключ</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный ключ">
                        </div>
                        <div class="form-group mt-4">
                            <label>Платежные системы</label>
                            <div class="row" id="assets"></div>
                        </div>
                    </div>
                    <div class="m-0" id="method_sp">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/sp">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_key">Публичный ключ</label>
                            <input type="text" class="form-control" id="public_key" placeholder="Вставьте публичный ключ">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный ключ</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный ключ">
                        </div>
                    </div>
                    <div class="m-0" id="method_sm">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/sm" readonly>
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">Shop ID</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте Shop ID StreamPay">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">API-ключ (Bearer Token)</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте API-ключ StreamPay">
                        </div>
                    </div>
                    <div class="m-0" id="method_et">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/et">
                        </div>
                        <div class="form-group">
                            <label for="public_id">ID кассы</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте ID кассы">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретное слово #1</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретное слово #1">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key_two">Секретное слово #2</label>
                            <input type="text" class="form-control" id="secret_key_two" placeholder="Вставьте секретное слово #2">
                        </div>
                    </div>
                    <div class="m-0" id="method_lv">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/lv">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">ID кассы</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте ID кассы">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный ключ</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный ключ">
                        </div>
                    </div>
                    <div class="m-0" id="method_ap">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/ap">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">ID кассы</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте ID кассы">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный токен</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный токен">
                        </div>
                        <div class="form-group mt-4">
                            <label>Платежные системы</label>
                            <div class="row" id="assets"></div>
                        </div>
                    </div>
                    <div class="m-0" id="method_fk">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/fk">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">ID кассы</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте ID кассы">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_key">API ключ</label>
                            <input type="text" class="form-control" id="public_key" placeholder="Вставьте API ключ из merchant.freekassa.net">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретное слово #1</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретное слово #1">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key_two">Секретное слово #2</label>
                            <input type="text" class="form-control" id="secret_key_two" placeholder="Вставьте секретное слово #2">
                        </div>
                        <div class="form-group mt-4">
                            <label>Способы оплаты</label>
                            <div class="row" id="assets"></div>
                        </div>
                    </div>
                    <div class="m-0" id="method_ai">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/ai">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">ID кассы</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте ID кассы">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный ключ #1</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный ключ #1">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key_two">Секретный ключ #2</label>
                            <input type="text" class="form-control" id="secret_key_two" placeholder="Вставьте секретный ключ #2">
                        </div>
                        <div class="form-group mt-4">
                            <label>Платежные системы</label>
                            <div class="row" id="assets"></div>
                        </div>
                    </div>
                    <div class="m-0" id="method_pp">
                        <div class="form-group">
                            <label>Result URL (вставить в настройках магазина Pally)</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/pp" readonly>
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">Shop ID</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте Shop ID из настроек магазина Pally">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">API Token</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте API Token из раздела API интеграция">
                        </div>
                    </div>
                    <div class="m-0" id="method_rk">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/rk">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">ID кассы</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте ID кассы">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">API-токен</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте API-токен">
                        </div>
                    </div>
                    <div class="m-0" id="method_po">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/po">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">ID проекта</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте ID проекта">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный ключ</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный ключ">
                        </div>
                    </div>
                    <div class="m-0" id="method_cb">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/cb">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">API-токен</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте API-токен">
                        </div>
                        <div class="form-group mt-4">
                            <label>Выберите валюты</label>
                            <div class="row" id="assets">
                                <div class="col-6 py-2">
                                    <li class="d-flex justify-content-between align-items-center px-4 py-3" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">BTC</h6><div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cb_btc"><label class="custom-control-label" for="cb_btc"></label></div></div></li>
                                </div>
                                <div class="col-6 py-2">
                                    <li class="d-flex justify-content-between align-items-center px-4 py-3" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">TON</h6><div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cb_ton"><label class="custom-control-label" for="cb_ton"></label></div></div></li>
                                </div>
                                <div class="col-6 py-2">
                                    <li class="d-flex justify-content-between align-items-center px-4 py-3" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">ETH</h6><div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cb_eth"><label class="custom-control-label" for="cb_eth"></label></div></div></li>
                                </div>
                                <div class="col-6 py-2">
                                    <li class="d-flex justify-content-between align-items-center px-4 py-3" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">USDT</h6><div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cb_usdt"><label class="custom-control-label" for="cb_usdt"></label></div></div></li>
                                </div>
                                <div class="col-6 py-2">
                                    <li class="d-flex justify-content-between align-items-center px-4 py-3" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">USDC</h6><div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cb_usdc"><label class="custom-control-label" for="cb_usdc"></label></div></div></li>
                                </div>
                                <div class="col-6 py-2">
                                    <li class="d-flex justify-content-between align-items-center px-4 py-3" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">LTC</h6><div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cb_ltc"><label class="custom-control-label" for="cb_ltc"></label></div></div></li>
                                </div>
                                <div class="col-6 py-2">
                                    <li class="d-flex justify-content-between align-items-center px-4 py-3" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">BNB</h6><div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cb_bnb"><label class="custom-control-label" for="cb_bnb"></label></div></div></li>
                                </div>
                                <div class="col-6 py-2">
                                    <li class="d-flex justify-content-between align-items-center px-4 py-3" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">TRX</h6><div><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cb_trx"><label class="custom-control-label" for="cb_trx"></label></div></div></li>
                                </div>

                            </div>

                        </div>
                    </div>
                    <div class="m-0" id="method_cp">
                        <div class="form-group">
                            <label>Адрес для уведомлений</label>
                            <input type="text" class="form-control" value="{{ env('APP_URL') }}/pay/callback/cp">
                        </div>
                        <div class="form-group mt-4">
                            <label for="public_id">Логин кассы (auth_login)</label>
                            <input type="text" class="form-control" id="public_id" placeholder="Вставьте логин кассы CrystalPay">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key">Секретный ключ (auth_secret)</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте секретный ключ">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key_two">Секретный Salt ключ</label>
                            <input type="text" class="form-control" id="secret_key_two" placeholder="Вставьте секретный Salt ключ">
                        </div>
                    </div>
                    <div class="m-0" id="method_ts">
                        <div class="form-group">
                            <label for="secret_key">Токен бота</label>
                            <input type="text" class="form-control" id="secret_key" placeholder="Вставьте токен бота для Telegram Stars">
                        </div>
                        <div class="form-group mt-4">
                            <label for="secret_key_two">Курс звезды (₽)</label>
                            <input type="text" class="form-control" id="secret_key_two" placeholder="Например: 1.55 (цена одной звезды в рублях)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btn-save"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAsset" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0"><i class="far fa-credit-card mr-1" style="font-size: 16px"></i> Платежная система: <span id="title"></span></h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body pb-4 pt-3">
                    <div class="row">
                        <div class="col">
                            <label for="min">Минимальная сумма</label>
                            <input type="number" class="form-control" id="min" placeholder="Введите сумму">
                        </div>
                        <div class="col">
                            <label for="max">Максимальная сумма</label>
                            <input type="number" class="form-control" id="max" placeholder="Введите сумму">
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
