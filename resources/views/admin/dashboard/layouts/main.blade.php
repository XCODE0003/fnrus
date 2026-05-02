<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="/assets/img/favicon.png?6" rel="shortcut icon" type="image/x-icon" />
    <link href="/assets/img/favicon.png?6" rel="icon" type="image/x-icon" />
    <title>{{ $title }}</title>
    <!-- <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet"> -->
    <link href="/assets/fontawesome-pro/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="/assets/css/bootstrap-select.min.css?v=1.0.179" rel="stylesheet">
    <link href="/assets/css/dark_b.min.css?v=1.0.203" rel="stylesheet">
    <link href="/assets/css/quill.dark_b.css?v=1.0.181" rel="stylesheet">
    <link href="/assets/css/material-components-web.min.css?v=1.0.179" rel="stylesheet">
    <link href="/assets/css/dataTables.material.min.css?4" rel="stylesheet">
    <link href="/assets/css/bootstrap-datetimepicker.min.css?2"  rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" rel="stylesheet" />
    <link href="/assets/css/dropzone.css?v=1.0.1" type="text/css" rel="stylesheet"  />
    <script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.2/dist/min/dropzone.min.js"></script>
    <style type="text/css">

        .mdc-data-table__row {
            border-top-color: #222933!important;
        }

        .dataTables_length {
            display: none;
        }
        
        .dataTables_filter {
            display: none;
        }

        table .selected {
            background-color: #151515;
            border-left: 5px solid #dcac01;
        }

        table .selected:hover {
            background-color: #121212!important;
        }

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

        #block_upload_image {
            border: 1px solid #303030;
            border-radius: 15px;
            overflow: hidden;
            max-height: 50px;
        }

        #block_upload_image img {
            max-height: 50px;
        }

        #btn_title {
            white-space: nowrap;
            overflow: hidden;
            display: block;
            text-overflow: ellipsis;
        }

        .nav_bg {
            background-color: #1d2636!important;
        }

        #preloader {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        
        .sidebar.toggled .sidebar-brand-full { display: none !important; }
        .sidebar.toggled .sidebar-brand-mini { display: inline !important; }
        #page-top {
            display: none;
        }

    </style>
</head>
<body>
<div id="page-top">
<div id="wrapper">
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar" style="z-index: 13">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
            <div class="sidebar-brand-icon" style="font-size: 23px;">
                <span class="sidebar-brand-full"><span class="text-primary">FN</span><span class="text-white">RUS</span></span>
                <span class="sidebar-brand-mini" style="display:none;"><span class="text-primary">F</span><span class="text-white">R</span></span>
            </div>
        </a>

        <div id="menu_sidebar"></div>

        <div class="text-center mt-3">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>
    </ul>
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-transparent topbar mb-2 static-top">
                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link rounded-circle px-2">
                    <i class="fa fa-bars" style="font-size: 19px;"></i>
                </button>

                    <ul class="navbar-nav ml-auto"><div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-800 font-weight-bold normal" id="navbar_username">...</span>
                            <img class="img-profile rounded-circle" src="/assets/img/noavatar.png?5">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Выйти</a>
                        </div>
                    </li>
                </ul>
            </nav> @yield('content')
        </div>
    </div>
</div>

<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0"><i class="far fa-sign-out mr-1" style="font-size: 16px"></i> Точно выйти?</h5>
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

<div class="modal fade" id="allSettings" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0">
                    <i class="far fa-cog mr-1" style="font-size: 16px"></i> Общие настройки
                </h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="notifySettings" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0">
                    <i class="far fa-bell mr-1" style="font-size: 16px"></i> Уведомления
                </h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mt-0">
                    <label for="notify_target_id">Telegram ID аккаунта или чата</label>
                    <input type="text" class="form-control" id="notify_target_id" placeholder="Введите целое число">
                </div>
                <div class="form-group mx-2 mt-4">
                    <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                        <input type="checkbox" class="custom-control-input d-block" id="tg_notify_buys">
                        <label class="custom-control-label" for="tg_notify_buys" style="margin-left: 20px;">Уведомлять о новых покупках</label>
                    </div>
                    <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                        <input type="checkbox" class="custom-control-input d-block" id="tg_notify_balance">
                        <label class="custom-control-label" for="tg_notify_balance" style="margin-left: 20px;">Уведомлять о новых пополнениях баланса</label>
                    </div>
                    <div class="custom-control custom-switch d-block" style="margin-left: 35px;">
                        <input type="checkbox" class="custom-control-input d-block" id="tg_notify_users">
                        <label class="custom-control-label" for="tg_notify_users" style="margin-left: 20px;">Уведомлять о новых пользователях</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="saveNotify();"><i class="far fa-save mr-1"></i> Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="refModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0"><i class="far fa-user-plus mr-1" style="font-size: 16px"></i> Реферальная программа</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mt-0">
                    <label for="ref_percent">Процент с продаж</label>
                    <input type="text" class="form-control" id="ref_percent" placeholder="Укажите процент с продаж по умолчанию">
                </div>
                <div class="form-group mt-4">
                    <label for="min_sum_withdrawal_card">Минимальная сумма для вывода на карту</label>
                    <input type="text" class="form-control" id="min_sum_withdrawal_card" placeholder="Укажите целое число">
                </div>
                <div class="form-group mt-4">
                    <label for="min_sum_withdrawal_balance">Минимальная сумма для вывода на баланс</label>
                    <input type="text" class="form-control" id="min_sum_withdrawal_balance" placeholder="Укажите целое число">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="saveReferral();"><i class="far fa-save mr-1"></i> Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="secretToken" style="display:none!important" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0"><i class="far fa-key mr-1" style="font-size: 16px"></i> Cекретный токен бота</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mt-0">
                    <label for="token">Актуальный токен</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="result-token" disabled>
                        <div class="input-group-append" id="btn-get-token">
                            <button class="btn btn-outline-primary" type="button" onclick="getShopToken();"><i class="far fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="form-group mt-4">
                    <label for="token">Новый токен</label>
                    <input type="text" class="form-control" name="token" id="token" placeholder="Секретный токен">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="saveToken();"><i class="far fa-save mr-1"></i> Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBalance" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title m-0"><i class="far fa-sack-dollar mr-1" style="font-size: 16px"></i> Пополнение баланса</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mt-0">
                    <label for="min_sum_topup">Минимальная сумма пополнения</label>
                    <input type="text" class="form-control" id="min_sum_topup" placeholder="Введите целое число">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="saveTopup();"><i class="far fa-save mr-1"></i> Сохранить</button>
            </div>
        </div>
    </div>
</div>
</div>
<div id="system_msg"></div>

<script>
    Dropzone.autoDiscover = false;

    window.onload = function () {
        var api_url = "/api"; // Replace with your API endpoint
        var dropzoneOptions = {
            dictDefaultMessage: '<i class="far fa-upload mr-1" style="font-size:15px"></i> Выберите изображения',
            url:  api_url + "/attachments/image/upload",
            addRemoveLinks: true,
            maxFilesize: 5,
            acceptedFiles: ".png, .jpg, .gif",
            headers: {'Authorization': 'Bearer ' + getCookie('session_token')},
            init: function() {
                this.on('success', function(file, resp) {
                    file.previewElement.setAttribute('data-id', resp.result.id);
                });
                this.on('error', function(file, errorMessage) {
                    alert('Error uploading file: ' + errorMessage);
                });
            }
        };

        createDropzoneIfVisible('#addProduct #uploader', dropzoneOptions);
        createDropzoneIfVisible('#editProduct #uploader', dropzoneOptions);
        console.log("Loaded");

    };
</script>
<script src="/assets/jquery/jquery.min.js"></script>
<script>
// Global 2FA gate: when any /api/admin/* call returns 423 with
// action=2fa_required or action=setup_required, redirect the SPA to the
// 2FA setup page so the user can either enroll or verify their TOTP.
// Also suppresses DataTables alert popups (raised when 423 hits a
// DataTable-driven endpoint) since we handle the error via redirect.
$(document).ajaxComplete(function (event, xhr) {
    if (xhr.status !== 423) return;
    let action = '';
    try { action = (xhr.responseJSON || JSON.parse(xhr.responseText || '{}')).action || ''; } catch (e) {}
    if (action !== '2fa_required' && action !== 'setup_required') return;
    var prefix = @json(trim(config('admin.prefix', 'admin'), '/'));
    if (window.location.pathname === '/' + prefix + '/2fa') return;
    window.location.href = '/' + prefix + '/2fa';
});
$(function () {
    if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
        jQuery.fn.dataTable.ext.errMode = 'none';
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/jquery-easing/jquery.easing.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.material.min.js"></script>
<script src="/assets/datatables/dataTables.bootstrap4.min.js"></script>
<script src="/assets/js/simpleUpload.min.js?v=2"></script>
<script src="/assets/js/sidebar.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="/assets/js/image-resize.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment-with-locales.min.js"></script>
<script src="/assets/js/bootstrap-datetimepicker.min.js"></script>
<script>var date = parseInt((new Date).getTime()/1e3);let s=document.createElement("script");s.src="/assets/js/main.js?t="+date,document.head.append(s);</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/i18n/defaults-ru_RU.js"></script>
</body>
</html>

