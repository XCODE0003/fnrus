let pathname = window.location.pathname;
let pathname_one = pathname.split('/')[1];
let pathname_two = pathname.split('/')[2];
let pathname_three = pathname.split('/')[3];
let addr_page = pathname_one+'/'+pathname_two;
let api_url = window.location.origin;

$(document).ready(function(){

loadSidebar();

if(pathname_one == 'admin'){

     statsProfit('today');
    statsSales('today');
}

if(addr_page == 'admin/orders'){

 $('#datatable').DataTable({
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'destroy': true,
      'searching': true,
      "order": [[ 6, "desc" ]],
      'columns': [
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': false },
  ],
      "ajax": "/api.orders.php",
        "language": {
      "processing": "Пожалуйста подождите..",
      "searchPlaceholder": "Поиск по таблице",
      "emptyTable": "У вас нет ни одного заказа",
      "info": "Страница _PAGE_ из _PAGES_",
      "infoEmpty": "Показано 0 из 0",
    "lengthMenu":     "_MENU_",
    "zeroRecords":    "По запросу не найдено ни одного результата",
      "search": "",
      "paginate": {
        "first":      "Первая",
        "last":       "Последняя",
        "next":       "Следующая",
        "previous":   "Предыдущая"
    }
    }
   });

}

if(addr_page == 'admin/pages'){

 $('#datatable').DataTable({
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'destroy': true,
      'searching': true,
      "order": [[ 0, "desc" ]],
      'columns': [
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': false },
    { 'orderable': false },
    { 'orderable': false },
  ],
      "ajax": "/api.pages.php",
        "language": {
      "processing": "Пожалуйста подождите..",
      "searchPlaceholder": "Поиск по таблице",
      "emptyTable": "У вас нет ни одной страницы",
      "info": "Страница _PAGE_ из _PAGES_",
      "infoEmpty": "Показано 0 из 0",
    "lengthMenu":     "_MENU_",
    "zeroRecords":    "По запросу не найдено ни одного результата",
      "search": "",
      "paginate": {
        "first":      "Первая",
        "last":       "Последняя",
        "next":       "Следующая",
        "previous":   "Предыдущая"
    }
    }
   });

}

if(addr_page == 'admin/news'){

 $('#datatable').DataTable({
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'destroy': true,
      'searching': true,
      "order": [[ 0, "desc" ]],
      'columns': [
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': false },
    { 'orderable': false },
    { 'orderable': false },
  ],
      "ajax": "/api.news.php",
        "language": {
      "processing": "Пожалуйста подождите..",
      "searchPlaceholder": "Поиск по таблице",
      "emptyTable": "У вас нет ни одной новости",
      "info": "Страница _PAGE_ из _PAGES_",
      "infoEmpty": "Показано 0 из 0",
    "lengthMenu":     "_MENU_",
    "zeroRecords":    "По запросу не найдено ни одного результата",
      "search": "",
      "paginate": {
        "first":      "Первая",
        "last":       "Последняя",
        "next":       "Следующая",
        "previous":   "Предыдущая"
    }
    }
   });

}

if(addr_page == 'admin/coupons'){

 $('#datatable').DataTable({
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'destroy': true,
      'searching': true,
      "order": [[ 0, "desc" ]],
      'columns': [
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': false },
    { 'orderable': false },
  ],
      "ajax": "/api.coupons.php",
        "language": {
      "processing": "Пожалуйста подождите..",
      "searchPlaceholder": "Поиск по таблице",
      "emptyTable": "У вас нет ни одной купона",
      "info": "Страница _PAGE_ из _PAGES_",
      "infoEmpty": "Показано 0 из 0",
    "lengthMenu":     "_MENU_",
    "zeroRecords":    "По запросу не найдено ни одного результата",
      "search": "",
      "paginate": {
        "first":      "Первая",
        "last":       "Последняя",
        "next":       "Следующая",
        "previous":   "Предыдущая"
    }
    }
   });

}

if(addr_page == 'admin/materials'){

 $('#datatable').DataTable({
     'stateSave': true,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'destroy': true,
      'searching': true,
      "order": [[ 0, "desc" ]],
      'columns': [
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': false },
  ],
      "ajax": api_url+"/api.materials.php",
        "language": {
      "processing": "Пожалуйста подождите..",
      "searchPlaceholder": "Поиск по таблице",
      "emptyTable": "У вас нет ни одного материала",
      "info": "Страница _PAGE_ из _PAGES_",
      "infoEmpty": "Показано 0 из 0",
    "lengthMenu":     "_MENU_",
    "zeroRecords":    "По запросу не найдено ни одного результата",
      "search": "",
      "paginate": {
        "first":      "Первая",
        "last":       "Последняя",
        "next":       "Следующая",
        "previous":   "Предыдущая"
    }
    }
   });

}

if(addr_page == 'admin/categories'){

 $('#datatable').DataTable({
     'stateSave': true,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'destroy': true,
      'searching': true,
      "order": [[ 0, "desc" ]],
      'columns': [
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': true },
    { 'orderable': false },
    { 'orderable': false },
    { 'orderable': false },
    { 'orderable': false },
  ],
      "ajax": api_url+"/api.categories.php",
        "language": {
      "processing": "Пожалуйста подождите..",
      "searchPlaceholder": "Поиск по таблице",
      "emptyTable": "У вас нет ни одной категории",
      "info": "Страница _PAGE_ из _PAGES_",
      "infoEmpty": "Показано 0 из 0",
    "lengthMenu":     "_MENU_",
    "zeroRecords":    "По запросу не найдено ни одного результата",
      "search": "",
      "paginate": {
        "first":      "Первая",
        "last":       "Последняя",
        "next":       "Следующая",
        "previous":   "Предыдущая"
    }
    }
   });

var fixHelper = function(e, ui) {
  ui.children().each(function() {
    $(this).width($(this).width());
  });
  return ui;
};

$('#datatable tbody').sortable({
  handle: '.handle',
  helper: fixHelper,
  stop: function() {
    $.ajax({
      url: api_url+'/api/categories/sort',
      method: 'post',
      data: $('#datatable input').serialize(),
      success: function(data){
      messageSystem(true,"Сохранено");
      }
    });
  }
});

}


if(addr_page == 'admin/goods'){

 $('#datatable').DataTable({
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'destroy': true,
      'searching': true,
      "order": [[ 0, "desc" ]],
      'columns': [
         { 'orderable': true },
         { 'orderable': true },
         { 'orderable': true },
         { 'orderable': true },
         { 'orderable': true },
     { 'orderable': true },
     { 'orderable': true },
    { 'orderable': false },
    { 'orderable': false },
    { 'orderable': false },
    { 'orderable': false },
    { 'orderable': false },
    { 'orderable': false },
  ],
      "ajax": "/api.products.php",
        "language": {
      "processing": "Пожалуйста подождите..",
      "searchPlaceholder": "Поиск по таблице",
      "emptyTable": "У вас нет ни одного товара",
      "info": "Страница _PAGE_ из _PAGES_",
      "infoEmpty": "Показано 0 из 0",
    "lengthMenu":     "_MENU_",
    "zeroRecords":    "По запросу не найдено ни одного результата",
      "search": "",
      "paginate": {
        "first":      "Первая",
        "last":       "Последняя",
        "next":       "Следующая",
        "previous":   "Предыдущая"
    }
    }
   });


var fixHelper = function(e, ui) {
  ui.children().each(function() {
    $(this).width($(this).width());
  });
  return ui;
};

$('#datatable tbody').sortable({
  handle: '.handle',
  helper: fixHelper,
  stop: function() {
    $.ajax({
      url: api_url+'/api/products/sort',
      method: 'post',
      data: $('#datatable input').serialize(),
      success: function(data){
      messageSystem(true,"Сохранено");
      }
    });
  }
});

}

});


if(pathname_one == 'admin'){



function statsProfit(period) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/stats/profit',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            period: period,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {

            if(data.status == true){
                $('#dashboard_profits_stats').text(data.result);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


function statsSales(period) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/stats/sales',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            period: period,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {

            if(data.status == true){
                $('#dashboard_sales_stats').text(data.result);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

function getProfitsSelectPeriod(){
    let period = $('#profits_select_period').val();
    statsProfit(period);
}

function getSalesSelectPeriod(){
    let period = $('#sales_select_period').val();
    statsSales(period);
}

}


if(addr_page == 'admin/payments'){


function changeMPayment(type) {

    let title = $('#form_'+type+' #in_title').val();
    let merchant_id = $('#form_'+type+' #in_merchant_id').val();
    let merchant_public = $('#form_'+type+' #in_merchant_public').val();
    let merchant_secret = $('#form_'+type+' #in_merchant_secret').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/mpayments/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            type: type,
            title: title,
            merchant_id: merchant_id,
            merchant_public: merchant_public,
            merchant_secret: merchant_secret,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                messageSystem(true,"Изменения сохранены",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}



function changePaymentStatus(type,status) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/mpayments/status',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            type: type,
            status: status,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

    $("#status_qw:checkbox").on("change", function() {
        $('#form_qw').toggle();
        if($("#status_qw:checked").val()){
            changePaymentStatus('qw',0);
        } else {
            changePaymentStatus('qw',1);
        }
    });

    $("#status_fk:checkbox").on("change", function() {
        $('#form_fk').toggle();
        if($("#status_fk:checked").val()){
            changePaymentStatus('fk',0);
        } else {
            changePaymentStatus('fk',1);
        }
    });

    $("#status_ym:checkbox").on("change", function() {
        $('#form_ym').toggle();
        if($("#status_ym:checked").val()){
            changePaymentStatus('ym',0);
        } else {
            changePaymentStatus('ym',1);
        }
    });
}

if(addr_page == 'admin/pages'){

function createPage() {

    let title = $('#addPage #in_title').val();
    let meta_description = $('#addPage #in_meta_description').val();
    let meta_keywords = $('#addPage #in_meta_keywords').val();
    let text = document.querySelector('#addPage #in_text').children[0].innerHTML;
    let shortname = $('#addPage #in_shortname').val();
    let visibility = $('#addPage #in_visibility').val();
   
    $.ajax({
        type: "POST",
        url: api_url+'/api/pages/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            title: title,
            meta_description: meta_description,
            meta_keywords: meta_keywords,
            text: text,
            shortname: shortname,
            visibility: visibility,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#addPage').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Страница добавлена",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}



function changePage(id) {

    let title = $('#editPage #in_title').val();
    let meta_description = $('#editPage #in_meta_description').val();
    let meta_keywords = $('#editPage #in_meta_keywords').val();
    let text = document.querySelector('#editPage #in_text').children[0].innerHTML;
    let shortname = $('#editPage #in_shortname').val();
    let visibility = $('#editPage #in_visibility').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/pages/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            title: title,
            meta_description: meta_description,
            meta_keywords: meta_keywords,
            text: text,
            shortname: shortname,
            visibility: visibility,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#editPage').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                messageSystem(true,"Изменения сохранены",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}



function visibilityPage(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/pages/visibility',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                if(data.visibility == 0){var text_desc = 'скрыта';}
                if(data.visibility == 1){var text_desc = 'общедоступена';}
                messageSystem(true,"Страница "+text_desc,3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}



function deletePage(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/pages/delete',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Страница удалена",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


var modalEditPage = document.getElementById('editPage')
modalEditPage.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $('#editPage #btn-save').attr('onclick','changePage('+id+');return false;');

  $.ajax({
        type: "POST",
        url: api_url+'/api/pages/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#editPage #in_title').val(data.result.title);
                $('#editPage #in_meta_description').val(data.result.meta_description);
                $('#editPage #in_meta_keywords').val(data.result.meta_keywords);

                if(data.result.text == ''){
                $('#editPage #block_text').hide();
                } else {
                $('#editPage #block_text').show();
                $('#editPage #in_text .ql-editor').html(data.result.text);
                }

                if(data.result.shortname == ''){
                $('#editPage #block_shortname').hide();
                } else {
                $('#editPage #block_shortname').show();
                $('#editPage #in_shortname').val(data.result.shortname); 
                }

                $('#editPage #in_visibility').val(data.result.visibility);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})


}

if(addr_page == 'admin/news'){


function createNews() {

    let title = $('#addNews #in_title').val();
    let meta_description = $('#addNews #in_meta_description').val();
    let meta_keywords = $('#addNews #in_meta_keywords').val();
    let text = document.querySelector('#addNews #in_text').children[0].innerHTML;
    let shortname = $('#addNews #in_shortname').val();
    let visibility = $('#addNews #in_visibility').val();
   
    $.ajax({
        type: "POST",
        url: api_url+'/api/news/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            title: title,
            meta_description: meta_description,
            meta_keywords: meta_keywords,
            text: text,
            shortname: shortname,
            visibility: visibility,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#addNews').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Страница добавлена",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}



function changeNews(id) {

    let title = $('#editNews #in_title').val();
    let meta_description = $('#editNews #in_meta_description').val();
    let meta_keywords = $('#editNews #in_meta_keywords').val();
    let text = document.querySelector('#editNews #in_text').children[0].innerHTML;
    let shortname = $('#editNews #in_shortname').val();
    let visibility = $('#editNews #in_visibility').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/news/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            title: title,
            meta_description: meta_description,
            meta_keywords: meta_keywords,
            text: text,
            shortname: shortname,
            visibility: visibility,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#editNews').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                messageSystem(true,"Изменения сохранены",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


function visibilityNews(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/news/visibility',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                if(data.visibility == 0){var text_desc = 'скрыта';}
                if(data.visibility == 1){var text_desc = 'общедоступена';}
                messageSystem(true,"Страница "+text_desc,3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}



function deleteNews(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/news/delete',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Страница удалена",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


var modalEditNews = document.getElementById('editNews')
modalEditNews.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $('#editNews #btn-save').attr('onclick','changeNews('+id+');return false;');

  $.ajax({
        type: "POST",
        url: api_url+'/api/news/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#editNews #in_title').val(data.result.title);
                $('#editNews #in_meta_description').val(data.result.meta_description);
                $('#editNews #in_meta_keywords').val(data.result.meta_keywords);

                if(data.result.text == ''){
                $('#editNews #block_text').hide();
                } else {
                $('#editNews #block_text').show();
                $('#editNews #in_text .ql-editor').html(data.result.text);
                }

                if(data.result.shortname == ''){
                $('#editNews #block_shortname').hide();
                } else {
                $('#editNews #block_shortname').show();
                $('#editNews #in_shortname').val(data.result.shortname); 
                }

                $('#editNews #in_visibility').val(data.result.visibility);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})

}

if(addr_page == 'admin/coupons'){

function copyToClipboard(id) {

  var $temp = $("<input>");
  $("body").append($temp);
  $temp.val($('#result-code[data-id="'+id+'"]').text()).select();
  document.execCommand("copy");
  $temp.remove();

messageSystem(true,"Купон скопирован",3000);

}

function createCoupon() {

    let type = $('#addCoupon #type_sale').val();
    let type_min_uses = $('#addCoupon #type_min_uses').val();
    let sale = $('#addCoupon #sale').val();
    let code = $('#addCoupon #code').val();
    let min_uses = $('#addCoupon #min_uses').val();
    let max_uses = $('#addCoupon #max_uses').val();
    let goods_ids = $('#addCoupon #goods_ids').val();
   
    $.ajax({
        type: "POST",
        url: api_url+'/api/coupons/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            type: type,
            type_min_uses: type_min_uses,
            sale: sale,
            code: code,
            min_uses: min_uses,
            max_uses: max_uses,
            goods_ids: goods_ids,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#addCoupon').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Купон добавлен",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

var modalAddCoupon = document.getElementById('addCoupon')
modalAddCoupon.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $.ajax({
        type: "POST",
        url: api_url+'/api/products/list',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){

                var select_products = '';

                $(data.result).each(function(index, e){
                  select_products += '<option value="'+e.id+'">'+e.title+'</option>';
                });

                $('#addCoupon #goods_ids').html(select_products);
                
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})

function deleteCoupon(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/coupons/delete',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Купон удален",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}
}

if(addr_page == 'admin/goods'){


function createProduct() {

    let cat_id = $('#addProduct #in_cat_id').val();
    let title = $('#addProduct #in_title').val();
    let meta_description = $('#addProduct #in_meta_description').val();
    let meta_keywords = $('#addProduct #in_meta_keywords').val();
    let description = document.querySelector('#addProduct #in_description').children[0].innerHTML;
    let information = document.querySelector('#addProduct #in_information').children[0].innerHTML;
    let system_requirements = document.querySelector('#addProduct #in_system_requirements').children[0].innerHTML;
    let link_review = $('#addProduct #in_link_review').val();
    let link_instruction = $('#addProduct #in_link_instruction').val();
    
    let shortname = $('#addProduct #in_shortname').val();
    let visibility = $('#addProduct #in_visibility').val();

    var functionals = [];
    
    var images = [];


    $(document.querySelectorAll('#addProduct #in_functional')).each(function(index, e){
     if(e.value != ''){
     functionals[index] = e.value;
     }
    });

    $(document.querySelectorAll('#addProduct #image_cover')).each(function(index, e){
     images[index] = e.getAttribute('data-id');
    });

    var price_fix = '';
    var tariffs = [];

    if ($('#addProduct #type_price').val() == 0) {
    var price_fix = $('#addProduct #in_price_fix').val();
    var tariffs = [];
    }

    if ($('#addProduct #type_price').val() == 1) {
    var price_fix = '';
    var tariffs = [];
    $(document.querySelectorAll('#addProduct #in_days')).each(function(index, e){
      var price = document.querySelectorAll('#addProduct #in_price')[index].value;
      if(e.value != '' && price != ''){
      tariffs[index] = {days: e.value, price: price};
      }
    });
    }

    $.ajax({
        type: "POST",
        url: api_url+'/api/products/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            cat_id: cat_id,
            title: title,
            meta_description: meta_description,
            meta_keywords: meta_keywords,
            description: description,
            information: information,
            system_requirements: system_requirements,
            link_review: link_review,
            link_instruction: link_instruction,
            functionals: functionals,
            price: price_fix,
            tariffs: tariffs,
            images: images,
            shortname: shortname,
            visibility: visibility,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#addProduct').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Товар добавлен",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}



function changeProduct(id) {

    let cat_id = $('#editProduct #in_cat_id').val();
    let title = $('#editProduct #in_title').val();
    let meta_description = $('#editProduct #in_meta_description').val();
    let meta_keywords = $('#editProduct #in_meta_keywords').val();
    let description = document.querySelector('#editProduct #in_description').children[0].innerHTML;
    let information = document.querySelector('#editProduct #in_information').children[0].innerHTML;
    let system_requirements = document.querySelector('#editProduct #in_system_requirements').children[0].innerHTML;
    let link_review = $('#editProduct #in_link_review').val();
    let link_instruction = $('#editProduct #in_link_instruction').val();
    
    let shortname = $('#editProduct #in_shortname').val();
    let visibility = $('#editProduct #in_visibility').val();

    var functionals = [];
    var images = [];

    $(document.querySelectorAll('#editProduct #in_functional')).each(function(index, e){
     if(e.value != ''){
     functionals[index] = e.value;
     }
    });

    $(document.querySelectorAll('#editProduct #image_cover')).each(function(index, e){
     images[index] = e.getAttribute('data-id');
    });

    var price_fix = '';
    var tariffs = [];

    if ($('#editProduct #type_price').val() == 0) {
    var price_fix = $('#editProduct #in_price_fix').val();
    var tariffs = [];
    }

    if ($('#editProduct #type_price').val() == 1) {
    var price_fix = '';
    $(document.querySelectorAll('#editProduct #in_days')).each(function(index, e){
      var price = document.querySelectorAll('#editProduct #in_price')[index].value;
      if(e.value != '' && price != ''){
      tariffs[index] = {days: e.value, price: price};
      }
    });
    }

    $.ajax({
        type: "POST",
        url: api_url+'/api/products/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            cat_id: cat_id,
            title: title,
            meta_description: meta_description,
            meta_keywords: meta_keywords,
            description: description,
            information: information,
            system_requirements: system_requirements,
            link_review: link_review,
            link_instruction: link_instruction,
            functionals: functionals,
            price: price_fix,
            tariffs: tariffs,
            images: images,
            shortname: shortname,
            visibility: visibility,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#editProduct').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Изменения сохранены",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


function addMaterial(id) {

    let tariff_id = $('#addMaterial #in_tariff_id').val();
    let body = $('#addMaterial #in_body').val();
    
    $.ajax({
        type: "POST",
        url: api_url+'/api/materials/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            tariff_id: tariff_id,
            body: body,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#addMaterial').modal('hide');
                $('#addMaterial #in_body').val('');
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Товар пополнен",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

function exportMaterial(id) {

    let tariff_id = $('#exportMaterial #in_tariff_id').val();
    let count = $('#exportMaterial #in_count').val();

    if($('#in_remove_availability').is(':checked')){
    var remove_availability = 1;
    } else {
    var remove_availability = 0;
    }
    
    $.ajax({
        type: "POST",
        url: api_url+'/api/materials/export',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            tariff_id: tariff_id,
            count: count,
            remove_availability: remove_availability,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
              $('#exportMaterial #in_count').val('');
              $('#exportMaterial #in_remove_availability').removeAttr('checked');
              $('#result_body').html('<textarea class="form-control" id="in_result_body" rows="5" placeholder="Выгруженный товар">'+data.body+'</textarea><button class="btn btn-success w-100 mt-2" id="copy-button">Скопировать</button>');
              var input  = document.getElementById("in_result_body");
              var button = document.getElementById("copy-button");

              button.addEventListener("click", function (event) {
                event.preventDefault();
                input.select();
                document.execCommand("copy");
              });
                // $('#exportMaterial').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                 // messageSystem(true,"Товар выгружен",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


 function showTypePrice(id) {
    var theSelect = $('#'+id+' #type_price').val();
    // alert(theSelect);
    if (theSelect === '0') {
        $('#'+id+' #block_price').show();
        $('#'+id+' #block_tariff').hide();
    }
    if (theSelect === '1') {
        $('#'+id+' #block_price').hide();
        $('#'+id+' #block_tariff').show();
    }
}

function addBlockFunctional(num,id) {
    var html = '<div class="row" id="block_functional_item" data-id="'+num+'"><div class="col-md-11"><div class="mb-3"><textarea class="form-control" id="in_functional" placeholder="Напишите информацию о функционале.." rows="3"></textarea></div></div><div class="col-md-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;" onclick="deleteBlockFunctional('+num+',\''+id+'\');return false;"></i></div></div>';
    num++
    $('#'+id+' #blocks_functional').append(html);
    $('#'+id+' #btn-add-functional').attr('onclick','addBlockFunctional('+num+',\''+id+'\')');
}

function addBlockTariff(num,id) {
    var html = '<div class="row" id="block_tariff_item" data-id="'+num+'"><div class="col-md-6"><div class="mb-3"><input type="number" class="form-control" id="in_days" placeholder="Кол-во дней"></div></div><div class="col-md-5"><div class="mb-3"><input type="number" class="form-control" id="in_price" placeholder="Цена"></div></div><div class="col-md-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;" onclick="deleteBlockTariff('+num+',\''+id+'\');return false;"></i></div></div>';
  num++
    $('#'+id+' #blocks_tariffs').append(html);
    $('#'+id+' #btn-add-tariff').attr('onclick','addBlockTariff('+num+',\''+id+'\')');
}

function deleteBlockTariff(num,id) {
  $('#'+id+' #block_tariff_item[data-id="'+num+'"]').remove();
}

function deleteBlockFunctional(num,id) {
  $('#'+id+' #block_functional_item[data-id="'+num+'"]').remove();
}


function visibilityProduct(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/products/visibility',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                if(data.visibility == 0){var text_desc = 'скрыт';}
                if(data.visibility == 1){var text_desc = 'общедоступен';}
                messageSystem(true,"Товар "+text_desc,3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

function deleteProduct(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/products/delete',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Товар удален",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

var modalAddProduct = document.getElementById('addProduct')
modalAddProduct.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $.ajax({
        type: "POST",
        url: api_url+'/api/categories/list',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){

                var select_cats = '<option value="0">Выберите категорию</option>';

                $(data.result).each(function(index, e){
                  select_cats += '<option value="'+e.id+'">'+e.title+'</option>';
                });

                $('#addProduct #in_cat_id').html(select_cats);
                
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})

var modalAddMaterial = document.getElementById('addMaterial')
modalAddMaterial.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $('#addMaterial #btn-add-material').attr('onclick','addMaterial('+id+');return false;');

    $.ajax({
        type: "POST",
        url: api_url+'/api/products/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
              if (data.result.tariff[0] != undefined){
                
                var select_tariffs = '';

                $(data.result.tariff).each(function(index, e){
                  select_tariffs += '<option value="'+index+'">'+e.price+' ₽ за '+e.days+' дней</option>';
                });

                $('#addMaterial #in_tariff_id').html(select_tariffs);

                $('#addMaterial #block_tariff').show();
              }

                $('#addMaterial #product_title').text(data.result.title);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

});

var modalExportMaterial = document.getElementById('exportMaterial')
modalExportMaterial.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $('#exportMaterial #btn-export-material').attr('onclick','exportMaterial('+id+');return false;');

    $.ajax({
        type: "POST",
        url: api_url+'/api/products/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
        }),
        async: false,
        success: function(data) {
            if(data.status == true){

                if (data.result.tariff[0] != undefined){
                
                var select_tariffs = '';

                $(data.result.tariff).each(function(index, e){
                  select_tariffs += '<option value="'+index+'">'+e.price+' ₽ за '+e.days+' дней</option>';
                });

                $('#exportMaterial #in_tariff_id').html(select_tariffs);

                $('#exportMaterial #block_tariff').show();
              }

                $('#exportMaterial #product_title').text(data.result.title);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

});


var modalEditProduct = document.getElementById('editProduct')
modalEditProduct.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $('#editProduct #btn-save').attr('onclick','changeProduct('+id+');return false;');

    $.ajax({
        type: "POST",
        url: api_url+'/api/categories/list',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){

                var select_cats = '<option value="0">Выберите категорию</option>';

                $(data.result).each(function(index, e){
                  select_cats += '<option value="'+e.id+'">'+e.title+'</option>';
                });

                $('#editProduct #in_cat_id').html(select_cats);
                
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

  $.ajax({
        type: "POST",
        url: api_url+'/api/products/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#editProduct #in_cat_id').val(data.result.cat_id);
                $('#editProduct #in_title').val(data.result.title);
                $('#editProduct #in_meta_description').val(data.result.meta_description);
                $('#editProduct #in_meta_keywords').val(data.result.meta_keywords);
                $('#editProduct #in_description .ql-editor').html(data.result.description);
                $('#editProduct #in_information .ql-editor').html(data.result.information);
                $('#editProduct #in_system_requirements .ql-editor').html(data.result.system_requirements);
                $('#editProduct #in_link_review').val(data.result.link_review);
                $('#editProduct #in_link_instruction').val(data.result.link_instruction);
                $('#editProduct #in_shortname').val(data.result.shortname);
                $('#editProduct #in_visibility').val(data.result.visibility);

                if (!data.result.tariff[0]) {
                  $('#editProduct #type_price').val(0);
                  $('#editProduct #block_price').show();
                  $('#editProduct #block_tariff').hide();
                  $('#editProduct #in_price_fix').val(data.result.tariff);
                } else {
                  $('#editProduct #type_price').val(1);
                  $('#editProduct #block_price').hide();
                  $('#editProduct #block_tariff').show();

                  $('#editProduct #block_tariff_one').remove();

                  var blocks_tariffs = '';

                  $(data.result.tariff).each(function(index, e){

                    blocks_tariffs += '<div class="row" id="block_tariff_item" data-id="'+index+'"><div class="col-md-6"><div class="mb-3"><input type="number" class="form-control" id="in_days" value="'+e.days+'" placeholder="Кол-во дней"></div></div><div class="col-md-5"><div class="mb-3"><input type="number" class="form-control" id="in_price" value="'+e.price+'" placeholder="Цена"></div></div><div class="col-md-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;" onclick="deleteBlockTariff('+index+',\'editProduct\');return false;"></i></div></div>'; 

                  });

                  var count_tf = data.result.tariff.length;

                  $('#editProduct #btn-add-tariff').attr('onclick','addBlockTariff('+count_tf+',\'editProduct\')');
                  $('#editProduct #blocks_tariffs').html(blocks_tariffs);

                }

                var blocks_uploads = '';

                  $(data.result.images).each(function(index, e){
                    blocks_uploads += '<div id="image_cover" data-id="'+e.id+'"><div class="text-center"><img class="rounded" src="'+e.file+'" width="200" /></div><div class="text-center py-2"><a class="text-danger" href="#" onclick="deleteImageProduct(\''+e.id+'\');return false;">Удалить</a></div></div>';
                    
                  });

                  $('#editProduct #uploads').html(blocks_uploads);

                var block_functionals = '';

                $(data.result.functionals).each(function(index, e){

                  block_functionals += '<div class="row" id="block_functional_item" data-id="'+index+'"><div class="col-md-11"><div class="mb-3"><textarea class="form-control" id="in_functional" placeholder="Напишите информацию о функционале.." rows="3">'+e+'</textarea></div></div><div class="col-md-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;" onclick="deleteBlockFunctional('+index+',\'editProduct\');return false;"></i></div></div>';

                });

                var count_fs = data.result.functionals.length;

                $('#editProduct #btn-add-functional').attr('onclick','addBlockFunctional('+count_fs+',\'editProduct\')');
                $('#blocks_functional').html(block_functionals);
                
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})

}

if(addr_page == 'admin/categories'){

function createCategory() {

    let title = $('#addCat #in_title').val();
    let meta_description = $('#addCat #in_meta_description').val();
    let meta_keywords = $('#addCat #in_meta_keywords').val();
    let text = document.querySelector('#addCat #in_text').children[0].innerHTML;
    let shortname = $('#addCat #in_shortname').val();
    let visibility = $('#addCat #in_visibility').val();
    let cover = $('#addCat #in_attach').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/categories/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            title: title,
            meta_description: meta_description,
            meta_keywords: meta_keywords,
            text: text,
            shortname: shortname,
            visibility: visibility,
            cover: cover,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#addCat').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Категория добавлена",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

function changeCategory(id) {

    let title = $('#editCat #in_title').val();
    let meta_description = $('#editCat #in_meta_description').val();
    let meta_keywords = $('#editCat #in_meta_keywords').val();
    let text = document.querySelector('#editCat #in_text').children[0].innerHTML;
    let shortname = $('#editCat #in_shortname').val();
    let visibility = $('#editCat #in_visibility').val();
    let cover = $('#editCat #in_attach').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/categories/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            title: title,
            meta_description: meta_description,
            meta_keywords: meta_keywords,
            text: text,
            shortname: shortname,
            visibility: visibility,
            cover: cover,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#editCat').modal('hide');
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Изменения сохранены",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

function visibilityCategory(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/categories/visibility',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                if(data.visibility == 0){var text_desc = 'скрыта';}
                if(data.visibility == 1){var text_desc = 'общедоступна';}
                messageSystem(true,"Категория "+text_desc,3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

function deleteCategory(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/categories/delete',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Категория удалена",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

}


if(addr_page == 'admin/materials'){

function deleteMaterial(id) {

    $.ajax({
        type: "POST",
        url: api_url+'/api/materials/delete',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#datatable').DataTable().ajax.reload();
                 messageSystem(true,"Материал удален",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

}


function messageSystem(status,description,delay = 3000){

  if(status == false){
    $("#system_msg").removeClass().show().html('<span><i class="far fa-exclamation-circle me-2"></i>'+description+'</span>').addClass('error').delay(delay).fadeOut(200);
  } else if(status == true){
    $("#system_msg").removeClass().show().html('<span><i class="far fa-check-circle me-2"></i>'+description+'</span>').addClass('success').delay(delay).fadeOut(200);
  }

}


if(addr_page == 'admin/categories'){

function deleteImageCat(id) {
    $('#'+id+' #in_attach').val('');
    $('#'+id+' #image_cover').hide();
}

$('#addCat #in_cover').change(function(){

$(this).simpleUpload(api_url+"/api/cover/upload", {

    allowedExts: ["webp", "jpg", "jpeg", "png", "gif"],
    allowedTypes: ["image/webp", "image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],

    start: function(file){
        //upload started
    },
    progress: function(progress){
        //received progress
    },
    success: function(data){
        $('#addCat #block_cover').html('<div id="image_cover"><div class="text-center"><img class="rounded" src="'+data.result.file+'" width="200" /></div><div class="text-center py-2"><a class="text-danger" href="#" onclick="deleteImageCat(\'addCat\');return false;">Удалить</a></div></div>');
        $('#addCat #in_attach').val(data.result.id);
    },
    error: function(error){
        //upload failed
    }

});

});

$('#editCat #in_cover').change(function(){

$(this).simpleUpload(api_url+"/api/cover/upload", {

    allowedExts: ["webp", "jpg", "jpeg", "png", "gif"],
    allowedTypes: ["image/webp", "image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],

    start: function(file){
        //upload started
    },
    progress: function(progress){
        //received progress
    },
    success: function(data){
        $('#editCat #block_cover').html('<div id="image_cover"><div class="text-center"><img class="rounded" src="'+data.result.file+'" width="200" /></div><div class="text-center py-2"><a class="text-danger" href="#" onclick="deleteImageCat(\'editCat\');return false;">Удалить</a></div></div>');
        $('#editCat #in_attach').val(data.result.id);
    },
    error: function(error){
        //upload failed
    }

});

});

}


if(addr_page == 'admin/goods'){

  $('#addProduct #in_images').change(function(){

    $(this).simpleUpload("/api/images/upload", {

      allowedExts: ["webp", "jpg", "jpeg", "png", "gif"],
      allowedTypes: ["image/webp", "image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],

      start: function(file){
 
      },

      progress: function(progress){

      },

      success: function(data){
    

        if (data.status == true) {
          //now fill the block with the format of the uploaded file
          var formatDiv = '<div id="image_cover" data-id="'+data.result.id+'"><div class="text-center"><img class="rounded" src="'+data.result.file+'" width="200" /></div><div class="text-center py-2"><a class="text-danger" href="#" onclick="deleteImageProduct(\''+data.result.id+'\');return false;">Удалить</a></div></div>';
          $('#addProduct #uploads').append(formatDiv);

        }

      },

      error: function(error){
      
      }

    });

  });


  $('#editProduct #in_images').change(function(){

    $(this).simpleUpload("/api/images/upload", {

      allowedExts: ["webp", "jpg", "jpeg", "png", "gif"],
      allowedTypes: ["image/webp", "image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],

      start: function(file){
 
      },

      progress: function(progress){

      },

      success: function(data){
    

        if (data.status == true) {
          //now fill the block with the format of the uploaded file
          var formatDiv = '<div id="image_cover" data-id="'+data.result.id+'"><div class="text-center"><img class="rounded" src="'+data.result.file+'" width="200" /></div><div class="text-center py-2"><a class="text-danger" href="#" onclick="deleteImageProduct(\''+data.result.id+'\');return false;">Удалить</a></div></div>';
          $('#editProduct #uploads').append(formatDiv);

        }

      },

      error: function(error){
      
      }

    });

  });


  function deleteImageProduct(img_id) {
    $('#image_cover[data-id="'+img_id+'"]').remove();
  }

}

if(addr_page == 'admin/categories'){

var modalEditCat = document.getElementById('editCat')
modalEditCat.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $('#editCat #btn-save').attr('onclick','changeCategory('+id+');return false;');

  $.ajax({
        type: "POST",
        url: api_url+'/api/categories/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#editCat #in_title').val(data.result.title);
                $('#editCat #in_meta_description').val(data.result.meta_description);
                $('#editCat #in_meta_keywords').val(data.result.meta_keywords);
                $('#editCat #in_text .ql-editor').html(data.result.text);
                $('#editCat #in_shortname').val(data.result.shortname);
                $('#editCat #in_visibility').val(data.result.visibility);
                if(data.result.cover != undefined){
                $('#editCat #in_attach').val(data.result.cover.id);
                $('#editCat #block_cover').html('<div id="image_cover"><div class="text-center"><img class="rounded" src="'+data.result.cover.file+'" width="200" /></div><div class="text-center py-2"><a class="text-danger" href="#" onclick="deleteImageCat(\'editCat\');return false;">Удалить</a></div></div>');
                }
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})

}

var options_description = {
  debug: 'info',
  modules: {
    toolbar: '#addCat #in_text_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите описание..',
  theme: 'snow'
};

var options_description = {
  debug: 'info',
  modules: {
    toolbar: '#editCat #in_text_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите описание..',
  theme: 'snow'
};



  var options_description = {
  debug: 'info',
  modules: {
    toolbar: '#editProduct #in_description_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите описание...',
  theme: 'snow'
};

  var options_information = {
  debug: 'info',
  modules: {
    toolbar: '#editProduct #in_information_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите информацию о товаре...',
  theme: 'snow'
};

  var options_system_requirements = {
  debug: 'info',
  modules: {
    toolbar: '#editProduct #in_system_requirements_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите информацию о системных требованиях',
  theme: 'snow'
};


  var options_description = {
  debug: 'info',
  modules: {
    toolbar: '#addProduct #in_description_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите описание...',
  theme: 'snow'
};

  var options_information = {
  debug: 'info',
  modules: {
    toolbar: '#addProduct #in_information_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите информацию о товаре...',
  theme: 'snow'
};

  var options_system_requirements = {
  debug: 'info',
  modules: {
    toolbar: '#addProduct #in_system_requirements_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите информацию о системных требованиях',
  theme: 'snow'
};


var options_text_news_add = {
  debug: 'info',
  modules: {
    toolbar: '#addNews #in_text_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите описание..',
  theme: 'snow'
};


var options_text_news_edit = {
  debug: 'info',
  modules: {
    toolbar: '#editNews #in_text_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'strike' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите описание..',
  theme: 'snow'
};


var options_text_pages_add = {
  debug: 'info',
  modules: {
    toolbar: '#addPage #in_text_toolbar',
    'syntax': true,
    'toolbar': [
      [ 'bold', 'italic', 'underline', 'button' ],
      [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
      [{ 'list': 'ordered' }, { 'list': 'bullet'}],
      [ { 'align': [] }],
      [ 'link', 'image', 'video' ],
    ],
  },
  placeholder: 'Напишите описание..',
  theme: 'snow'
};


var options_text_pages_edit = {
  debug: 'info',
  modules: {
    toolbar: '#editPage #in_text_toolbar',
    'syntax': true,
        'toolbar': [
          [ 'bold', 'italic', 'underline', 'button' ],
          [{ 'header': '1' }, { 'header': '2' }, 'blockquote' ],
          [{ 'list': 'ordered' }, { 'list': 'bullet'}],
          [ { 'align': [] }],
          [ 'link', 'image', 'video' ],
        ],
  },
  placeholder: 'Напишите описание..',
  theme: 'snow'
};




if(addr_page == 'admin/goods'){
new Quill('#editProduct #in_description', options_description);
new Quill('#editProduct #in_information', options_information);
new Quill('#editProduct #in_system_requirements', options_system_requirements);
new Quill('#addProduct #in_description', options_description);
new Quill('#addProduct #in_information', options_information);
new Quill('#addProduct #in_system_requirements', options_system_requirements);
}

if(addr_page == 'admin/categories'){
new Quill('#addCat #in_text', options_description);
new Quill('#editCat #in_text', options_description);
}

if(addr_page == 'admin/news'){
var add_news = new Quill('#addNews #in_text', options_text_news_edit);
var edit_news = new Quill('#editNews #in_text', options_text_news_add);
}

if(addr_page == 'admin/pages'){
var add_page = new Quill('#addPage #in_text', options_text_pages_add);
var edit_page = new Quill('#editPage #in_text', options_text_pages_edit);

var toolbar_add_news = add_news.getModule('toolbar');
toolbar_add_news.addHandler('button', function() {
  console.log('button')
});

$( "#addNews .ql-button" ).click(function() {
  var range = add_news.getSelection();
    add_news.insertText(range.index, "[NAME|LINK]");
});


var toolbar_edit_news = edit_news.getModule('toolbar');
toolbar_edit_news.addHandler('button', function() {
  console.log('button')
});

$( "#editNews .ql-button" ).click(function() {
  var range = edit_news.getSelection();
    edit_news.insertText(range.index, "[NAME|LINK]");
});

var toolbar_edit_page = edit_page.getModule('toolbar');
toolbar_edit_page.addHandler('button', function() {
  console.log('button')
});

$( "#editPage .ql-button" ).click(function() {
  var range = edit_page.getSelection();
    edit_page.insertText(range.index, "[NAME|LINK]");
});


}

function changeAccountPassword() {

    let old_password = $('#settingsAccount #old_password').val();
    let new_password = $('#settingsAccount #new_password').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/accounts/password/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            old_password: old_password,
            new_password: new_password,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#settingsAccount').modal('hide');
                messageSystem(true,"Пароль изменен",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


function changeAccountLogin() {

    let new_username = $('#settingsAccount #new_username').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/accounts/username/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            new_username: new_username,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#settingsAccount').modal('hide');
                $('#settingsAccount #username').val('');
                messageSystem(true,"Логин изменен",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


function changeContacts() {
    var contacts = [];
    $(document.querySelectorAll('#settingsContacts #title')).each(function(index, e){
      var link = document.querySelectorAll('#settingsContacts #link')[index].value;
      if(e.value != '' && link != ''){
      contacts[index] = {title: e.value, link: link};
      }
    });

    $.ajax({
        type: "POST",
        url: api_url+'/api/contacts/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            contacts: contacts,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#settingsContacts').modal('hide');
                messageSystem(true,"Изменения сохранены",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });
}


function changeSocials() {

    let link_fb = $('#settingsSocials #link_fb').val();
    let link_ig = $('#settingsSocials #link_ig').val();
    let link_vk = $('#settingsSocials #link_vk').val();
    let link_tw = $('#settingsSocials #link_tw').val();
    let link_tg = $('#settingsSocials #link_tg').val();
    let link_yt = $('#settingsSocials #link_yt').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/socials/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            link_fb: link_fb,
            link_ig: link_ig,
            link_vk: link_vk,
            link_tw: link_tw,
            link_tg: link_tg,
            link_yt: link_yt,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#settingsSocials').modal('hide');
                messageSystem(true,"Изменения сохранены",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

function changeNotify(type) {

    let bot_token = $('#settingsNotify #bot_token').val();
    let user_id = $('#settingsNotify #user_id').val();

    $.ajax({
        type: "POST",
        url: api_url+'/api/notifications/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            bot_token: bot_token,
            user_id: user_id,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#settingsNotify').modal('hide');
                messageSystem(true,"Изменения сохранены",3000);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}


function logoutMember() {

    $.ajax({
        type: "POST",
        url: api_url+'/api/accounts/logout',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            
            if(data.status == true){
                eraseCookie('session_key');
                location.href = '/';
            } else if(data.status == false){
                alert(data.description);
            }
        }

    });

}

var modalSettingsNotify = document.getElementById('settingsNotify')
modalSettingsNotify.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');


  $.ajax({
        type: "POST",
        url: api_url+'/api/notifications/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#settingsNotify #bot_token').val(data.result.bot_token);
                $('#settingsNotify #user_id').val(data.result.user_id);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})



var modalSettingsSocials = document.getElementById('settingsSocials')
modalSettingsSocials.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');


  $.ajax({
        type: "POST",
        url: api_url+'/api/socials/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#settingsSocials #link_fb').val(data.result.link_fb);
                $('#settingsSocials #link_ig').val(data.result.link_ig);
                $('#settingsSocials #link_vk').val(data.result.link_vk);
                $('#settingsSocials #link_tw').val(data.result.link_tw);
                $('#settingsSocials #link_tg').val(data.result.link_tg);
                $('#settingsSocials #link_yt').val(data.result.link_yt);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})


var modalSettingsContacts = document.getElementById('settingsContacts')
modalSettingsContacts.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');


  $.ajax({
        type: "POST",
        url: api_url+'/api/contacts/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){

                $('#settingsContacts #block_contact_one').remove();
                
                var blocks_contacts = '';

                  $(data.result).each(function(index, e){

                    blocks_contacts += '<div class="row" id="block_contact_item" data-id="'+index+'"><div class="col-6"><div class="mb-3"><input type="text" class="form-control" id="title" value="'+e.title+'" placeholder="Название"></div></div><div class="col-5"><div class="mb-3"><input type="text" class="form-control" id="link" value="'+e.link+'" placeholder="Ссылка"></div></div><div class="col-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;" onclick="deleteBlockContact('+index+',\'settingsContacts\');return false;"></i></div></div>'; 

                  });

                  var count_tf = data.result.length;

                  $('#settingsContacts #btn-add-contact').attr('onclick','addBlockContact('+count_tf+',\'settingsContacts\')');
                  $('#settingsContacts #blocks_contacts').html(blocks_contacts);

            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})


var modalSettingsAccount = document.getElementById('settingsAccount')
modalSettingsAccount.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-bs-id');

  $.ajax({
        type: "POST",
        url: api_url+'/api/accounts/info',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $('#settingsAccount #username').val(data.result.username);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

})


function getShortname(modal,id) {

    let title = $(modal+' '+id).val();
    
    $.ajax({
        type: "POST",
        url: api_url+'/api/check/shortname',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            title: title,
            session_key: getCookie('session_key')
        }),
        async: false,
        success: function(data) {
            if(data.status == true){
                $(modal+' #in_shortname').val(data.result);
            } else if(data.status == false){
                messageSystem(false,data.description,3000);
            }
        }

    });

}

function addBlockContact(num,id) {
    var html = '<div class="row" id="block_contact_item" data-id="'+num+'"><div class="col-6"><div class="mb-3"><input type="text" class="form-control" id="title" placeholder="Название"></div></div><div class="col-5"><div class="mb-3"><input type="text" class="form-control" id="link" placeholder="Ссылка"></div></div><div class="col-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;" onclick="deleteBlockContact('+num+',\''+id+'\');return false;"></i></div></div>';
  num++
    $('#'+id+' #blocks_contacts').append(html);
    $('#'+id+' #btn-add-contact').attr('onclick','addBlockContact('+num+',\''+id+'\')');
}

function deleteBlockContact(num,id) {
  $('#'+id+' #block_contact_item[data-id="'+num+'"]').remove();
}

function getCookie(name) {
  let matches = document.cookie.match(new RegExp(
    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
  ));
  return matches ? decodeURIComponent(matches[1]) : undefined;
}

function createCookie(name,value,days) {
    var expires = "";
    if (days) { 
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        expires = ";expires=" +date.toGMTString();
    }
    document.cookie = name + "=" + value + expires + ";path=/";
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}

