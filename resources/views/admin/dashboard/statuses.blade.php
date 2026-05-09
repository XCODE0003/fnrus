@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-toggle-on mr-1"></i> Статусы софта
                </h5>
                <a data-toggle="modal" data-target="#manageChannels" href="#" class="btn btn-sm btn-secondary shadow px-3 mr-2">
                    <i class="far fa-paper-plane"></i>
                    <span class="text d-none d-lg-inline ml-1">Telegram-каналы</span>
                </a>
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

    {{-- Create modal: minimal — title/game/status only --}}
    <div class="modal fade" id="createCheat" tabindex="-1" role="dialog" aria-labelledby="createCheatLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="createCheatLabel"><i class="far fa-file mr-1" style="font-size: 16px"></i> Новый софт</h5>
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

    {{-- Edit modal: full publication form (template, image, channels) --}}
    <div class="modal fade" id="changeCheat" tabindex="-1" role="dialog" aria-labelledby="changeCheatLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="changeCheatLabel"><i class="far fa-file mr-1" style="font-size: 16px"></i> Редактировать софт</h5>
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

                    <hr class="my-4">

                    <div class="form-group text-left">
                        <label class="d-flex align-items-center justify-content-between">
                            <span>Шаблон сообщения для Telegram</span>
                            <small class="text-muted">Доступны эмодзи и форматирование</small>
                        </label>
                        <div class="mb-2">
                            <small class="text-muted mr-2">Плейсхолдеры:</small>
                            <button type="button" class="btn btn-outline-secondary btn-sm mr-1 cheat-placeholder" data-token="{game}">{game}</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm mr-1 cheat-placeholder" data-token="{product}">{product}</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm cheat-placeholder" data-token="{status}">{status}</button>
                        </div>
                        <textarea id="message_template" rows="6" class="form-control" placeholder="Шаблон публикации"></textarea>
                    </div>

                    <div class="form-group text-left mt-4">
                        <label>Изображение к публикации</label>
                        <div class="d-flex align-items-center">
                            <input type="file" id="cheat_image_file" accept="image/png,image/jpeg,image/webp" class="d-none">
                            <button type="button" id="cheat_image_pick" class="btn btn-secondary btn-sm">
                                <i class="far fa-upload mr-1"></i> Загрузить
                            </button>
                            <button type="button" id="cheat_image_remove" class="btn btn-link btn-sm text-danger ml-2 d-none">
                                <i class="far fa-trash-alt mr-1"></i> Удалить
                            </button>
                            <small id="cheat_image_status" class="text-muted ml-3"></small>
                        </div>
                        <input type="hidden" id="cheat_image_path">
                        <div id="cheat_image_preview" class="mt-2 d-none">
                            <img src="" alt="Изображение публикации" style="max-height:140px;border-radius:6px;">
                        </div>
                    </div>

                    <div class="form-group text-left mt-4">
                        <label>Telegram-каналы для рассылки</label>
                        <div id="cheat_channels_list" class="border rounded p-2" style="max-height:160px;overflow-y:auto">
                            <small class="text-muted">Загрузка…</small>
                        </div>
                        <small class="text-muted">Тогглы переключают активность канала глобально.</small>
                    </div>

                    <div class="form-group text-left mx-2 mt-4">
                        <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                            <input type="checkbox" class="custom-control-input d-block" id="is_notify" checked>
                            <label class="custom-control-label" for="is_notify" style="margin-left: 20px;">Разослать в Telegram при сохранении (если статус изменился)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="changeCheat();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Channel management modal --}}
    <div class="modal fade" id="manageChannels" tabindex="-1" role="dialog" aria-labelledby="manageChannelsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="manageChannelsLabel"><i class="far fa-paper-plane mr-1"></i> Telegram-каналы</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label>Добавить канал</label>
                            <div class="d-flex align-items-end" style="gap:.5rem">
                                <div class="flex-grow-1">
                                    <input id="tgch_title" class="form-control form-control-sm" placeholder="Название (для админки)">
                                </div>
                                <div class="flex-grow-1">
                                    <input id="tgch_chat_id" class="form-control form-control-sm" placeholder="@username или -1001234567890">
                                </div>
                                <div>
                                    <select id="tgch_type" class="form-control form-control-sm">
                                        <option value="channel">Канал</option>
                                        <option value="group">Группа</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="tgChannelCreate()">
                                    <i class="far fa-plus mr-1"></i> Добавить
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="tgch_list">
                        <small class="text-muted">Загрузка…</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TinyMCE: loaded only on this page. Self-hosted via cdn.jsdelivr.net (community core, GPL2) --}}
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="/assets/js/admin/statuses.js?v={{ time() }}"></script>
@endsection
