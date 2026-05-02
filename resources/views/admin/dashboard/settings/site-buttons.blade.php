@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10 col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 text-left"><i class="far fa-fw fa-mouse-pointer mr-1"></i> Кнопки сайта</h5>
                    </div>
                    <div class="card-body p-4">
                        <h6 class="font-weight-bold mb-3"><i class="fab fa-telegram-plane mr-1"></i> Кнопка Telegram Bot (подвал сайта)</h6>
                        <div class="form-group">
                            <label for="btn_tg_bot_url">Ссылка</label>
                            <input type="text" class="form-control" id="btn_tg_bot_url" placeholder="https://t.me/...">
                        </div>
                        <div class="form-group">
                            <label for="btn_tg_bot_text">Текст кнопки</label>
                            <input type="text" class="form-control" id="btn_tg_bot_text" placeholder="Telegram Bot">
                        </div>
                        <div class="form-group">
                            <label for="btn_tg_bot_icon">Иконка</label>
                            <select class="form-control" id="btn_tg_bot_icon">
                                <option value="telegram">Telegram</option>
                                <option value="discord">Discord</option>
                                <option value="vk">VK</option>
                                <option value="youtube">YouTube</option>
                                <option value="instagram">Instagram</option>
                                <option value="star">Звезда</option>
                                <option value="link">Ссылка</option>
                            </select>
                        </div>
                        <hr class="my-4">
                        <h6 class="font-weight-bold mb-3"><i class="fab fa-telegram-plane mr-1"></i> Кнопка «Купить через бота» (главная)</h6>
                        <div class="form-group">
                            <label for="btn_buy_bot_url">Ссылка</label>
                            <input type="text" class="form-control" id="btn_buy_bot_url" placeholder="https://t.me/...">
                        </div>
                        <div class="form-group">
                            <label for="btn_buy_bot_text">Текст кнопки</label>
                            <input type="text" class="form-control" id="btn_buy_bot_text" placeholder="Купить через бота">
                        </div>
                        <div class="form-group">
                            <label for="btn_buy_bot_icon">Иконка</label>
                            <select class="form-control" id="btn_buy_bot_icon">
                                <option value="telegram">Telegram</option>
                                <option value="discord">Discord</option>
                                <option value="vk">VK</option>
                                <option value="youtube">YouTube</option>
                                <option value="instagram">Instagram</option>
                                <option value="star">Звезда</option>
                                <option value="link">Ссылка</option>
                            </select>
                        </div>
                        <hr class="my-4">
                        <h6 class="font-weight-bold mb-3"><i class="far fa-star mr-1"></i> Кнопка «Отзывы» (главная)</h6>
                        <div class="form-group">
                            <label for="btn_reviews_url">Ссылка</label>
                            <input type="text" class="form-control" id="btn_reviews_url" placeholder="https://t.me/...">
                        </div>
                        <div class="form-group">
                            <label for="btn_reviews_text">Текст кнопки</label>
                            <input type="text" class="form-control" id="btn_reviews_text" placeholder="@Palkey">
                        </div>
                        <div class="form-group">
                            <label for="btn_reviews_icon">Иконка</label>
                            <select class="form-control" id="btn_reviews_icon">
                                <option value="telegram">Telegram</option>
                                <option value="discord">Discord</option>
                                <option value="vk">VK</option>
                                <option value="youtube">YouTube</option>
                                <option value="instagram">Instagram</option>
                                <option value="star">Звезда</option>
                                <option value="link">Ссылка</option>
                            </select>
                        </div>
                        <hr class="my-4">
                        <button onclick="saveSiteButtons();" class="btn btn-primary col-sm-12">
                            <i class="far fa-save mr-1"></i> Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
