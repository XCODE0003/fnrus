@extends('dashboard.layouts.main')
@section('content')
    <div class="container-fluid">

        <form name="formEditCat" id="edit_category" class="row">
            <div class="col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-left"><i class="fas fa-fw fa-file-alt"></i> Основная информация</h6>
                    </div>
                    <div class="card-body p-4">

                        <div class="form-group text-left">
                            <label>Категория</label>
                            <select class="form-control" name="cat_id">
                                <option value="0" selected="">Без категории</option><option value="22734">- Игры</option><option value="22733">Развлечения</option><option value="22732">VPN</option>  </select>
                        </div>
                        <div class="form-group text-left">
                            <label>Название категории <span class="text-danger">*</span></label>
                            <input name="name" class="form-control" placeholder="Напишите название" value="VPN">
                        </div>

                        <div class="form-group text-left">
                            <label for="text_page">Описание категории</label>
                            <div id="text_message_toolbar"></div>
                            <div id="text_message" style="height: 110px;"></div>
                        </div>
                        <div class="form-group text-left">
                            <label>Видимость категории</label>
                            <select class="form-control" name="hide">
                                <option value="0" selected="">Видно</option>
                                <option value="1">Не видно</option>
                            </select>
                        </div>
                        <div class="form-group text-center">
                            Обязательное поле <span class="text-danger">*</span>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-lg-5 mb-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-left"><i class="far fa-image"></i> Изображение</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-group text-left">
                            <input type="hidden" id="file" name="cover" value="">
                            <div id="filename_cat"></div>
                            <center><label for="file-upload" class="custom-file-upload"><i class="far fa-upload mr-1" style="font-size: 15px"></i>  Выберите изображение</label>
                                <input id="file-upload" type="file" style="display: none;" name="file">
                                <div id="btn_del">
                                </div>
                            </center>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fixed-bottom text-right mb-4 px-1" style="z-index:12;"><button class="btn btn-primary font-weight-bold py-2 px-4 mr-4" onclick="edit_cat (this.form);return false;"><i class="fa fa-save mr-1"></i> Сохранить</button></div>
        </form>

    </div>
@endsection
