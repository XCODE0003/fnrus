@extends('admin.page.layouts.main')
@section('content')
<div class="card o-hidden border-0 mt-5 mb-4" style="border-radius: 1.3rem;">
                <div class="card-header">
                    <h5 class="m-0 p-0 text-center">Регистрация</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Nested Row within Card Body -->
                    <div class="row">

                        <div class="col-lg-12">
                            <div class="p-5">
                                <form method="post">
                                    <div class="form-group">
                                        <input type="text" id="username" class="form-control py-3 px-4" placeholder="Введите логин">
                                    </div>
                                    <div class="form-group">
                                        <input type="password" id="password" class="form-control py-3 px-4" placeholder="Введите пароль">
                                    </div>
                                    <div class="form-group mx-2 mb-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" id="terms" name="terms" value="1" class="custom-control-input" style="cursor: pointer;">
                                            <label class="custom-control-label" for="terms" style="cursor: pointer;font-size: 14px">Я принимаю условия <a target="_blank" href="/docs/policy" style="color:#228dc5">пользовательского соглашения</a></label>
                                        </div>
                                    </div>
                                    <button onclick="signUp();return false;" class="btn btn-primary w-100 py-3 px-4">
                                        Зарегистрироваться
                                    </button>

                                    <div class="text-center mt-3">
                                        <script style="font-size: 99px;position: absolute;" async src="https://telegram.org/js/telegram-widget.js?2" data-telegram-login="TbizAuthBot" data-request-access="write" data-size="large" data-radius="20" data-auth-url="check.auth.php"></script>
                                    </div>
                                </form>
                                <hr>
                                <div class="text-center">
                                    <a href="/auth/sign-in">Уже есть аккаунт</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endsection
