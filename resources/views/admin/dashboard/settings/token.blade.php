@extends('admin.dashboard.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-12 col-md-9">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 text-left"><i class="far fa-fw fa-key mr-1"></i> Секретный токен</h5>
                    </div>
                    <div class="card-body p-5">
                        <form>
                            <div class="form-group">
                                <label for="access_token text-left">Токен бота (созданного через бота <a target="_blank" href="https://t.me/botFather" style="color: #4183c4">@botFather</a>)</label>
                                <input type="text" name="token" class="form-control" id="access_token" aria-describedby="access_token_help" placeholder="Вставьте сюда токен бота" value="856337120:AAGEGgNOw03QLeVYzyElCzTuAPVK9IlBVII">
                            </div>

                            <button onclick="save_info (this.form);return false;" class="btn col-sm-12 mb-1 btn-primary">Сохранить изменения</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editButton" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title m-0" id="exampleModalLabel">Редактировать кнопку</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div id="modal_loader">
                        <img width="500" src="https://flevix.com/wp-content/uploads/2019/07/Ring-Preloader.gif">
                    </div>
                    <div id="modal_body">
                        <div class="modal-body">
                            <div class="form-group text-left">
                                <label for="name_button">Название кнопки <span class="text-danger">*</span></label>
                                <input name="name" id="modal_name_button" class="form-control" placeholder="Напишите название кнопки">
                            </div>
                            <div id="block-no-home">
                                <div class="form-group text-left">
                                    <label for="text_page">Текст кнопки <span class="text-danger">*</span></label>
                                    <div id="standalone-container" class="edit-button">
                                        <div id="toolbar-container" class="ql-toolbar ql-snow">
    <span class="ql-formats">
      <button class="ql-bold" type="button"><svg viewBox="0 0 18 18"> <path class="ql-stroke" d="M5,4H9.5A2.5,2.5,0,0,1,12,6.5v0A2.5,2.5,0,0,1,9.5,9H5A0,0,0,0,1,5,9V4A0,0,0,0,1,5,4Z"></path> <path class="ql-stroke" d="M5,9h5.5A2.5,2.5,0,0,1,13,11.5v0A2.5,2.5,0,0,1,10.5,14H5a0,0,0,0,1,0,0V9A0,0,0,0,1,5,9Z"></path> </svg></button>
      <button class="ql-italic" type="button"><svg viewBox="0 0 18 18"> <line class="ql-stroke" x1="7" x2="13" y1="4" y2="4"></line> <line class="ql-stroke" x1="5" x2="11" y1="14" y2="14"></line> <line class="ql-stroke" x1="8" x2="10" y1="14" y2="4"></line> </svg></button>
      <button class="ql-strike" type="button"><svg viewBox="0 0 18 18"> <line class="ql-stroke ql-thin" x1="15.5" x2="2.5" y1="8.5" y2="9.5"></line> <path class="ql-fill" d="M9.007,8C6.542,7.791,6,7.519,6,6.5,6,5.792,7.283,5,9,5c1.571,0,2.765.679,2.969,1.309a1,1,0,0,0,1.9-.617C13.356,4.106,11.354,3,9,3,6.2,3,4,4.538,4,6.5a3.2,3.2,0,0,0,.5,1.843Z"></path> <path class="ql-fill" d="M8.984,10C11.457,10.208,12,10.479,12,11.5c0,0.708-1.283,1.5-3,1.5-1.571,0-2.765-.679-2.969-1.309a1,1,0,1,0-1.9.617C4.644,13.894,6.646,15,9,15c2.8,0,5-1.538,5-3.5a3.2,3.2,0,0,0-.5-1.843Z"></path> </svg></button>
      <button class="ql-link" type="button"><svg viewBox="0 0 18 18"> <line class="ql-stroke" x1="7" x2="11" y1="7" y2="11"></line> <path class="ql-even ql-stroke" d="M8.9,4.577a3.476,3.476,0,0,1,.36,4.679A3.476,3.476,0,0,1,4.577,8.9C3.185,7.5,2.035,6.4,4.217,4.217S7.5,3.185,8.9,4.577Z"></path> <path class="ql-even ql-stroke" d="M13.423,9.1a3.476,3.476,0,0,0-4.679-.36,3.476,3.476,0,0,0,.36,4.679c1.392,1.392,2.5,2.542,4.679.36S14.815,10.5,13.423,9.1Z"></path> </svg></button>
    </span>
                                        </div>
                                        <div id="editor-container" class="ql-container ql-snow"><div class="ql-editor ql-blank" data-gramm="false" contenteditable="true"><p><br></p></div><div class="ql-clipboard" contenteditable="true" tabindex="-1"></div><div class="ql-tooltip ql-hidden"><a class="ql-preview" target="_blank" href="about:blank"></a><input type="text" data-formula="e=mc^2" data-link="https://quilljs.com" data-video="Embed URL"><a class="ql-action"></a><a class="ql-remove"></a></div></div>
                                    </div>
                                    <div class="alert alert-primary mt-2" role="alert">
                                        Максимальное количество символов с фото - <b>1024</b><br>
                                        Максимальное количество символов без фото - <b>4096</b>
                                    </div>
                                    <input type="hidden" id="modal_button_id" name="button_id">
                                </div>
                                <div class="form-group text-left">
                                    <label for="name_button">Дополнительная кнопка</label>
                                    <input name="param_btn0" id="modal_button_param_btn0" class="form-control" placeholder="Название кнопки">
                                </div>
                                <div class="form-group text-left">
                                    <input name="param_btn1" id="modal_button_param_btn1" class="form-control" placeholder="Ссылка кнопки">
                                </div>
                                <div class="form-group text-left">
                                    <label>Изображение</label>
                                    <input type="hidden" id="modal_button_image" name="image">
                                    <div id="modal_button_image_block"></div>
                                    <center><label for="btn_modal_edit_button" class="custom-file-upload">
                                            <i class="fal fa-cloud-upload mr-1"></i> Выберите изображение
                                        </label>
                                        <input id="btn_modal_edit_button" type="file" style="display: none;" name="file"></center>
                                </div>
                                <div class="form-group text-center">
                                    Обязательное поле <span class="text-danger">*</span>
                                    <hr>
                                    <a style="color:#cc0000;cursor:pointer" id="modal_del_button" data-id="0">Удалить кнопку</a>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Отмена</button>
                            <button class="btn btn-primary" onclick="modal_edit_button (this.form);return false;">Сохранить</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
