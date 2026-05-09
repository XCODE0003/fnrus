<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
</head>
<body style="background-color: #111720;padding: 20px;font-family: sans-serif;font-size: 16px;color:#eaeaea">
<div style="max-width: 550px; margin:0 auto;background:#1a2230;padding:24px;border-radius:8px">
    <a target="_blank" href="{{ env('APP_URL') }}" style="width:110px;margin:0 auto;display:block; height: 40px; margin-bottom: 24px">
        <img height="40" src="https://telegra.ph/file/304e3f7bac584996c5b9b.png" alt="Logo">
    </a>

    {{-- $body_html is server-side sanitized before reaching this template --}}
    {!! $body_html !!}

    <hr style="border:0;border-top:1px solid #2c3a52;margin:32px 0 16px">
    <div style="font-size:12px;color:#6a7894;text-align:center;line-height:1.5">
        Это письмо получено как часть нашей рассылки.<br>
        @if (!empty($unsubscribe_url))
            <a href="{{ $unsubscribe_url }}" style="color:#9aafd1">Отписаться от рассылок</a>
        @endif
    </div>
</div>
</body>
</html>
