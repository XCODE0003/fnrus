@extends('emails.layouts.main')
@section('content')
    <div style="padding:25px;background-color:#fff;border-radius: 15px">
        <h2 style="margin: 0;padding-bottom: 10px;color:#222">Сброс пароля</h2>
        <p style="color:#444">
            Администратор сайта инициировал сброс вашего пароля.
            Чтобы установить новый пароль, перейдите по ссылке ниже:
        </p>
        <p style="text-align:center;margin:24px 0">
            <a href="{{ $reset_url }}" style="display:inline-block;background:#dcac01;color:#000;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">
                Установить новый пароль
            </a>
        </p>
        <p style="color:#666;font-size:13px">
            Ссылка действительна {{ $valid_hours }} часов. После сброса пароля вы сможете войти в систему как обычно.
        </p>
        <p style="color:#888;font-size:12px;margin-top:32px">
            Если вы не ожидали этого письма — свяжитесь со службой поддержки.
        </p>
    </div>
@endsection
