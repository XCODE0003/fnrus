@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-cloud-upload mr-1"></i> Файлы
                </h5>
                <a data-toggle="modal" data-target="#fileUpload" href="#" class="btn btn-sm btn-color shadow px-3">
                    <i class="far fa-upload"></i>
                    <span class="text d-none d-lg-inline ml-1">Загрузить</span>
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
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по файлам">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 5px"></th>
                            <th style="width: 5px">ID</th>
                            <th class="font-weight-normal text-uppercase">Название</th>
                            <th class="font-weight-normal text-uppercase">Тип</th>
                            <th class="font-weight-normal text-uppercase">Размер</th>
                            <th class="font-weight-normal text-uppercase">Загружено</th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="fileUpload" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="exampleModalLabel"><i class="far fa-upload mr-1" style="font-size: 16px"></i> Загрузить файл</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <label for="upload_files" class="custom-file-upload text-center mb-0">
                        <i class="far fa-upload mr-1" style="font-size:15px"></i> Выберите файл
                    </label>
                    <input id="upload_files" type="file" style="display: none;" name="file">
                    <div class="form-group d-none" id="block_result">
                        <input type="text" class="form-control text-center" id="result_url" />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
