@extends('emails.layouts.main')
@section('content')
    <div style="padding:25px;background-color:#fff;border-radius: 15px">
        <h2 style="margin: 0;padding-bottom: 10px">Изменился статус софта</h2>
        <p>Софт: <b>{{ $title }}</b></p>
        <p>Статус: <b>{{ $status }}</b></p>
    </div>
@endsection
