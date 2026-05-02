<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="/public/assets/img/favicon.png?6" rel="shortcut icon" type="image/x-icon" />
    <link href="/public/assets/img/favicon.png?6" rel="icon" type="image/x-icon" />
    <title>{{ $title }}</title>
    <!-- <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet"> -->
    <link href="/public/assets/fontawesome-pro/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="/public/assets/css/dark_b.min.css?v=1.0.179" rel="stylesheet">
    <link href="/public/assets/css/quill.dark_b.css?v=1.0.179" rel="stylesheet">
    <link href="/public/assets/css/material-components-web.min.css?v=1.0.179" rel="stylesheet">
    <link href="/public/assets/css/dataTables.material.min.css?4" rel="stylesheet">
    <link href="/public/assets/css/bootstrap-datetimepicker.min.css?2"  rel="stylesheet"/>
    <style type="text/css">

        #system_msg {
            display: block;
            position: fixed;
            background-color: #232931;
            bottom: 108px;
            left: 8px;
            color: #f1f1f1;
            min-width: 350px;
            padding: 17px 30px;
            box-sizing: border-box;
            box-shadow: 0 2px 5px 0 rgb(0 0 0 / 26%);
            border-radius: 24px;
            margin: 12px;
            font-size: 16px;
            cursor: default;
            -webkit-transition: -webkit-transform .3s,opacity .3s;
            transition: transform .3s,opacity .3s;
            -webkit-transform: translateY(100px);
            transform: translateY(100px);
            z-index: 1100;
            /* opacity: 0; */
            display: none;
        }

        .gradient-text {
            font-weight: bold;
            display: inline-block;
            background-image: linear-gradient(
                35deg,#17ead9 0%,#6078ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }


        .success {
            background: linear-gradient(267.05deg,#098b45,#1f683d 99.28%)!important;
            color: #fff!important;
        }

        .error {
            background: linear-gradient(267.05deg,#c33737,#9d2b2b 99.28%)!important;
            color: #fff!important;
        }
    </style>
</head>
<body id="page-top">
<!-- Page Wrapper -->
<div id="wrapper">

    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-transparent topbar mb-5 static-top">
                <a class="sidebar-brand ml-3" href="/dashboard">
                    <div class="sidebar-brand-icon">
                        <img src="/public/assets/img/telelogo.png?1" id="logo" height="25">
                    </div>
                </a>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" target="_blank" href="https://t.me/TbizBot" title="Техническая поддержка">
                            <i class="far fa-headset text-gray-800" style="font-size: 19px;"></i>
                        </a>
                    </li>
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item">
                        <a class="nav-link" href="/pay" title="Тарифы">
                            <i class="far fa-credit-card text-gray-800" style="font-size: 19px;"></i>
                        </a>
                    </li>
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item">
                        <a class="nav-link" href="/set/theme" title="Изменить цветовой тон">
                            <i class="far fa-adjust text-gray-800" style="font-size: 19px;"></i>
                        </a>
                    </li>
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-800 font-weight-bold normal" id="u_username"></span>
                            <img class="img-profile rounded-circle" src="/public/assets/img/noavatar.png?5">
                        </a>
                        <!-- Dropdown - User Information -->
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                            <a class="dropdown-item" id="modal_profile" href="#" data-toggle="modal" data-target="#setProfile" class="mb-4">
                                <i class="fas fa-fw fa-cog text-gray-400" style="margin-right: 6px"></i> Настройки аккаунта </a>
                            <a class="dropdown-item" href="/subscriptions" class="mb-4">
                                <i class="fas fa-fw fa-list-alt text-gray-400" style="margin-right: 6px"></i> Мои подписки </a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Выход </a>
                        </div>
                    </li>
                </ul>
            </nav> @yield('content')
        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->
</div>

<div id="system_msg"></div>

<div class="modal fade" id="settingsAccount" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0">Настройки аккаунта</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <nav>
                    <div class="nav nav-pills nav-fill" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Изменить логин</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">Изменить пароль</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-utc" role="tab" aria-controls="nav-utc" aria-selected="false">Часовой пояс</a>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                        <div class="form-group">
                            <input type="text" name="token" class="form-control" id="change_login" style="text-align: center;margin-top: 15px" aria-describedby="change_login_help" placeholder="Введите новый логин" value="">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                        <div class="form-group">
                            <input type="password" name="token" class="form-control" id="change_pass" style="text-align: center;margin-top: 15px" aria-describedby="change_pass_help" placeholder="Введите новый пароль" value="">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="nav-utc" role="tabpanel" aria-labelledby="nav-utc-tab">
                        <div class="form-group"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="button">Сохранить</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0"><i class="far fa-sign-out mr-1"></i> Точно выйти?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Выберите «Выйти» ниже, если вы готовы завершить текущий сеанс.</div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="logout();"><i class="far fa-sign-out mr-1"></i> Выйти</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="setProfile" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0"><i class="far fa-user mr-1"></i> Настройки аккаунта</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form id="form_set_profile" class="modal-body">
                <div class="form-group">
                    <label for="input">Старый пароль</label>
                    <input type="password" class="form-control" id="old_password" placeholder="Введите старый пароль">
                </div>
                <div class="form-group">
                    <label for="input">Новый пароль</label>
                    <input type="password" class="form-control" id="new_password" placeholder="Введите новый пароль">
                </div>
                <div class="form-group" style="display: none" id="change_password">
                    <label for="input">Код подтверждения</label>
                    <input type="text" class="form-control" id="code_confirm" placeholder="Введите код подтверждения">
                </div>
                <div class="form-group mt-3">
                    <button class="btn btn-primary w-100" style="display: none;" id="btn_confirm" onclick="save_password();return false;">Подтверждить изменение</button>
                    <button class="btn btn-primary w-100" id="btn_change" onclick="save_password();return false;"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
                <hr>
                <div class="row py-2 px-3 mt-2 mb-1">
                    <div class="col-10">
                        <label for="confirm_status" style="cursor: pointer;">Двухфакторная аутентификация</label>
                    </div>
                    <div class="col-2">
                        <button id="confirm_status" onclick="save_confirm();return false;" type="button" class="btn btn-success btn-sm btn-toggle" data-toggle="button" aria-pressed="false" autocomplete="off">
                            <div class="handle"></div>
                        </button>
                    </div>
                </div>
                <div class="form-group mx-5 text-center" style="display: none;" id="code_confirm_auth">
                    <hr>
                    <input type="text" class="form-control text-center" id="code" placeholder="Код подтверждения">
                    <button class="w-100 btn btn-primary mt-2" onclick="save_confirm_code();return false;">Подтвердить</button>
                </div>
                <div class="alert alert-primary" role="alert"> Код подтверждения будет отправляться на ваш аккаунт Telegram в бота уведомлений <a target="_blank" href="https://t.me/notify_telebiz_bot">@notify_telebiz_bot</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/public/assets/jquery/jquery.min.js"></script>
<script src="/public/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/public/assets/jquery-easing/jquery.easing.min.js"></script>
<script src="/public/assets/datatables/jquery.dataTables.min.js"></script>
<script src="/public/assets/datatables/dataTables.bootstrap4.min.js"></script>
<script src="/public/assets/js/simpleUpload.min.js?v=2"></script>
<script src="/public/assets/js/sidebar.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>var date = parseInt((new Date).getTime()/1e3);let s=document.createElement("script");s.src="/public/assets/js/main.js?t="+date,document.head.append(s);</script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();
    });
</script>
</body>
</html>
