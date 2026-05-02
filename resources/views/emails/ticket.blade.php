@extends('emails.layouts.main')
@section('content')
    <div style="padding:25px;background-color:#fff;border-radius: 15px">
    <h2 style="margin: 0;padding-bottom: 10px">Новый ответ от Службы поддержки</h2>
        <p>Тема: <b>{{ $subject }}</b></p>
    <p style="background: #f6f6f6;padding:20px;border-radius: 15px">{{ $text }}</p>
        <p>Ответить на сообщение можно через сайт <a target="_blank" href="{{ env('APP_URL') }}">Fnrus</a> или <a target="_blank" href="{{ env('BOT_TELEGRAM_USERNAME') }}">Телеграм-бота</a>. </p>
    </div>
@endsection
