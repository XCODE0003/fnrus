@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 text-left"><i class="far fa-fw fa-file-contract mr-1"></i> Пользовательское соглашение</h5>
                    </div>
                    <div class="card-body p-4">
                        <h6 class="text-white mb-2">Русский</h6>
                        <div id="policy_editor_ru" style="height: 500px; border: 2px solid #4a90d9; background: #141c27; border-radius: 4px;"></div>

                        <h6 class="text-white mb-2 mt-4">English</h6>
                        <div id="policy_editor_en" style="height: 500px; border: 2px solid #4a90d9; background: #141c27; border-radius: 4px;"></div>

                        <button onclick="savePolicy();" class="btn btn-primary col-sm-12 mt-3">
                            <i class="far fa-save mr-1"></i> Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
