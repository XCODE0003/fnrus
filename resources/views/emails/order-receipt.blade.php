@extends('emails.layouts.main')
@section('content')
    <div style="padding:25px;background-color:#fff;border-radius: 15px">
        <h2 style="margin: 0;padding-bottom: 10px">Новая покупка</h2>
        <p>Товар: <b>{{ $product_title }}</b></p>
        <p>Сумма: <b>{{ $product_sum }}</b></p>
        <a target="_blank" href="{{ $order_link }}" style="background: #8456FF;text-decoration:none;display:block;text-align:center;border:0;color:#fff;font-size:16px;font-weight:bold;margin:0;padding:15px 25px;cursor:pointer;border-radius: 15px">Получить товар</a>
        <p>Если покупку сделали не Вы, советуем изменить данные для входа и обратиться в службу поддержки.</p>
    </div>
@endsection
