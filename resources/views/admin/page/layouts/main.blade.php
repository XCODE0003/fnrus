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

    <title>Войти в систему</title>

    <!-- Custom fonts for this template-->
    <link href="/public/assets/fontawesome-pro/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="/public/assets/css/dark_b.min.css?v=1.0.108" rel="stylesheet">
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

        #block_upload_image {
            border: 1px solid #303030;
            border-radius: 15px;
            margin-bottom: 15px;
            overflow: hidden;
        }

        #block_upload_image img {
            width: 100%;
        }
    </style>
</head>

<body class="bg-gradient-primary">

<div class="container">

    <!-- Outer Row -->
    <div class="row justify-content-center">

        <div class="col-xl-5 col-lg-12 col-md-5 col-sm-12">

            @yield('content')

        </div>

    </div>

</div>

<div id="system_msg"></div>

<script src="/public/assets/jquery/jquery.min.js"></script>
<script src="/public/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/public/assets/jquery-easing/jquery.easing.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.33/moment-timezone-with-data.min.js"></script>
<script>var date = parseInt((new Date).getTime()/1e3);let s=document.createElement("script");s.src="/public/assets/js/main.js?t="+date,document.head.append(s);</script>

</html>
