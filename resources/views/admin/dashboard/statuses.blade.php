@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-toggle-on mr-1"></i> Статусы софта
                </h5>
                <a data-toggle="modal" data-target="#createCheat" href="#" class="btn btn-sm btn-color shadow px-3">
                    <i class="far fa-plus"></i>
                    <span class="text d-none d-lg-inline ml-1">Добавить софт</span>
                </a>
            </div>
            <div class="card-body p-4">
                <div class="row" id="cheats"></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="createCheat" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-file mr-1" style="font-size: 16px"></i> Новый софт</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="title">Название</label>
                        <input id="title" class="form-control" placeholder="Введите название софта">
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="game_id">Игра</label>
                        <select class="form-control" id="game_id">
                            <option value="0">Не выбрана</option>
                        </select>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="status">Статус</label>
                        <select class="form-control" id="status">
                            <option>Не выбрана</option>
                            <option value="1">Рекомендуем к использованию</option>
                            <option value="2">Не рекомендуем к использованию</option>
                            <option value="3">На обновлении</option>
                            <option value="4">На свой страх и риск</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="createCheat();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="changeCheat" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-file mr-1" style="font-size: 16px"></i> Редактировать софт</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="title">Название</label>
                        <input id="title" class="form-control" placeholder="Введите название софта">
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="game_id">Игра</label>
                        <select class="form-control" id="game_id">
                            <option value="0">Не выбрана</option>
                        </select>
                    </div>
                    <div class="form-group text-left mt-4">
                        <label for="status">Статус</label>
                        <select class="form-control" id="status">
                            <option>Не выбрана</option>
                            <option value="1">Рекомендуем к использованию</option>
                            <option value="2">Не рекомендуем к использованию</option>
                            <option value="3">На обновлении</option>
                            <option value="4">На свой страх и риск</option>
                        </select>
                    </div>
                    <div class="form-group text-left mx-2 mt-4">
                        <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                            <input type="checkbox" class="custom-control-input d-block" id="is_notify" checked>
                            <label class="custom-control-label" for="is_notify" style="margin-left: 20px;">Уведомить пользователей</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="changeCheat();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
@endsection
