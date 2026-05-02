@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-envelope mr-1"></i> Рассылки
                </h5>
                <a data-toggle="modal" data-target="#createSender" href="#" class="btn btn-sm btn-color shadow px-3">
                    <i class="far fa-plus"></i>
                    <span class="text d-none d-lg-inline ml-1">Создать</span>
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <select class="form-control form-control-sm my-1" id="input-length">
                            <option value="10">Показать: 10</option>
                            <option value="25">Показать: 25</option>
                            <option value="50">Показать: 50</option>
                            <option value="100">Показать: 100</option>
                            <option value="500">Показать: 500</option>
                            <option value="1000">Показать: 1000</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control form-control-sm my-1" id="input-status">
                            <option value="">Статус: Любой</option>
                            <option value="1">Статус: Ожидает запуска</option>
                            <option value="2">Статус: Выполнено</option>
                            <option value="4">Статус: Выполняется</option>
                            <option value="5">Статус: Остановлено</option>
                            <option value="6">Статус: Магазин удален</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по рассылкам">
                    </div>
                </div>
                <!-- <div id="control_panel" class="p-3 py-2 d-none" style="background: #222; position: fixed; bottom: 22px; border-radius: 14px; right: 22px; z-index: 1000;"></div> -->
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th class="font-weight-normal text-uppercase">Название</th>
                            <th class="font-weight-normal text-uppercase">Тип</th>
                            <th class="font-weight-normal text-uppercase">Прогресс</th>
                            <th class="font-weight-normal text-uppercase">Дата запуска</th>
                            <th class="font-weight-normal text-uppercase">Статус</th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="createSender" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 800px" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0">
                        <i class="far fa-fw fa-plus mr-1"></i> Новая рассылка
                    </h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row" id="block_message">
                        <div class="col-md-6">
                            <div class="form-group text-left">
                                <label>Название</label>
                                <input id="title" class="form-control" placeholder="Напишите название" />
                            </div>
                            <div class="form-group text-left mt-4">
                                <label for="text_sender">Описание</label>
                                <div id="text_sender_toolbar"></div>
                                <div id="text_sender"></div>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Когда отправить</label>
                                <select class="form-control" id="type_time" onchange="showTime('createSender', 1);">
                                    <option value="0">Сейчас</option>
                                    <option value="1">Позже</option>
                                </select>
                            </div>
                            <div class="form-group text-left mt-4 d-none" id="started_at">
                                <label>Время запуска</label>
                                <div class="input-group text-center">
                                    <select class="form-control text-center" style="width: 5%" id="date_day" name="started_at">
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                        <option value="24">24</option>
                                        <option value="25">25</option>
                                        <option value="26">26</option>
                                        <option value="27">27</option>
                                        <option value="28">28</option>
                                        <option value="29">29</option>
                                        <option value="30">30</option>
                                        <option value="31">31</option>
                                    </select>
                                    <select class="form-control text-center" style="width: 15%" id="date_month" name="started_at">
                                        <option value="01">Января</option>
                                        <option value="02">Февраля</option>
                                        <option value="03">Марта</option>
                                        <option value="04">Апреля</option>
                                        <option value="05">Мая</option>
                                        <option value="06">Июня</option>
                                        <option value="07">Июля</option>
                                        <option value="08">Августа</option>
                                        <option value="09">Сентября</option>
                                        <option value="10">Октября</option>
                                        <option value="11">Ноября</option>
                                        <option value="12">Декабря</option>
                                    </select>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">в</span>
                                    </div>
                                    <select class="form-control text-center" style="width: 5%" id="date_hours" name="started_at">
                                        <option value="00">00</option>
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                    </select>
                                    <select class="form-control text-center" style="width: 5%" id="date_minutes" name="started_at">
                                        <option value="00">00</option>
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                        <option value="24">24</option>
                                        <option value="25">25</option>
                                        <option value="26">26</option>
                                        <option value="27">27</option>
                                        <option value="28">28</option>
                                        <option value="29">29</option>
                                        <option value="30">30</option>
                                        <option value="31">31</option>
                                        <option value="32">32</option>
                                        <option value="33">33</option>
                                        <option value="34">34</option>
                                        <option value="35">35</option>
                                        <option value="36">36</option>
                                        <option value="37">37</option>
                                        <option value="38">38</option>
                                        <option value="39">39</option>
                                        <option value="40">40</option>
                                        <option value="41">41</option>
                                        <option value="42">42</option>
                                        <option value="43">43</option>
                                        <option value="44">44</option>
                                        <option value="45">45</option>
                                        <option value="46">46</option>
                                        <option value="47">47</option>
                                        <option value="48">48</option>
                                        <option value="49">49</option>
                                        <option value="50">50</option>
                                        <option value="51">51</option>
                                        <option value="52">52</option>
                                        <option value="53">53</option>
                                        <option value="54">54</option>
                                        <option value="55">55</option>
                                        <option value="56">56</option>
                                        <option value="57">57</option>
                                        <option value="58">58</option>
                                        <option value="59">59</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group text-left">
                                <label class="mb-1">Изображение</label>
                                <div class="row">
                                    <div class="col-10">
                                        <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                        <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Загружено</div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-3" id="btn_delete_image"></div>
                                    </div>
                                    <div class="col-12">
                                        <label id="btn_upload_image" for="cs_image_upload" class="custom-file-upload text-center mb-0">
                                            <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                        </label>
                                        <input id="cs_image_upload" type="file" style="display: none;" name="file">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-left mt-4" id="block_buttons" style="">
                                <label class="form-label d-block">Кнопки</label>

                                <div id="blocks_buttons"></div>
                                <button class="btn btn-outline-secondary w-100 mb-2" id="btn-add-button" onclick="addBlockButton(2,'createSender')"><i class="far fa-plus mr-1"></i> Добавить кнопку</button>
                            </div>

                            <div class="form-group text-left mx-0 mt-4">
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="cs_disable_web_page_preview">
                                    <label class="custom-control-label" for="cs_disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                                </div>
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="cs_has_spoiler" disabled>
                                    <label class="custom-control-label" for="cs_has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-primary" onclick="checkSender('createSender');"><i class="far fa-envelope mr-1" style="font-size: 14px"></i> Отправить себе</button>
                    <button class="btn btn-primary" onclick="saveSender();"><i class="far fa-play mr-1" style="font-size: 14px"></i> Запустить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editSender" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 800px" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0">
                        <i class="far fa-fw fa-edit mr-1"></i> Редактирование рассылки
                    </h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row" id="block_message">
                        <div class="col-md-6">
                            <div class="form-group text-left">
                                <label>Название</label>
                                <input id="title" class="form-control" placeholder="Напишите название" />
                            </div>
                            <div class="form-group text-left mt-4">
                                <label for="text_sender">Описание</label>
                                <div id="text_sender_toolbar"></div>
                                <div id="text_sender"></div>
                            </div>
                            <div class="form-group text-left mt-4">
                                <label>Когда отправить</label>
                                <select class="form-control" id="type_time" onchange="showTime('editSender', 1);">
                                    <option value="0">Сейчас</option>
                                    <option value="1">Позже</option>
                                </select>
                            </div>
                            <div class="form-group text-left mt-4 d-none" id="started_at">
                                <label>Время запуска</label>
                                <div class="input-group text-center">
                                    <select class="form-control text-center" style="width: 5%" id="date_day" name="started_at">
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                        <option value="24">24</option>
                                        <option value="25">25</option>
                                        <option value="26">26</option>
                                        <option value="27">27</option>
                                        <option value="28">28</option>
                                        <option value="29">29</option>
                                        <option value="30">30</option>
                                        <option value="31">31</option>
                                    </select>
                                    <select class="form-control text-center" style="width: 15%" id="date_month" name="started_at">
                                        <option value="01">Января</option>
                                        <option value="02">Февраля</option>
                                        <option value="03">Марта</option>
                                        <option value="04">Апреля</option>
                                        <option value="05">Мая</option>
                                        <option value="06">Июня</option>
                                        <option value="07">Июля</option>
                                        <option value="08">Августа</option>
                                        <option value="09">Сентября</option>
                                        <option value="10">Октября</option>
                                        <option value="11">Ноября</option>
                                        <option value="12">Декабря</option>
                                    </select>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">в</span>
                                    </div>
                                    <select class="form-control text-center" style="width: 5%" id="date_hours" name="started_at">
                                        <option value="00">00</option>
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                    </select>
                                    <select class="form-control text-center" style="width: 5%" id="date_minutes" name="started_at">
                                        <option value="00">00</option>
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                        <option value="24">24</option>
                                        <option value="25">25</option>
                                        <option value="26">26</option>
                                        <option value="27">27</option>
                                        <option value="28">28</option>
                                        <option value="29">29</option>
                                        <option value="30">30</option>
                                        <option value="31">31</option>
                                        <option value="32">32</option>
                                        <option value="33">33</option>
                                        <option value="34">34</option>
                                        <option value="35">35</option>
                                        <option value="36">36</option>
                                        <option value="37">37</option>
                                        <option value="38">38</option>
                                        <option value="39">39</option>
                                        <option value="40">40</option>
                                        <option value="41">41</option>
                                        <option value="42">42</option>
                                        <option value="43">43</option>
                                        <option value="44">44</option>
                                        <option value="45">45</option>
                                        <option value="46">46</option>
                                        <option value="47">47</option>
                                        <option value="48">48</option>
                                        <option value="49">49</option>
                                        <option value="50">50</option>
                                        <option value="51">51</option>
                                        <option value="52">52</option>
                                        <option value="53">53</option>
                                        <option value="54">54</option>
                                        <option value="55">55</option>
                                        <option value="56">56</option>
                                        <option value="57">57</option>
                                        <option value="58">58</option>
                                        <option value="59">59</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group text-left">
                                <label class="mb-1">Изображение</label>
                                <div class="row">
                                    <div class="col-10">
                                        <div class="d-none" id="block_upload_image" data-attach="" style="float:left"></div>
                                        <div class="d-none" id="text_image_uploaded" style="line-height:50px;float:left;margin-left: 20px">Изображение загружено</div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-3" id="btn_delete_image"></div>
                                    </div>
                                    <div class="col-12">
                                        <label id="btn_upload_image" for="es_image_upload" class="custom-file-upload text-center mb-0">
                                            <i class="far fa-upload mr-1" style="font-size:15px"></i> Загрузите изображение
                                        </label>
                                        <input id="es_image_upload" type="file" style="display: none;" name="file">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-left mt-4" id="block_buttons" style="">
                                <label class="form-label d-block">Кнопки</label>

                                <div id="blocks_buttons"></div>
                                <button class="btn btn-outline-secondary w-100 mb-2" id="btn-add-button" onclick="addBlockButton(2,'editSender')"><i class="far fa-plus mr-1"></i> Добавить кнопку</button>
                            </div>

                            <div class="form-group text-left mx-0 mt-4">
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="es_disable_web_page_preview">
                                    <label class="custom-control-label" for="es_disable_web_page_preview" style="margin-left: 20px;">Отключить предпросмотр ссылок</label>
                                </div>
                                <div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">
                                    <input type="checkbox" class="custom-control-input d-block" id="es_has_spoiler" disabled>
                                    <label class="custom-control-label" for="es_has_spoiler" style="margin-left: 20px;">Включить спойлер на изображение</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-primary" onclick="checkSender('editSender');"><i class="far fa-envelope mr-1" style="font-size: 14px"></i> Отправить себе</button>
                    <button class="btn btn-primary" id="btn-save" onclick="saveSender();"><i class="far fa-save mr-1" style="font-size: 14px"></i> Сохранить и запустить</button>
                </div>
            </div>
        </div>
    </div>
@endsection
