<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="format-detection" content="telephone=no" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="MobileOptimized" content="176" />
    <meta name="HandheldFriendly" content="True" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Заказ #{{ $hash }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://telegram.org/js/telegram-web-app.js?1"></script>
    <style>
        body {
            --bg-color: var(--tg-theme-bg-color, #fff);
            font-family: sans-serif;
            background-color: var(--bg-color);
            color: var(--tg-theme-text-color, #222);
            font-size: 15px;
            margin: 0;
            padding: 0;
            color-scheme: var(--tg-color-scheme);
        }

        a {
            color: var(--tg-theme-link-color, #2678b6);
        }
        .btn {
            font-size: 15px;
            padding: 10px 24px;
        }

        /*.btn:hover {*/
        /*    background: ;*/
        /*}*/
        .btn-primary {
            background-color: var(--tg-theme-button-color, #50a8eb);
            color: var(--tg-theme-button-text-color, #fff);
            border: none;
        }

        button {
            display: block;
            /*width: 100%;*/
            font-size: 15px;
            margin: 15px 0;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            background-color: var(--tg-theme-button-color, #50a8eb);
            color: var(--tg-theme-button-text-color, #ffffff);
            cursor: pointer;
        }
        .main-container {
            padding: 15px;
        }
        .list-header {
            text-transform: uppercase;
            font-size: .92em;
            color: var(--tg-theme-hint-color, #ccc);
            margin: 0 0 10px;
        }
        a.list-group-item,
        button.list-group-item {
            color: var(--tg-theme-text-color, #222);
        }
        .main-container p {
            margin: 0 0 10px;
        }
        .main-container pre,
        .main-container > .btn {
            margin: 0 0 7px;
        }
        .main-container pre + .hint,
        .main-container > .btn + .hint {
            text-align: center;
            margin: 0 0 15px;
        }

        button[disabled] {
            opacity: 0.6;
            cursor: auto;
            pointer-events: none;
        }

        button.close_btn {
            /*position: fixed;*/
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 0;
            margin: 0;
            padding: 16px 20px;
            text-transform: uppercase;
        }

        input[type="text"],
        .input[contenteditable] {
            display: block;
            box-sizing: border-box;
            font-size: 15px;
            width: 100%;
            padding: 12px 20px;
            margin: 15px 0;
            border: 1px solid var(--tg-theme-link-color, #000);
            background-color: var(--tg-theme-bg-color, #ffffff);
            border-radius: 4px;
            color: var(--tg-theme-text-color, #222222);
            text-align: start;
        }
        input[type="text"]::-webkit-input-placeholder {
            color: var(--tg-theme-hint-color, #ccc);
        }
        input[type="text"]::-moz-placeholder {
            color: var(--tg-theme-hint-color, #ccc);
        }
        input[type="text"]:-ms-input-placeholder {
            color: var(--tg-theme-hint-color, #ccc);
        }
        .input[data-placeholder] {
            position: relative;
        }
        .input[data-placeholder]:empty:before {
            position: absolute;
            left: 0;
            right: 0;
            content: attr(data-placeholder);
            color: var(--tg-theme-hint-color, #ccc);
            padding: 0 20px;
            font-weight: normal;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
            pointer-events: none;
            z-index: -1;
        }
        section {
            padding: 25px;
            max-width: 720px;
            margin: 0 auto;
        }
        section#top_sect {
            background-color: var(--tg-theme-bg-color, #ffffff);
        }
        section#top_sect.second {
            background-color: var(--tg-theme-secondary-bg-color, #efefef);
        }
        section .sect_row {
            margin: 10px 0;
        }
        section + section {

            padding: 0 15px 25px;
        }
        p {
            margin: 40px 0 15px;
        }
        ul {
            text-align: left;
        }
        li {
            color: var(--tg-theme-hint-color, #a8a8a8);
        }
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 7px;
        }
        pre {
            background: rgba(0, 0, 0, .07);
            color: var(--tg-theme-text-color, #222);
            font-size: 12px;
            border: none;
            border-radius: 4px;
            padding: 8px;
            margin: 7px 0;
            word-break: break-word;
            white-space: pre-wrap;
            text-align: left;
        }
        .dark pre {
            background: rgba(255, 255, 255, .15);
        }
        .chat_img {
            width: 30px;
            border-radius: 15px;
            margin-right: 10px;
        }
        .columns {
            display: flex;
        }
        .columns>* {
            flex-grow: 1;
        }
        .hint {
            font-size: .8em;
            color: var(--tg-theme-hint-color, #a8a8a8);
        }
        .ok {
            color: green;
        }
        .err {
            color: red;
        }
        .status_need {
            display: none;
        }
        #fixed_wrap {
            position: fixed;
            left: 0;
            right: 0;
            top: 0;
            transform: translateY(100vh);
        }
        .viewport-container {
            position: fixed;
            left: 0;
            right: 0;
            top: 0;
            height: var(--tg-viewport-stable-height, 100vh);
            transition: height .2s ease;
        }
        .viewport-container .main-container {
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .viewport-container .main-container button {
            width: auto;
        }
        .viewport-border,
        .viewport-stable_border {
            position: fixed;
            left: 0;
            right: 0;
            top: 0;
            height: var(--tg-viewport-height, 100vh);
            pointer-events: none;
        }
        .viewport-stable_border {
            height: var(--tg-viewport-stable-height, 100vh);
        }
        .viewport-border:before,
        .viewport-stable_border:before {
            content: attr(text);
            display: inline-block;
            position: absolute;
            background: gray;
            right: 0;
            top: 0;
            font-size: 7px;
            padding: 2px 4px;
            vertical-align: top;
        }
        .viewport-stable_border:before {
            background: green;
            left: 0;
            right: auto;
        }
        .viewport-border:after,
        .viewport-stable_border:after {
            content: '';
            display: block;
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            border: 2px dashed gray;
        }
        .viewport-stable_border:after {
            border-color: green;
        }

        small {
            font-size: 12px;
        }

        .method-payment {
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #E2E2E2;

        }

        .method-payment:hover {
            background: #FFF9F4;
            border: 1px solid #FB8C2C;
        }

    </style>
    <script>
        function setThemeClass() {
            document.documentElement.className = Telegram.WebApp.colorScheme;
        }
        Telegram.WebApp.onEvent('themeChanged', setThemeClass);
        setThemeClass();

    </script>
</head>

<body>

<section>
    <div class="my-5 text-center" style="width: 200px;margin:0 auto">
        @if($status == 2)
            <div class="d-block" style="margin-bottom: 13px"><svg style="width: 50px;fill:#4ABD5C" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.3.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M243.8 339.8C232.9 350.7 215.1 350.7 204.2 339.8L140.2 275.8C129.3 264.9 129.3 247.1 140.2 236.2C151.1 225.3 168.9 225.3 179.8 236.2L224 280.4L332.2 172.2C343.1 161.3 360.9 161.3 371.8 172.2C382.7 183.1 382.7 200.9 371.8 211.8L243.8 339.8zM512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"/></svg></div>
            <div class="d-block mb-0" style="font-weight: 500;font-size: 18px">Заказ оплачен</div>
        @endif
        @if($status == 3)
            <div class="d-block" style="margin-bottom: 13px"><svg style="width: 50px;fill:#dc3545" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.3.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M175 175C184.4 165.7 199.6 165.7 208.1 175L255.1 222.1L303 175C312.4 165.7 327.6 165.7 336.1 175C346.3 184.4 346.3 199.6 336.1 208.1L289.9 255.1L336.1 303C346.3 312.4 346.3 327.6 336.1 336.1C327.6 346.3 312.4 346.3 303 336.1L255.1 289.9L208.1 336.1C199.6 346.3 184.4 346.3 175 336.1C165.7 327.6 165.7 312.4 175 303L222.1 255.1L175 208.1C165.7 199.6 165.7 184.4 175 175V175zM512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"/></svg></div>
            <div class="d-block mb-4" style="font-weight: 500;font-size: 18px">Заказ отменен</div>
            <a href="javascript:Telegram.WebApp.openTelegramLink('https://t.me/{{ $shop_username }}');" class="btn btn-primary">Вернуться в бота</a>
        @endif
        @if($status == 4)
            <div class="d-block" style="margin-bottom: 13px"><svg style="width: 50px;fill:#ffc107" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.3.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M128 0c13.3 0 24 10.7 24 24V64H296V24c0-13.3 10.7-24 24-24s24 10.7 24 24V64h40c35.3 0 64 28.7 64 64v16 48V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V192 144 128C0 92.7 28.7 64 64 64h40V24c0-13.3 10.7-24 24-24zM400 192H48V448c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V192zm-95 89l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg></div>
            <div class="d-block mb-4" style="font-weight: 500;font-size: 18px">Истек срок оплаты</div>
            <a href="javascript:Telegram.WebApp.openTelegramLink('https://t.me/{{ $shop_username }}');" class="btn btn-primary">Вернуться в бота</a>
        @endif
    </div>
</section>
<script src="https://code.jquery.com/jquery-3.6.4.js" integrity="sha256-a9jBBRygX1Bh5lt8GZjXDzyOB+bWve9EiO7tROUtj/E=" crossorigin="anonymous"></script>
<script type="application/javascript">

    const DemoApp = {
        initData: Telegram.WebApp.initData || '',
        initDataUnsafe: Telegram.WebApp.initDataUnsafe || {},

        init(options) {
            document.body.style.visibility = '';
            Telegram.WebApp.ready();
        },
        expand() {
            Telegram.WebApp.expand();
        },
        close() {
            Telegram.WebApp.close();
        },

    }

    @if($status == 2)
    function getOrder() {

        $.ajax({
            type: "GET",
            url: '/invoice/{{ $hash }}/status',
            dataType: 'json',
            contentType: 'application/json',
            async: true,
            success: function(data) {
                if(data.ok == true){
                    Telegram.WebApp.openTelegramLink('https://t.me/{{ $shop_username }}');
                }
            }

        });

    }
     @endif

    DemoApp.init();

</script>

</body>
</html>
