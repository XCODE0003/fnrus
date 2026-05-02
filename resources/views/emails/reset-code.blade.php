@extends('emails.layouts.main')
@section('content')
    <div style="padding:25px;background-color:#fff;border-radius: 15px">
        <h2 style="margin: 0;padding-bottom: 10px">Код для восстановления пароля</h2>
        <p style="background: #f6f6f6;padding:20px;border-radius: 15px;text-align: center"><b style="font-size: 24px;letter-spacing: 2px;">{{ $code }}</b></p>
        <p>Если заявку на восстановление подали не Вы, тогда просто проигнорируйте данное сообщение.</p>
    </div>
@endsection
