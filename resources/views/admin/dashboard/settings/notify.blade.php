@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-12 col-md-9">
                <div class="card shadow mb-4">
                    <div class="card-header  py-3">
                        <h5 class="m-0 text-left"><i class="far fa-fw fa-bell mr-1"></i> Уведомления магазина</h5>
                    </div>
                    <div class="card-body p-5">
                        <form>
                            <div class="form-group text-left">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="notify_new_user" value="1" class="custom-control-input" id="customCheck1" checked="">
                                    <label class="custom-control-label" for="customCheck1" style="cursor: pointer;">Получать уведомления о новых участниках</label>
                                </div>
                            </div>
                            <div class="form-group text-left">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="notify_new_pay" value="1" class="custom-control-input" id="customCheck2" checked="">
                                    <label class="custom-control-label" for="customCheck2" style="cursor: pointer;">Получать уведомления о новых платежах</label>
                                </div>
                            </div>
                            <div class="alert alert-primary" role="alert">
                                Для получения уведомлений<br>запустите бота <a target="_blank" href="https://t.me/notify_telebiz_bot">@notify_telebiz_bot</a>
                            </div>
                            <button onclick="toggle_notify (this.form);return false;" class="btn col-sm-12 mb-1 btn-primary">Сохранить изменения</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
