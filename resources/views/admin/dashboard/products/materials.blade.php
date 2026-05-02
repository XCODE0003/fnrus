@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header d-flex align-items-center">
            <h5 class="mr-auto m-0">
                <i class="far fa-fw fa-file-alt mr-1"></i> Материалы
            </h5>
            <a data-toggle="modal" data-target="#addMaterial" href="#" class="btn btn-sm btn-color shadow px-3 ml-1">
                <i class="far fa-plus"></i>
                <span class="text d-none d-lg-inline ml-1">Добавить</span>
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
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по материалам ">
                    </div>
                  <!--   <div class="col-md-2">
                        <button class="btn btn-primary w-100" style="line-height: 1.5;font-size: .875rem;padding-top: 7px;padding-bottom: 7px;position: relative;bottom: -4px;">Поиск дублей</button>
                    </div> -->
                </div>
                <hr />
            <div class="table-responsive">
                <table id="datatable" сlass="mdl-data-table w-100">
                    <thead>
                    <tr style="font-size: 13px">
                        <th style="width: 5px"></th>
                        <th class="font-weight-normal text-uppercase" style="width: 250px">Товар</th>
                        <th class="font-weight-normal text-uppercase">Тариф</th>
                        <th class="font-weight-normal text-uppercase">Материал</th>
                        <th class="font-weight-normal text-uppercase" style="width: 150px">Статус</th>
                        <th style="width: 15px"></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="addMaterial" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-fw fa-plus mr-1" style="font-size: 16px"></i> Добавить материал</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label>Товар <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" name="good_id" id="select_good">
                            <option value="0">Выберите товар</option>
                        </select>
                    </div>
                    <div class="form-group text-left">
                        <label for="text_page">Материал <span class="text-danger">*</span>
                        </label>
                        <textarea name="result" class="form-control" placeholder="Вставьте материал (один в строчку, или оставьте один материал и поставьте галочку ниже)" style="min-width: 251px;height: 150px;"></textarea>
                    </div>
                    <div class="form-group text-left" style="display: none;">
                        <label>ZIP архив с файлами</label>
                        <input type="hidden" id="zip" name="zip">
                        <div id="filename_zip"></div>
                        <center>
                            <label for="file-upload-zip" class="custom-file-upload">
                                <i class="fal fa-cloud-upload mr-1"></i> Выберите архив </label>
                            <input id="file-upload-zip" type="file" style="display: none;" name="file">
                        </center>
                    </div>
                    <div class="custom-control custom-checkbox mb-4">
                        <input type="checkbox" name="loop" value="1" class="custom-control-input" id="loop_toggle">
                        <label class="custom-control-label" for="loop_toggle" style="cursor: pointer;">Выдавать всем пользователям один материал бесконечное количество раз</label>
                    </div>
                    <hr>
                    <div class="form-group text-center"> Обязательное поле <span class="text-danger">*</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="add_material (this.form);return false;">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
