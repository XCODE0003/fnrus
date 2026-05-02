@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header d-flex align-items-center">
                <h5 class="mr-auto m-0">
                    <i class="far fa-fw fa-star mr-1"></i> Отзывы
                </h5>
                <a data-toggle="modal" data-target="#createReview" href="#" class="btn btn-sm btn-color shadow px-3">
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
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input class="form-control form-control-sm my-1" type="text" id="input-search" placeholder="Поиск по отзывам">
                    </div>
                </div>
                <hr />
                <div class="table-responsive">
                    <table id="datatable" сlass="mdl-data-table w-100">
                        <thead>
                        <tr style="font-size: 13px">
                            <th style="width: 50px"></th>
                            <th class="font-weight-normal text-uppercase">Автор</th>
                            <th class="font-weight-normal text-uppercase">Текст</th>
                            <th class="font-weight-normal text-uppercase">Ссылка</th>
                            <th style="width: 15px"></th>
                            <th style="width: 15px"></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="createReview" tabindex="-1" role="dialog" aria-labelledby="createReviewLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="createReviewLabel"><i class="far fa-star mr-1" style="font-size: 16px"></i> Новый отзыв</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="review_author">Автор</label>
                        <input id="review_author" class="form-control" placeholder="Имя автора">
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="review_author_link">Ссылка на автора</label>
                        <input id="review_author_link" class="form-control" placeholder="https://t.me/username">
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="review_text">Текст отзыва</label>
                        <textarea id="review_text" class="form-control" rows="4" placeholder="Текст отзыва"></textarea>
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="review_avatar">Аватар (URL)</label>
                        <input id="review_avatar" class="form-control" placeholder="https://example.com/avatar.jpg">
                    </div>
                    <div class="form-group text-left mt-2">
                        <label for="review_avatar_file">или загрузить файл</label>
                        <input type="file" id="review_avatar_file" class="form-control-file" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted">JPG, PNG, GIF, WebP. Макс. 5 МБ. Если выбран файл — URL игнорируется.</small>
                        <div id="review_avatar_preview" class="mt-2" style="display:none;">
                            <img src="" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">
                        </div>
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="review_link">Ссылка на отзыв</label>
                        <input id="review_link" class="form-control" placeholder="https://t.me/channel/123">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="createReview();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="changeReview" tabindex="-1" role="dialog" aria-labelledby="changeReviewLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title m-0" id="changeReviewLabel"><i class="far fa-edit mr-1" style="font-size: 16px"></i> Редактировать отзыв</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group text-left">
                        <label for="review_author">Автор</label>
                        <input id="review_author" class="form-control" placeholder="Имя автора">
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="review_author_link">Ссылка на автора</label>
                        <input id="review_author_link" class="form-control" placeholder="https://t.me/username">
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="review_text">Текст отзыва</label>
                        <textarea id="review_text" class="form-control" rows="4" placeholder="Текст отзыва"></textarea>
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="review_avatar">Аватар (URL)</label>
                        <input id="review_avatar" class="form-control" placeholder="https://example.com/avatar.jpg">
                    </div>
                    <div class="form-group text-left mt-2">
                        <label for="review_avatar_file">или загрузить файл</label>
                        <input type="file" id="review_avatar_file" class="form-control-file" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted">JPG, PNG, GIF, WebP. Макс. 5 МБ. Если выбран файл — URL игнорируется.</small>
                        <div id="review_avatar_preview" class="mt-2" style="display:none;">
                            <img src="" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">
                        </div>
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="review_link">Ссылка на отзыв</label>
                        <input id="review_link" class="form-control" placeholder="https://t.me/channel/123">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="changeReview();"><i class="far fa-save mr-1"></i> Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.querySelectorAll('input[id="review_avatar_file"]').forEach(function(input) {
        input.addEventListener('change', function() {
            var preview = this.closest('.modal-body').querySelector('[id="review_avatar_preview"]');
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.style.display = 'block';
                    preview.querySelector('img').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.style.display = 'none';
            }
        });
    });
    </script>
@endsection
