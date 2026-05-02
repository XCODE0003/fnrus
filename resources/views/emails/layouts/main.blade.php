<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="background-color: #111720;padding: 20px;font-family: sans-serif;font-size: 16px">
<div style="max-width: 550px; margin:0 auto">
    <a target="_blank" href="{{ env('APP_URL') }}" style="width:110px;margin:0 auto;display:block; height: 40px; margin-bottom: 24px">
        <img height="40" src="https://telegra.ph/file/304e3f7bac584996c5b9b.png">
    </a>
    @yield('content')
    <div style="padding:20px;text-align:center"><a style="color: #f7f7f7;" target="_blank" href="https://fnrus.com/#faq">Нужна помощь?</a></div>
</div>
</body>
</html>
