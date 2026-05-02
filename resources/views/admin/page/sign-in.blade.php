@extends('admin.page.layouts.main')
@section('content')
<div id="auth" class="card o-hidden border-0 mt-2 mb-4" style="border-radius: 1.3rem;">
    <div class="text-center mt-5 mb-2 font-weight-bold" style="font-size: 1.6rem">
        <span class="text-primary">FN</span>
        <span class="text-white">RUS</span>
    </div>
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="p-5">
                                <div class="form-group m-0">
                                    <input type="text" id="username" class="form-control py-3 px-4" placeholder="Введите логин">
                                </div>
                                <div class="form-group">
                                    <input type="password" id="password" class="form-control py-3 px-4" placeholder="Введите пароль">
                                </div>
                                <div class="form-group">
                                   <button class="btn btn-primary w-100 py-3 px-4 font-weight-bold text-uppercase" id="btn_login" onclick="signIn();return false;">Войти</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endsection
