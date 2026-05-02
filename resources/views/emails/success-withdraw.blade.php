@extends('emails.layouts.main')
@section('content')
    <div style="padding:25px;background-color:#fff;border-radius: 15px">
        <h2 style="margin: 0;padding-bottom: 10px">✅ Успешный вывод средств</h2>
        <p>Средства успешно отправлены на указанные реквизиты.</p>
        <p>Реквизиты: <b>{{ $req }}</b></p>
        <p>Сумма: <b>{{ $sum }}</b></p>
    </div>
@endsection
