let api_url = window.location.origin + '/api';
let api_url_cdn = 'https://fnrus.com/api';
let path = window.location.pathname;
let path_a = path.split('/')[1];
let path_b = path.split('/')[2];
let path_c = path.split('/')[3];
let intervalId;

$(document).ready(function() {
    userInfo(function(data) {
        if (data.ok === true && data.result.role_id !== 0) {
            $('#u_username').val(data.result.username);
            $('#navbar_username').text(data.result.username);
            loadSidebar();
            // shopInfo(); // скрыто — бот временно не нужен
            checkRole();
            setTimeout(function(){
                getStatsCounter();
                setInterval(function() {
                    getStatsCounter();
                }, 5000);
            }, 50);
            if (path_b === 'stats') {
                $('.nav-item[data-id="analytics"]').addClass('active');
                getStats(1,0);
                // top_sales();
                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/orders/sales/top',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    searching: false,
                    paginate: false,
                    ordering: false,
                    paging: false,
                    info: false,
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по заказам",
                        emptyTable: "В магазине нет ни одного заказа",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'sales',
                            name: 'sales',
                        },
                        {
                            data: 'sum',
                            name: 'sum'
                        },
                        {
                            data: 'count_views',
                            name: 'count_views'
                        },

                    ]
                });
            }
            if (path_b === 'orders') {

                let method_type = localStorage.getItem('orders_method_payment');  
                selectPaymentsAll().done(function(data) {
                    if(data.ok === true){
                        var select_html = '<option value="">Способ оплаты: Любая</option>';
                  
                        $(data.result).each(function(index, e) {
                            var selected = '';
                            if(method_type == e.type){
                                selected = ' selected';
                            }
                            select_html += '<option value="' + e.type + '"'+selected+'>Способ оплаты: ' + e.title + '</option>';
                        });
                        $('#input-method-payment').html(select_html);
                    }
                });

                $('.nav-item[data-id="orders"]').addClass('active');

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/orders/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        "data": function(d) {
                            var status = null;
                            if(localStorage.getItem(path_b + '_status') !== undefined){
                                status = localStorage.getItem(path_b + '_status');
                            }
                            var method_payment = null;
                            if(localStorage.getItem(path_b + '_method_payment') !== undefined){
                                method_payment = localStorage.getItem(path_b + '_method_payment');
                            }
                            d.status = status;
                            d.method_payment = method_payment;
                        },
                        async: true,
                    },
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по заказам",
                        emptyTable: "В магазине нет ни одного заказа",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [
                        {
                            data: 'id',
                            name: 'id'
                        },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'buyer',
                            name: 'buyer'
                        },
                        {
                            data: 'block_pay',
                            name: 'block_pay',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'amount',
                            name: 'amount'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at',
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'block_link',
                            name: 'block_link',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        }
                    ]
                });
            }
            if (path_b === 'members') {
                $('.nav-item[data-id="members"]').addClass('active');
                let role_id_val = localStorage.getItem(path_b + "_role_id");

                selectRolesAll(role_id_val, function (data) {
                    if(data.ok === true){
                        var select_html = '<option value="">Тип: Любой</option><option value="0">Тип: Пользователь</option>';
                  
                        $(data.result).each(function(index, e) {
                            var selected = '';
                            if(role_id_val == e.id){
                                selected = ' selected';
                            }
                            select_html += '<option value="' + e.id + '"'+selected+'>Тип: ' + e.title + '</option>';
                        });
                        $('#input-role-id').html(select_html);
                    }
                });

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/members/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        "data": function(d) {
                            var role_id = null;
                            if(localStorage.getItem(path_b + '_role_id') !== undefined){
                                role_id = localStorage.getItem(path_b + '_role_id');
                            }
                            d.role_id = role_id;
                        },
                        async: true,
                    },
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по пользователям",
                        emptyTable: "В магазине нет ни одного пользователя",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'member',
                            name: 'member'
                        },
                        {
                            data: 'balance_main',
                            name: 'balance_main'
                        },
                        {
                            data: 'count_ref',
                            name: 'count_ref',
                            orderable: false,
                        },
                        {
                            data: 'ref_percent',
                            name: 'ref_percent'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at'
                        },

                        {
                            data: 'block_edit',
                            name: 'block_edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_ban',
                            name: 'block_ban',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        }
                    ]
                });


            }
            if (path_b === 'roles') {
                $('.nav-item[data-id="roles"]').addClass('active');

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/roles/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по ролям",
                        emptyTable: "В магазине нет ни одной роли",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'block_edit',
                            name: 'block_edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        }
                    ]
                });
            }
            if (path_b === 'links') {
                $('.nav-item[data-id="ads"]').addClass('active');


                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/links/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по ссылкам",
                        emptyTable: "В магазине нет ни одной ссылки",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'code',
                            name: 'code'
                        },
                        {
                            data: 'visits_total',
                            name: 'visits_total'
                        },
                        {
                            data: 'visits_unique',
                            name: 'visits_unique'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at'
                        },
                        {
                            data: 'block_copy',
                            name: 'block_copy',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        }
                    ]
                });
            }
            if (path_b === 'files') {
                $('.nav-item[data-id="files"]').addClass('active');

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/attachments/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по файлам",
                        emptyTable: "Нет ни одного файла",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'id',
                            name: 'id'
                        },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'ext',
                            name: 'ext'
                        },
                        {
                            data: 'size',
                            name: 'size'
                        },
                        {
                            data: 'uploaded_at',
                            name: 'uploaded_at'
                        },
                        {
                            data: 'block_link_share',
                            name: 'block_link_share',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        }
                    ]
                });
            }
            if (path_b === 'settings') {
                $('.nav-item[data-id="settings"]').addClass('active');
                $('.nav-item[data-id="settings"] a').removeClass('collapsed');
                $('.nav-item[data-id="settings"] .collapse').addClass('show');
                $('.nav-item[data-id="settings"] .collapse-item[data-id="' + path_c + '"]').addClass('active');
            }
            if (path_b === 'settings' && path_c === 'payments') {
                paymentSystems();
            }
            if (path_b === 'settings' && path_c === 'constructor') {

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#text_join_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст вступления..',
                    theme: 'snow'
                };
                new Quill('#text_join', options_text_message);

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#text_after_payment_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                };
                new Quill('#text_after_payment', options_text_message);

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#text_agreement_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст соглашения..',
                    theme: 'snow'
                };
                new Quill('#text_agreement', options_text_message);

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#addButton #text_message_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                };
                new Quill('#addButton #text_message', options_text_message);

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#editButton #text_message_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                };
                new Quill('#editButton #text_message', options_text_message);

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#text_welcome_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                };
                new Quill('#text_welcome', options_text_message);

                var fixHelper = function(e, ui) {
                    ui.children().each(function() {
                        $(this).width($(this).width());
                    });
                    return ui;
                };

                $('#channels').sortable({
                    handle: '.handle',
                    helper: fixHelper,
                    stop: function() {
                        $.ajax({
                            url: api_url + '/channels/sub/sort',
                            method: 'POST',
                            beforeSend: function(xhr, settings) {
                                setAuthorization(xhr)
                            },
                            data: $('#channels input').serialize(),
                            success: function(data) {
                                if (data.ok === true) {
                                    messageSystem(true, data.description, 2000);
                                } else if (data.ok === false) {
                                    messageSystem(false, data.description, 3000);
                                }
                            }
                        });
                    }
                });

                $('#buttons').sortable({
                    handle: '.handle',
                    helper: fixHelper,
                    stop: function() {
                        $.ajax({
                            url: api_url + '/buttons/sort',
                            method: 'POST',
                            beforeSend: function(xhr, settings) {
                                setAuthorization(xhr)
                            },
                            data: $('#buttons input').serialize(),
                            success: function(data) {
                                if (data.ok === true) {
                                    messageSystem(true, data.description, 2000);
                                } else if (data.ok === false) {
                                    messageSystem(false, data.description, 3000);
                                }
                            }
                        });
                    }
                });

                constructor_info();

            }
            if (path_b === 'settings' && path_c === 'site-buttons') {
                loadSiteButtons();
            }
            if (path_b === 'settings' && path_c === 'policy') {
                console.log('[PageInit] policy page detected, calling initPolicyEditors');
                initPolicyEditors();
                loadPolicy();
            }
            if (path_b === 'settings' && path_c === 'about') {
                loadAboutItems();
            }
            if (path_b === 'settings' && path_c === 'delivery-text') {
                console.log('[PageInit] delivery-text page detected, calling initDeliveryEditors');
                initDeliveryEditors();
                loadDeliveryText();
            }
            if (path_b === 'settings' && path_c === 'support') {
                loadSupport();
            }
            if (path_b === 'faq'){
                $('.nav-item[data-id="faq"]').addClass('active');
                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/faq/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по вопросам",
                        emptyTable: "В магазине нет ни одного вопроса",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [
                        {
                            data: 'icon',
                            name: 'icon',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'question',
                            name: 'question'
                        },
                        {
                            data: 'instruction',
                            name: 'instruction'
                        },
                        {
                            data: 'block_move',
                            name: 'block_move',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_visibility',
                            name: 'block_visibility',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_edit',
                            name: 'block_edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                    ]
                });

                if (typeof Quill !== 'undefined') {
                    var Size = Quill.import('attributors/class/size');
                    Size.whitelist = ['14px', '16px', '18px', '20px', '24px'];
                    Quill.register(Size, true);
                }

                var faqToolbarOptions = [
                    [{ 'header': [1, 2, 3, false] }],
                    [{ 'size': ['14px', '16px', false, '18px', '20px', '24px'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'indent': '-1' }, { 'indent': '+1' }],
                    ['blockquote'],
                    ['link'],
                    ['clean']
                ];

                new Quill('#createFaq #text_answer', {
                    modules: { toolbar: faqToolbarOptions },
                    placeholder: 'Напишите ответ на вопрос..',
                    theme: 'snow'
                });

                new Quill('#changeFaq #text_answer', {
                    modules: { toolbar: faqToolbarOptions },
                    placeholder: 'Напишите ответ на вопрос..',
                    theme: 'snow'
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
                            url: api_url + '/faq/sort',
                            method: 'POST',
                            beforeSend: function(xhr, settings) {
                                setAuthorization(xhr)
                            },
                            data: $('#datatable input').serialize(),
                            success: function(data) {
                                if (data.ok === true) {
                                    messageSystem(true, data.description, 2000);
                                } else if (data.ok === false) {
                                    messageSystem(false, data.description, 3000);
                                }
                            }
                        });
                    }
                });
            }
            if (path_b === 'reviews'){
                $('.nav-item[data-id="reviews"]').addClass('active');
                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/reviews/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по отзывам",
                        emptyTable: "Нет ни одного отзыва",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [
                        {
                            data: 'avatar',
                            name: 'avatar',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'author',
                            name: 'author'
                        },
                        {
                            data: 'text',
                            name: 'text'
                        },
                        {
                            data: 'link',
                            name: 'link',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'block_edit',
                            name: 'block_edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                    ]
                });
            }
            if (path_b === 'promocodes'){

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/coupons/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по купонам",
                        emptyTable: "В магазине нет ни одного купона",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'goods',
                            name: 'goods'
                        },
                        {
                            data: 'sale',
                            name: 'sale'
                        },
                        {
                            data: 'code',
                            name: 'code'
                        },
                        // {
                        //     data: 'count_expired',
                        //     name: 'count_expired'
                        // },
                        {
                            data: 'count_uses_min',
                            name: 'count_uses_min'
                        },
                        {
                            data: 'count_uses_max',
                            name: 'count_uses_max'
                        },
                        {
                            data: 'block_copy',
                            name: 'block_copy',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_edit',
                            name: 'block_edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        }
                    ]
                });
            }
            if (path_b === 'products' || path_b === 'categories' || path_b === 'materials' || path_b === 'promocodes' || path_b === 'exports') {
                $('.nav-item[data-id="products"]').addClass('active');
                $('.nav-item[data-id="products"] a').removeClass('collapsed');
                $('.nav-item[data-id="products"] .collapse').addClass('show');
                $('.nav-item[data-id="products"] .collapse-item[data-id="' + path_b + '"]').addClass('active');
            }
            if (path_b === 'materials'){
                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/materials/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        "data": function(d) {
                            var filters = null;
                            if(localStorage.getItem(path_b + '_filters') !== undefined){
                                filters = localStorage.getItem(path_b + '_filters');
                            }
                            d.filters = filters;
                        },
                        async: true,
                    },
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по материалам",
                        emptyTable: "В магазине нет ни одного материала",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'tariff',
                            name: 'tariff'
                        },
                        {
                            data: 'body',
                            name: 'body',
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        }
                    ]
                });
            }
            if (path_b === 'senders') {

                const currentDate = new Date();
                const month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
                const day = currentDate.getDate().toString().padStart(2, '0');
                const hours = currentDate.getHours().toString().padStart(2, '0');
                const minutes = currentDate.getMinutes().toString().padStart(2, '0');

                document.querySelector('#createSender #block_message #date_month').value = month;
                document.querySelector('#createSender #block_message #date_day').value = day;
                document.querySelector('#createSender #block_message #date_hours').value = hours;
                document.querySelector('#createSender #block_message #date_minutes').value = minutes;

                $('#id_1').datetimepicker({
                    "allowInputToggle": true,
                    "showClose": true,
                    "showClear": true,
                    "showTodayButton": true,
                    "format": "MM/DD/YYYY HH:mm:ss",
                });
                $('.nav-item[data-id="senders"]').addClass('active');

                // var today = new Date();
                // var d = today.getUTCDate() + 1;
                // var m = today.getUTCMonth() + 1;
                // var y = today.getFullYear();
                // var h = today.getUTCHours() + 3;
                // var i = today.getUTCMinutes() + 5;
                //
                // if(h == 24){var h = 0;}
                //
                // document.getElementById('date_day').value = d;
                // document.getElementById('date_month').value = m;
                // document.getElementById('date_hours').value = h;
                // document.getElementById('date_minutes').value = i;

                $('#type_0_1').hide();
                $('#type_0_2').hide();
                $('#type_1').hide();
                $('#date_send').hide();
                $('#block_name_button').hide();
                $('#block_link_button').hide();
                $('#block_page_button').hide();

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/senders/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        "data": function(d) {
                            var status = null;
                            if(localStorage.getItem(path_b + '_status') !== undefined){
                                status = localStorage.getItem(path_b + '_status');
                            }
                            d.status = status;
                        },
                        async: true,
                    },
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: ['_all'],
                            className: 'mdc-data-table__cell',
                        },
                    ],
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по рассылкам",
                        emptyTable: "В магазине нет ни одной рассылки",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'type',
                            name: 'type'
                        },
                        {
                            data: 'progress',
                            name: 'progress'
                        },
                        {
                            data: 'started_at',
                            name: 'started_at'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'block_edit',
                            name: 'block_edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'block_delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        }
                    ]
                });
            }
            if (path_b === 'exports') {
                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/mexports/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по истории",
                        emptyTable: "В магазине нет ни одной выгрузки",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'tid',
                            name: 'tid'
                        },
                        {
                            data: 'count',
                            name: 'count'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at'
                        },
                        {
                            data: 'block_export',
                            name: 'export',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                    ]
                });
            }
            if (path_b === 'categories' && path_c === 'edit') {
                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#edit_category #text_message_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст описания..',
                    theme: 'snow'
                };
                new Quill('#edit_category #text_message', options_text_message);
            }
            if (path_b === 'withdrawals' && path_c === undefined) {

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/withdrawals/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    processing: false,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по выводам",
                        emptyTable: "В магазине нет ни одного вывода",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [
                        {
                            data: 'icon',
                            name: 'icon',
                            orderable: false,
                            searchable: false,
                        },

                        {
                            data: 'member',
                            name: 'member'
                        },

                        {
                            data: 'sum',
                            name: 'sum'
                        },
                        {
                            data: 'method',
                            name: 'method'
                        },
                        {
                            data: 'card_number',
                            name: 'card_number'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'block_done',
                            name: 'block_done',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                    ]
                });

            }
            if (path_b === 'tickets' && path_c === undefined) {
   
                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/tickets/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        "data": function(d) {
                            var status = null;
                            if(localStorage.getItem(path_b + '_status') !== undefined){
                                status = localStorage.getItem(path_b + '_status');
                            }
                
                            d.status = status;
                        },
                        async: true,
                    },
                    autoWidth: false,
                    processing: false,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по тикетам",
                        emptyTable: "В магазине нет ни одного тикета",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [
                        {
                            data: 'icon',
                            name: 'icon',
                            orderable: false,
                            searchable: false,
                        },

                        {
                            data: 'user',
                            name: 'user'
                        },
                        {
                            data: 'subject',
                            name: 'subject'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'last_answer_at',
                            name: 'last_answer_at'
                        },
                        {
                            data: 'block_dialog',
                            name: 'block_dialog',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_blocked',
                            name: 'block_blocked',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                    ]
                });

                var fixHelper = function(e, ui) {
                    var $clone = ui.clone();
                    $clone.css("width", ui.outerWidth()); // Set the width of the clone to match the original element
                    return $clone;
                };

                $('#chatTicket #templates').sortable({
                    items: 'div',
                    handle: '.handle',
                    helper: fixHelper,
                    stop: function() {
                        $.ajax({
                            url: api_url + '/tickets/templates/sort',
                            method: 'POST',
                            beforeSend: function(xhr, settings) {
                                setAuthorization(xhr)
                            },
                            data: $('#chatTicket #templates input').serialize(),
                            success: function(data) {
                                if (data.ok === true) {
                                    messageSystem(true, data.description, 2000);
                                } else if (data.ok === false) {
                                    messageSystem(false, data.description, 3000);
                                }
                            }
                        });
                    }
                });

            }
            if (path_b === 'instructions' && path_c === undefined){
                var instrToolbarOptions = [
                    [{ 'header': [1, 2, 3, false] }],
                    [{ 'size': ['14px', '16px', false, '18px', '20px', '24px'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'indent': '-1' }, { 'indent': '+1' }],
                    ['blockquote'],
                    ['link', 'image', 'video'],
                    ['clean']
                ];

                new Quill('#createInstruction #text_instruction', {
                    modules: {
                        toolbar: instrToolbarOptions,
                        imageResize: { displaySize: true }
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                });

                new Quill('#changeInstruction #text_instruction', {
                    modules: {
                        toolbar: instrToolbarOptions,
                        imageResize: { displaySize: true }
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                });

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/instructions/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    processing: true,
                    serverSide: true,
                    searching: true,
                    order: [],
                    language: {
                        searchPlaceholder: "Поиск по инструкциям",
                        emptyTable: "В магазине нет ни одной инструкции",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'views',
                            name: 'views'
                        },
                        {
                            data: 'link_share',
                            name: 'link_share',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_edit',
                            name: 'edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                    ]
                });

                $('#cp_image_upload').change(function() {
                    $(this).simpleUpload(api_url + "/attachments/image/upload", {

                        allowedExts: ["jpg", "jpeg", "png", "gif"],
                        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        start: function(file) {
                            //upload started
                        },
                        progress: function(progress) {
                            //received progress
                        },
                        success: function(data) {
                            $('#createPage #block_upload_image').attr('data-attach', data.result.id);
                            $('#createPage #block_upload_image').html('<img src="/' + data.result.id + '" />');
                            $('#createPage #block_upload_image').removeClass('d-none');
                            $('#createPage #btn_upload_image').addClass('d-none');
                            $('#createPage #text_image_uploaded').removeClass('d-none');
                            $('#createPage #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#createPage\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
                            $('#createPage #cp_has_spoiler').prop("disabled", false);
                            messageSystem(true, data.description, 2000);

                        },
                        error: function(error) {
                            //upload failed
                        }
                    });
                });

            }
            if (path_b === 'members' && path_c === undefined) {
                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#sendMessage #text_message_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                };
                new Quill('#sendMessage #text_message', options_text_message);
            }
            if (path_b === 'categories' && path_c === undefined) {

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/categories/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        async: true,
                    },
                    autoWidth: false,
                    processing: true,
                    serverSide: true,
                    stateSave: true,
                    searching: true,
                    order: [],
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Все"]],
                    language: {
                        searchPlaceholder: "Поиск по категориям",
                        emptyTable: "В магазине нет ни одной категории",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "_MENU_",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'category',
                            name: 'category'
                        },
                        // { data: 'type', name: 'type' },
                        {
                            data: 'count_products',
                            name: 'count_products'
                        },
                        {
                            data: 'count_views',
                            name: 'count_views'
                        },
                        {
                            data: 'block_move',
                            name: 'block_move',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_link_share',
                            name: 'link_share',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_edit',
                            name: 'edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                    ]
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
                            url: api_url + '/categories/sort',
                            method: 'POST',
                            beforeSend: function(xhr, settings) {
                                setAuthorization(xhr)
                            },
                            data: $('#datatable input').serialize(),
                            success: function(data) {
                                if (data.ok === true) {
                                    messageSystem(true, data.description, 2000);
                                } else if (data.ok === false) {
                                    messageSystem(false, data.description, 3000);
                                }
                            }
                        });
                    }
                });

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#addCategory #text_message_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст описания..',
                    theme: 'snow'
                };
                new Quill('#addCategory #text_message', options_text_message);

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#editCategory #text_message_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст описания..',
                    theme: 'snow'
                };
                new Quill('#editCategory #text_message', options_text_message);
            }
            if (path_b === 'statuses' && path_c === undefined) {
                statusesCheats();
            }
            if (path_b === 'products' && path_c === undefined) {
                
                let cat_id = localStorage.getItem('products_cat_id');  

                selectCatsAll(cat_id, 0).done(function (data){
                    if(data.ok === true){
                        var select_html = '<option value="">Категория: Любая</option>';

                        $(data.result).each(function(index, e) {
                            var selected = '';
                            if(cat_id == e.id){
                                selected = ' selected';
                            }
                            select_html += '<option value="' + e.id + '"'+selected+'>Категория: ' + e.title + '</option>';
                        });

                        $('#input-cat-id').html(select_html);
                    }   
                });

                $('#datatable').DataTable({
                    ajax: {
                        url: api_url + '/products/all',
                        dataType: 'json',
                        type: "GET",
                        beforeSend: function(xhr, settings) {
                            setAuthorization(xhr)
                        },
                        "data": function(d) {
                            var cat_id = null;
                            if(localStorage.getItem(path_b + '_cat_id') !== undefined){
                                cat_id = localStorage.getItem(path_b + '_cat_id');
                            }
                            d.cat_id = cat_id;
                        },
                        async: true,
                    },
                    autoWidth: false,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    searching: true,
                    order: [],
                    "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Все"]],
                    language: {
                        searchPlaceholder: "Поиск по товарам",
                        emptyTable: "В магазине нет ни одного товара",
                        info: "Страница _PAGE_ из _PAGES_",
                        infoEmpty: "Показано 0 из 0",
                        lengthMenu: "_MENU_",
                        processing: "Загрузка..",
                        zeroRecords: "По запросу не найдено ни одного результата",
                        search: "",
                        paginate: {
                            first: "Первая",
                            last: "Последняя",
                            next: "Следующая",
                            previous: "Предыдущая"
                        }
                    },
                    columns: [{
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                    },
                        {
                            data: 'title',
                            name: 'title'
                        },
                        {
                            data: 'cid',
                            name: 'cid'
                        },
                        {
                            data: 'price',
                            name: 'price'
                        },
                        {
                            data: 'count_sales',
                            name: 'count_sales'
                        },
                        {
                            data: 'count_all',
                            name: 'count_all'
                        },
                        {
                            data: 'count_views',
                            name: 'count_views'
                        },
                        {
                            data: 'block_move',
                            name: 'block_move',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_add_material',
                            name: 'add_material',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_export',
                            name: 'export',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },

                        {
                            data: 'block_edit',
                            name: 'edit',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                        {
                            data: 'block_delete',
                            name: 'delete',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                return data;
                            }
                        },
                    ]
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
                            url: api_url + '/products/sort',
                            method: 'POST',
                            beforeSend: function(xhr, settings) {
                                setAuthorization(xhr)
                            },
                            data: $('#datatable input').serialize(),
                            success: function(data) {
                                if (data.ok === true) {
                                    messageSystem(true, data.description, 2000);
                                } else if (data.ok === false) {
                                    messageSystem(false, data.description, 3000);
                                }
                            }
                        });
                    }
                });

                var options_text_message_ap = {
                    debug: 'info',
                    modules: {
                        toolbar: '#addProduct #text_message_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст описания..',
                    theme: 'snow'
                };

                var options_text_message_ep = {
                    debug: 'info',
                    modules: {
                        toolbar: '#editProduct #text_message_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст описания..',
                    theme: 'snow'
                };

                createQuillIfVisible('#addProduct #text_message', options_text_message_ap);
                createQuillIfVisible('#editProduct #text_message', options_text_message_ep);

                if (typeof Quill !== 'undefined') {
                    try {
                        var SizeDesc = Quill.import('attributors/class/size');
                        SizeDesc.whitelist = ['14px', '16px', '18px', '20px', '24px'];
                        Quill.register(SizeDesc, true);
                    } catch(e) { console.warn('Quill size register failed:', e); }
                }

                var descToolbarOptions = [
                    [{ 'header': [1, 2, 3, false] }],
                    [{ 'size': ['14px', '16px', false, '18px', '20px', '24px'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['blockquote'], ['link'], ['clean']
                ];

                window.__initDescQuill = function(modalSel, instanceKey) {
                    var el = document.querySelector(modalSel + ' #description');
                    if (!el) { console.error('description el not found:', modalSel); return; }
                    if (window[instanceKey]) { return; }
                    if (el.classList.contains('ql-container')) { return; }
                    try {
                        window[instanceKey] = new Quill(el, {
                            modules: { toolbar: descToolbarOptions },
                            placeholder: 'Напишите описание..',
                            theme: 'snow'
                        });
                        console.log('Quill init OK for', modalSel, instanceKey);
                    } catch(e) {
                        console.error('Quill init FAILED for', modalSel, e);
                    }
                };

                $('#addProduct').on('shown.bs.modal', function() {
                    window.__initDescQuill('#addProduct', 'descAddQuill');
                });
                $('#editProduct').on('show.bs.modal', function() {
                    window.__initDescQuill('#editProduct', 'descEditQuill');
                });

            }
            if (path_b === 'senders' && path_c === undefined) {
                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#createSender #text_sender_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                };

                new Quill('#createSender #text_sender', options_text_message);

                var options_text_message = {
                    debug: 'info',
                    modules: {
                        toolbar: '#editSender #text_sender_toolbar',
                        'syntax': true,
                        'toolbar': [
                            ['bold', 'italic', 'underline', 'link']
                        ]
                    },
                    placeholder: 'Напишите текст сообщения..',
                    theme: 'snow'
                };
                new Quill('#editSender #text_sender', options_text_message);
            }
            var preloader = document.getElementById('page-top');
            preloader.style.display = 'block';
        } else {
            location.href = '/';
        }
    });


$('#input-search').on('keyup change', function() {
    $('#datatable').DataTable().search(this.value).draw();
});

$('#input-length').on('change', function() {
    var length = $(this).val();
    $('#datatable').DataTable().page.len(length).draw();
});

$('#input-status').on('change', function() {
    var status = $(this).val();
    localStorage.setItem(path_b + '_status', status);
    setTimeout(function () {
        $('#datatable').DataTable().ajax.reload();
    }, 1000);
});

$('#input-filters').on('change', function() {
    var filters = $(this).val();
    localStorage.setItem(path_b + '_filters', filters);
    setTimeout(function () {
        $('#datatable').DataTable().ajax.reload();
    }, 1000);
});

$('#input-method-payment').on('change', function() {
    var status = $(this).val();
    localStorage.setItem(path_b + '_method_payment', status);
    setTimeout(function () {
        $('#datatable').DataTable().ajax.reload();
    }, 1000);
});

$('#input-role-id').on('change', function() {
    var role_id = $(this).val();
    localStorage.setItem(path_b + '_role_id', role_id);
    setTimeout(function () {
        $('#datatable').DataTable().ajax.reload();
    }, 1000);
});

$('#input-cat-id').on('change', function() {
    var cat_id = $(this).val();
    localStorage.setItem(path_b + '_cat_id', cat_id);
    setTimeout(function () {
        $('#datatable').DataTable().ajax.reload();
    }, 1000);
});

var params = localStorage.getItem("DataTables_datatable_" + location.pathname);
var parsedParams = params ? JSON.parse(params) : null;
let length_val = parsedParams ? parsedParams['length'] : null;
let search_val = parsedParams && parsedParams['search'] ? parsedParams['search']['search'] : null;
let status_val = localStorage.getItem(path_b + "_status");
let filters_val = localStorage.getItem(path_b + "_filters");

$('#input-length').val(length_val);
$('#input-search').val(search_val);
$('#input-status').val(status_val);
$('#input-filters').val(filters_val);
$('#input-method-payment').val(status_val);

});


function showType(modal_id){
    modal_id = '#'+modal_id;
    if($(modal_id + " #type").val() === '0'){
        $('#type_0_1').show();
        $('#type_0_2').show();
        $('#date_send').hide();
        $('#block_name_button').hide();
        $('#block_link_button').hide();
        $('#block_page_button').hide();
        $('#type_1').hide();
    }

    if($(modal_id + " #type").val() === '1'){
        $('#type_0_1').hide();
        $('#type_0_2').hide();
        $('#date_send').hide();
        $('#block_name_button').hide();
        $('#block_link_button').hide();
        $('#block_page_button').hide();
        $('#type_1').show();
    }
}

function typeButton(select, num, id){
    console.log(select.value);

    if(select.value === '0') {
        $('#' + id + ' #block_button_item[data-id="' + num + '"] #button_link').removeClass('d-none');
        $('#' + id + ' #block_button_item[data-id="' + num + '"] #button_action').addClass('d-none');
    }
    if(select.value === '1') {
        $('#' + id + ' #block_button_item[data-id="' + num + '"] #button_link').addClass('d-none');
        $('#' + id + ' #block_button_item[data-id="' + num + '"] #button_action').removeClass('d-none');
    }
}

function addBlockButton(num,id) {
    selectAll('products', '', function (data) {
        if (data.ok === true) {
            var products = data.result;

            var select_html = '';
            $(products).each(function(index, e) {
                select_html += '<option value="products/' + e.id + '/1">' + e.title + '</option>';
            });

            var html = '<div class="row" id="block_button_item" data-id="'+num+'"><div class="col-12"><select class="form-control mb-3" id="type_button" onchange="typeButton(this, '+num+', \''+id+'\');"><option value="0">Кастомная ссылка</option><option value="1">Ссылка на товар</option></select></div><div class="col-6"><div class="mb-3"><input type="text" class="form-control" id="button_title" placeholder="Название"></div></div><div class="col-5"><div class="mb-3"><select class="form-control mb-3 d-none" id="button_action">'+select_html+'</select><input type="text" class="form-control" id="button_link" placeholder="Ссылка"></div></div><div class="col-1"><i class="far fa-trash fa-xl text-danger" style="cursor:pointer;margin: 13px -7px;" onclick="deleteBlockButton('+num+',\''+id+'\');return false;"></i></div></div>';
            num++
            $('#'+id+' #blocks_buttons').append(html);
            $('#'+id+' #btn-add-button').attr('onclick','addBlockButton('+num+',\''+id+'\')');

        } else if (data.ok === false) {
            messageSystem(false, data.description, 3000);
        }
    });
}


function addBlockButtonCustom(id, num, text, url, cb) {
    var body = '';
    if(url !== undefined) {
        body = JSON.stringify({"num": num, "text": text, "url": url});
    } else {
        body = JSON.stringify({"num": num, "text": text, "cb": cb});
    }

    selectAll('products', body, function (data) {
        if (data.ok === true) {
            var products = data.result;
            var b = JSON.parse(body);

            console.log(b);

            var select_html = '';
            $(products).each(function(index, e) {
                var checked = '';
                if (b.cb === 'products/' + e.id + '/1'){
                    checked = ' selected';
                }
                select_html += '<option value="products/' + e.id + '/1"'+checked+'>' + e.title + '</option>';
            });

            var html = '';
            if(b.url) {
                html = '<div class="row" id="block_button_item" data-id="' + b.num + '"><div class="col-12"><select class="form-control mb-3" id="type_button" onchange="typeButton(this, ' + b.num + ', \'' + id + '\');"><option value="0">Кастомная ссылка</option><option value="1">Ссылка на товар</option></select></div><div class="col-6"><div class="mb-3"><input type="text" value="' + b.text + '" class="form-control" id="button_title" placeholder="Название"></div></div><div class="col-5"><div class="mb-3"><select class="form-control mb-3 d-none" id="button_action">' + select_html + '</select><input type="text" class="form-control" value="' + b.url + '" id="button_link" placeholder="Ссылка"></div></div><div class="col-1"><i class="far fa-trash fa-xl text-danger" style="cursor:pointer;margin: 13px -7px;" onclick="deleteBlockButton(' + b.num + ',\'' + id + '\');return false;"></i></div></div>';
            } else {
                html = '<div class="row" id="block_button_item" data-id="' + b.num + '"><div class="col-12"><select class="form-control mb-3" id="type_button" onchange="typeButton(this, ' + b.num + ', \'' + id + '\');"><option value="0">Кастомная ссылка</option><option value="1" selected>Ссылка на товар</option></select></div><div class="col-6"><div class="mb-3"><input type="text" value="' + b.text + '" class="form-control" id="button_title" placeholder="Название"></div></div><div class="col-5"><div class="mb-3"><select class="form-control mb-3" id="button_action">' + select_html + '</select><input type="text" class="form-control d-none" value="' + b.url + '" id="button_link" placeholder="Ссылка"></div></div><div class="col-1"><i class="far fa-trash fa-xl text-danger" style="cursor:pointer;margin: 13px -7px;" onclick="deleteBlockButton(' + b.num + ',\'' + id + '\');return false;"></i></div></div>';
            }
            $('#'+ id + ' #blocks_buttons').append(html);
            $('#'+ id + ' #btn-add-button').attr('onclick','addBlockButton('+num+',\''+id+'\')');

        } else if (data.ok === false) {
            messageSystem(false, data.description, 3000);
        }
    });
}


function deleteBlockButton(num,id) {
    $('#'+id+' #block_button_item[data-id="'+num+'"]').remove();
}


function showTime(selector, type) {
    var selector_two;

    if(type === 1){selector_two = 'block_message';}
    if(type === 2){selector_two = 'block_forward';}

    let modal_id = '#' + selector;
    if($(modal_id + " #"+selector_two+" #type_time").val() === '0'){
        $(modal_id + " #"+selector_two+" #started_at").addClass('d-none');
    }
    if($(modal_id + " #"+selector_two+" #type_time").val() === '1'){
        $(modal_id + " #"+selector_two+" #started_at").removeClass('d-none');
    }
}


function nextStep(old_id, new_id) {
    $('.card-header[data-id=' + old_id + ']').removeClass('d-block').addClass('d-none');
    $('.card-body[data-id=' + old_id + ']').removeClass('d-block').addClass('d-none');
    $('.card-header[data-id=' + new_id + ']').removeClass('d-none').addClass('d-block');
    $('.card-body[data-id=' + new_id + ']').removeClass('d-none').addClass('d-block');
}


function confirmWithdraw(id) {
    if (confirm("Вы уверены, что отправили средства по реквизитам пользователя?")) {
        $.ajax({
            type: "POST",
            url: api_url + '/withdrawals/' + id + '/confirm',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                setAuthorization(xhr)
            },
            async: true,
            success: function(data) {
                if (data.ok === true) {
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }
        });
    }
}

function setChannelSubActive() {
    $.ajax({
        type: "POST",
        url: api_url + '/channels/sub/settings/active',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function setTextActive(type) {
    $.ajax({
        type: "POST",
        url: api_url + '/texts/' + type + '/active',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function checkSaveChannel(type, id) {


    if(type === 'addChannel'){
        var is_active = 0;
        if ($('#' + type + ' #nc_is_active').is(":checked")) {
            is_active = 1;
        }

        var modal_id = '#'+type;
        var url = '/channels/sub/create';
        var data = {
            cid: $(modal_id + ' #channel_id').val(),
            title: $(modal_id + ' #channel_title').val(),
            link: $(modal_id + ' #channel_link').val(),
            is_active: is_active
        };
    }

    if(type === 'editChannel'){
        var is_active = 0;
        if ($('#' + type + ' #ec_is_active').is(":checked")) {
            is_active = 1;
        }

        var modal_id = '#'+type;
        var url = '/channels/sub/'+id+'/update';
        var data = {
            title: $(modal_id + ' #channel_title').val(),
            link: $(modal_id + ' #channel_link').val(),
            is_active: is_active
        };
    }

    $.ajax({
        type: "POST",
        url: api_url + url,
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(data),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                constructor_info();
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}


function saveButton(type, id) {

    if(type === 'addButton'){
        var modal_id = '#'+type;
        var url = '/buttons/create';
        var class_id = 'nb'
    }

    if(type === 'editButton'){
        var modal_id = '#'+type;
        var url = '/buttons/'+id+'/update';
        var class_id = 'eb'
    }

    var disable_web_page_preview = 0;
    if ($(modal_id+' #'+class_id+'_disable_web_page_preview').is(":checked")) {
        disable_web_page_preview = 1;
    }

    var has_spoiler = 0;
    if ($(modal_id+' #'+class_id+'_has_spoiler').is(":checked")) {
        has_spoiler = 1;
    }

    var buttons = [];

    $(modal_id + ' #block_buttons #block_button_item #button_title').each(function (index, e) {
        var button_link = $(modal_id + ' #block_buttons #block_button_item #button_link')[index].value;
        var button_action = $(modal_id + ' #block_buttons #block_button_item #button_action')[index].value;
        if (e.value != '' && button_link != '' && $($(modal_id + ' #block_buttons #block_button_item #button_link')[index]).is(":visible")) {
            buttons.push({ text: e.value, url: button_link });
        }
        if (e.value != '' && button_action != '' && $($(modal_id + ' #block_buttons #block_button_item #button_action')[index]).is(":visible")) {
            buttons.push({ text: e.value, callback_data: button_action });
        }
    });

    $.ajax({
        type: "POST",
        url: api_url + url,
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            title: $(modal_id + ' #title').val(),
            text: document.querySelector(modal_id+' #text_message').children[0].innerHTML,
            image: $(modal_id + ' #block_upload_image').attr('data-attach'),
            disable_web_page_preview: disable_web_page_preview,
            has_spoiler: has_spoiler,
            visible: $(modal_id + ' #visible').val(),
            buttons: JSON.stringify(buttons)
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                constructor_info();
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}


function saveLink() {

    let modal_id = '#createLink'

    $.ajax({
        type: "POST",
        url: api_url + '/links/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            title: $(modal_id + ' #title').val(),
            code: $(modal_id + ' #code').val(),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #title').val('');
                $(modal_id + ' #code').val('');
                $(modal_id).modal('hide');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function saveButtonCheck() {

    $.ajax({
        type: "POST",
        url: api_url + '/channels/sub/settings/button_check/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            button_check: $('#editButtonCheck #button_check').val(),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#editButtonCheck').modal('hide');
                constructor_info();
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function loadSidebar() {
    $.ajax({
        type: "GET",
        url: api_url + '/sidebar',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                const jsonData = data.result;

                const accordionSidebar = document.getElementById("menu_sidebar");

                accordionSidebar.innerHTML = '';

                jsonData.forEach(item => {
                    const html = item;

                    accordionSidebar.innerHTML += html;
                });

            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function saveReferral() {

    let modal_id = '#refModal';

    $.ajax({
        type: "POST",
        url: api_url + '/shops/settings/referral/save',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            ref_percent: $(modal_id + ' #ref_percent').val(),
            min_sum_withdrawal_card: $(modal_id + ' #min_sum_withdrawal_card').val(),
            min_sum_withdrawal_balance: $(modal_id + ' #min_sum_withdrawal_balance').val(),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function saveTopup() {

    let modal_id = '#modalBalance';

    $.ajax({
        type: "POST",
        url: api_url + '/shops/settings/topup/save',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            min_sum_topup: $(modal_id + ' #min_sum_topup').val(),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function saveNotify() {

    let modal_id = '#notifySettings';

    var tg_notify_buys = 0;
    var tg_notify_balance = 0;
    var tg_notify_users = 0;

    var notify_target_id = $(modal_id + ' #notify_target_id').val();

    if ($(modal_id + ' #tg_notify_buys').is(":checked")) {
        tg_notify_buys = 1;
    }
    if ($(modal_id + ' #tg_notify_balance').is(":checked")) {
        tg_notify_balance = 1;
    }
    if ($(modal_id + ' #tg_notify_users').is(":checked")) {
        tg_notify_users = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/shops/settings/notify/save',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            notify_target_id: notify_target_id,
            tg_notify_buys: tg_notify_buys,
            tg_notify_balance: tg_notify_balance,
            tg_notify_users: tg_notify_users,
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function saveToken() {

    let modal_id = '#secretToken';

    $.ajax({
        type: "POST",
        url: api_url + '/shops/token/change',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            token: $(modal_id + ' #token').val(),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                // shopInfo(); // скрыто — бот временно не нужен
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function saveDisplaySettings() {

    let modal_id = '#displayModal';

    $.ajax({
        type: "POST",
        url: api_url + '/shops/settings/display/save',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            display_products: $(modal_id + ' #display_products').val(),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                // shopInfo(); // скрыто — бот временно не нужен
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}


function top_sales(){
    $.ajax({
        type: "GET",
        url: api_url + '/orders/sales/top',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                var items = data.result;
                var items_html = '';

                $(items).each(function(index, e) {
                    items_html += '<tr><td class="py-3"><div class="d-flex py-1"><div><img width="48" src="/assets/img/cheat.png" class="rounded-circle mr-3" alt="image"></div><div class="d-flex flex-column justify-content-center"><h6 class="mb-0 font-weight-bold text-sm text-gray-800">'+e.title+'</h6><p class="text-sm text-secondary mb-0"><span class="text-success">'+e.count_sales+'</span> заказов</p></div></div></td><td class="align-middle text-sm"><p class="text-sm mb-0">'+e.count_profits+' ₽</p></td><td class="align-middle text-sm"><p class="text-sm mb-0">'+e.count_views+'</p></td></tr>';
                });

                $('table tbody').html(items_html);

            }
        }
    });
}


function checkRole() {
    $.ajax({
        type: "GET",
        url: api_url + '/permissions/'+path_b+'/check',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true && data.redirect !== undefined) {
                location.href = data.redirect;
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function statusesCheats(){
    $.ajax({
        type: "GET",
        url: api_url + '/statuses/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                var items = data.result;
                var items_html = '';

                $(items).each(function(index, e) {

                    var status_block = '';

                    if (e.status == 1) {
                        status_block = 'recommend';
                    }
                    if (e.status == 2) {
                        status_block = 'not-recommend';
                    }
                    if (e.status == 3) {
                        status_block = 'on-update';
                    }
                    if (e.status == 4) {
                        status_block = 'risk';
                    }

                    items_html += '<div class="col-md-4 my-2">\n' +
                        '                        <li class="d-flex justify-content-between align-items-center p-4" style="border: 2px solid #222933;border-radius: 15px">\n' +
                        '                            <h6 class="mb-0 font-weight-bold"><span class="status _'+ status_block +'">'+ e.title +'</span></h6>\n' +
                        '                            <div>\n' +
                        '                                <i class="far fa-pencil-alt ms-auto cursor-pointer mr-3" style="color:#696969" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" aria-label="Редактировать чит" data-toggle="modal" data-id="'+e.id+'" data-target="#changeCheat"></i>\n' +
                        '                                <a target="_blank" href="javascript:;"  onclick="deleteCheat('+e.id+');"><i class="far fa-trash-alt ms-auto cursor-pointer mr-3 text-danger" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" aria-label="Удалить чит"></i></a>\n' +
                        '                            </div>\n' +
                        '                        </li>\n' +
                        '                    </div>';
                });

                $('#cheats').html(items_html);

            }
        }
    });
}

function constructor_info(){
    $.ajax({
        type: "GET",
        url: api_url + '/texts/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                if(data.result.welcome){
                    if (data.result.welcome.image != '') {
                        $('#block_welcome #block_upload_image').attr('data-attach', data.result.welcome.image);
                        $('#block_welcome #block_upload_image').html('<a href="/' + data.result.welcome.image + '" data-fancybox="gallery"><img src="/' + data.result.welcome.image + '" /></a>');
                        $('#block_welcome #block_upload_image').removeClass('d-none');
                        $('#block_welcome #btn_upload_image').addClass('d-none');
                        $('#block_welcome #text_image_uploaded').removeClass('d-none');
                        $('#block_welcome #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center" onclick="removeImage(\'#block_welcome\');" style="line-height:50px;  "><i class="far fa-trash fa-xl"></i></a>');
                        $('#block_welcome #has_spoiler').prop("disabled", false);
                    } else {
                        $('#block_welcome #has_spoiler').attr('disabled', 'disabled');
                    }

                    if (data.result.welcome.disable_web_page_preview == 1) {
                        $('#block_welcome #disable_web_page_preview').prop('checked', true);
                    } else {
                        $('#block_welcome #disable_web_page_preview').prop("checked", false);
                    }

                    if (data.result.welcome.is_spoiler == 1) {
                        $('#block_welcome #has_spoiler').prop('checked', true);
                    } else {
                        $('#block_welcome #has_spoiler').prop("checked", false);
                    }

                    if (data.result.after_payment && data.result.after_payment.is_active == 1) {
                        $('#switch_text_pay').prop('checked', true);
                        $('#block_text_pay').removeClass('d-none');
                    } else {
                        $('#switch_text_pay').prop("checked", false);
                    }

                    if (data.result.agreement && data.result.agreement.is_active == 1) {
                        $('#switch_text_terms').prop('checked', true);
                        $('#block_text_terms').removeClass('d-none');
                    } else {
                        $('#switch_text_terms').prop("checked", false);
                    }

                    $('#text_welcome .ql-editor').html(data.result.welcome.text);
                    $('#text_after_payment .ql-editor').html(data.result.after_payment.text);
                    $('#text_agreement .ql-editor').html(data.result.agreement.text);
                }
                if(data.result.channels.all){

                    if(data.result.channels.settings.is_active == 0){
                        $('#switch_join').prop('checked', false);
                        $('#block_join').addClass('d-none');
                    }

                    if(data.result.channels.settings.is_active == 1){
                        $('#switch_join').prop('checked', true);
                        $('#block_join').removeClass('d-none');
                    }

                    var inner_html = '';
                    var count_columns = data.result.channels.columns;
                    var channels_count = data.result.channels.count;

                    $('#text_join .ql-editor').html(data.result.channels.settings.text);
                    $('#block_join #count_columns').val(count_columns);

                    var param_column;

                    if(count_columns == 1) {
                        param_column = '12';
                    } else if(count_columns == 2) {
                        param_column = '6';
                    } else if(count_columns == 3){
                        param_column = '4';
                    }

                    var param_column_add = '12'; // Инициализация переменной

                    if (channels_count == 1) {
                        switch (count_columns) {
                            case 1:
                                param_column_add = '12';
                                break;
                            case 2:
                                param_column_add = '6';
                                break;
                            case 3:
                                param_column_add = '4';
                                break;
                            default:
                                param_column_add = '12'; // Значение по умолчанию или другое подходящее значение
                        }
                    } else if (channels_count == 2) {
                        switch (count_columns) {
                            case 1:
                            case 2:
                                param_column_add = '12';
                                break;
                            case 3:
                                param_column_add = '4';
                                break;
                            default:
                                param_column_add = '12'; // Значение по умолчанию или другое подходящее значение
                        }
                    } else if (channels_count == 3 || channels_count == 5) {
                        switch (count_columns) {
                            case 1:
                                param_column_add = '12';
                                break;
                            case 2:
                                param_column_add = '6';
                                break;
                            case 3:
                                param_column_add = '4';
                                break;
                            default:
                                param_column_add = '12'; // Значение по умолчанию или другое подходящее значение
                        }
                    } else if (channels_count == 4 || channels_count == 6) {
                        switch (count_columns) {
                            case 1:
                            case 2:
                                param_column_add = '12';
                                break;
                            case 3:
                                param_column_add = '12';
                                break;
                            default:
                                param_column_add = '12'; // Значение по умолчанию или другое подходящее значение
                        }
                    } else {
                        param_column_add = '12'; // Значение по умолчанию для неожиданных значений channels_count
                    }


                    $(data.result.channels.all).each(function(index, e) {
                        inner_html += '<div class="col-sm-'+param_column+' px-2"><div class="card card-body card-plain border-radius-lg d-flex align-items-center bg-gradient-button flex-row my-2 px-4 py-3" title="'+e.title+'" style="border: 1px solid #1D2533;"><input type="hidden" name="sort[]" value="'+e.cid+'"><h6 class="mb-0 font-weight-bold" id="btn_title">'+e.title+'</h6> <i class="far fa-arrows ml-auto cursor-pointer handle" style="color:#bababa" data-toggle="tooltip" data-placement="top" aria-hidden="true" aria-label="Move button"></i><span class="sr-only">Move button</span> <i class="far fa-pencil-alt ml-3 cursor-pointer" style="color:#bababa" data-toggle="modal" data-id="'+e.cid+'" data-target="#editChannel"></i><span class="sr-only">Edit button</span><i class="far fa-trash ml-3 text-danger cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" aria-label="Delete button" onclick="deleteChannel('+e.cid+');"></i><span class="sr-only">Delete button</span></div></div>';
                    });

                    inner_html += '<div class="col-sm-'+param_column_add+' px-2"><div class="card card-body card-plain border-radius-lg text-center my-2 px-4 py-3 background-none cursor-pointer" title="Добавить канал" data-toggle="modal" data-target="#addChannel" style="background: transparent!important; border: 1px dashed#888; color: #888;"><h6 class="mb-0 font-weight-bold" id="btn_title">+ &nbsp;Добавить канал</h6></div></div>';
                    inner_html += '<div class="col-sm-12 px-2"><div class="card card-body card-plain border-radius-lg d-flex align-items-center bg-gradient-button flex-row my-2 px-4 py-3" style="border: 1px solid #1D2533;"><h6 class="mb-0 font-weight-bold" id="btn_title">'+data.result.channels.settings.button_check+'</h6> <i class="far fa-pencil-alt ml-auto cursor-pointer" style="color:#bababa" data-toggle="modal" data-target="#editButtonCheck"></i><span class="sr-only">Edit button</span></div></div>';

                    $('#channels').html(inner_html);
                }
                if(data.result.buttons.all){

                    var count_buttons = data.result.buttons.count;
                    var count_buttons_columns = data.result.buttons.columns;

                    var btn_inner_html = '';
                    var btn_param_column;
                    var btn_param_column_add;

                    if(count_buttons_columns == 1){
                        btn_param_column = '12';
                    } else if(count_buttons_columns == 2) {
                        btn_param_column = '6';
                    } else if(count_buttons_columns == 3){
                        btn_param_column = '4';
                    }

                    if (count_buttons == 1) {
                        switch (count_buttons_columns) {
                            case 1:
                                btn_param_column_add = '12';
                                break;
                            case 2:
                                btn_param_column_add = '6';
                                break;
                            case 3:
                                btn_param_column_add = '4';
                                break;
                        }
                    } else if (count_buttons == 2) {
                        switch (count_buttons_columns) {
                            case 1:
                            case 2:
                                btn_param_column_add = '12';
                                break;
                            case 3:
                                btn_param_column_add = '4';
                                break;
                        }
                    } else if (count_buttons == 3 || count_buttons == 5) {
                        switch (count_buttons_columns) {
                            case 1:
                                btn_param_column_add = '12';
                                break;
                            case 2:
                                btn_param_column_add = '6';
                                break;
                            case 3:
                                btn_param_column_add = '4';
                                break;
                        }
                    } else if (count_buttons == 4 || count_buttons == 6) {
                        switch (count_buttons_columns) {
                            case 1:
                            case 2:
                                btn_param_column_add = '12';
                                break;
                            case 3:
                                btn_param_column_add = '12';
                                break;
                        }
                    }

                    $(data.result.buttons.all).each(function(index, e) {
                        btn_inner_html += '<div class="col-sm-'+btn_param_column+' px-2"><div class="card card-body card-plain border-radius-lg d-flex align-items-center bg-gradient-button flex-row my-2 px-4 py-3" title="'+e.title+'" style="border: 1px solid #1D2533;"><input type="hidden" name="sort[]" value="'+e.id+'"><h6 class="mb-0 font-weight-bold" id="btn_title">'+e.title+'</h6> <i class="far fa-arrows ml-auto cursor-pointer handle" style="color:#bababa" data-toggle="tooltip" data-placement="top" aria-hidden="true" aria-label="Move button"></i><span class="sr-only">Move button</span> <i class="far fa-pencil-alt ml-3 cursor-pointer" style="color:#bababa" data-toggle="modal" data-id="'+e.id+'" data-target="#editButton"></i><span class="sr-only">Edit button</span><i class="far fa-trash ml-3 text-danger cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" aria-label="Delete button" onclick="deleteButton('+e.id+');"></i><span class="sr-only">Delete button</span></div></div>';
                    });

                    btn_inner_html += '<div class="col-sm-'+btn_param_column_add+' px-2"><div class="card card-body card-plain border-radius-lg text-center my-2 px-4 py-3 background-none cursor-pointer" title="Добавить канал" data-toggle="modal" data-target="#addButton" style="background: transparent!important; border: 1px dashed#888; color: #888;"><h6 class="mb-0 font-weight-bold" id="btn_title">+ &nbsp;Добавить кнопку</h6></div></div>';

                    $('#buttons').html(btn_inner_html);
                }
                $('#block_buttons #count_columns').val(data.result.buttons.columns)
            }
        }
    });
}


function logout() {
    $.ajax({
        type: "POST",
        url: api_url + '/auth/logout',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                location.href = '/';
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function paymentSystems() {
    $.ajax({
        type: "POST",
        url: api_url + '/methods_payments/ps/admin/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var systems = data.result;

                var methods = '';

                $(systems).each(function(index, e) {
                    var is_active = '';
                    if(e.is_active === 1){
                        is_active = ' checked';
                    }

                    var border_active = 'border: 2px solid #222933;';

                    methods += '<div class="col-md-4 my-2"><li class="d-flex justify-content-between align-items-center p-4" style="'+border_active+'border-radius: 15px"><h6 class="mb-0 font-weight-bold">' + e.title + '</h6><div><a target="_blank" href="' + e.link + '"><i class="far fa-link ms-auto cursor-pointer mr-3" style="color:#5c5c5c" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" aria-label="Ссылка на платежную систему"></i><span class="sr-only">Ссылка на платежную систему</span></a><i class="far fa-pencil-alt ms-auto cursor-pointer mr-3" style="color:#5c5c5c" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" aria-label="Редактировать метод" data-toggle="modal" data-title="'+e.title+'" data-type="'+e.type+'" data-target="#editMethod"></i><span class="sr-only">Редактировать метод</span><div class="custom-control custom-switch" style="margin-left: 40px;float: right"><input type="checkbox" class="custom-control-input" onclick="saveMethod(\'status\', \''+e.type+'\');" id="mp_'+e.type+'"'+is_active+'><label class="custom-control-label" for="mp_'+e.type+'"></label></div></div></li></div>';
                });

                $('#methods').html(methods);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}


function payment_systems_by_psid(psid, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/methods_payments/'+psid+'/list',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            callback(data);
        }
    });
}

function signIn() {

    let modal_id = '#auth';

    var username = $(modal_id + ' #username').val();
    var password = $(modal_id + ' #password').val();
    var hcaptcha_token = $(modal_id + ' textarea[name="h-captcha-response"]').val();

    $.ajax({
        type: "POST",
        url: api_url + '/auth/login',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            username: username,
            password: password,
            "h-captcha-response": hcaptcha_token
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
                createCookie('session_token', data.result.token, 15);
                if (getCookie('session_token') !== undefined) {
                    setTimeout(function() {
                        location.href = '/admin/stats';
                    }, 2000);
                }
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

}

function saveSender(id) {
    var modal_id = '#createSender';
    var url_method = '/senders/create';
    var prefix = 'cs';

    if (id > 0) {
        modal_id = '#editSender';
        url_method = '/senders/' + id + '/update';
        prefix = 'es';
    }

    var buttons = [];
    var currentDate = new Date();

    var has_spoiler = 0;
    var disable_web_page_preview = 0;

    if ($(modal_id + ' #block_message #' + prefix + '_has_spoiler').is(":checked")) {
        has_spoiler = 1;
    }
    if ($(modal_id + ' #block_message #' + prefix + '_disable_web_page_preview').is(":checked")) {
        disable_web_page_preview = 1;
    }

    $(modal_id + ' #block_buttons #block_button_item #button_title').each(function (index, e) {
        var button_link = $(modal_id + ' #block_buttons #block_button_item #button_link')[index].value;
        var button_action = $(modal_id + ' #block_buttons #block_button_item #button_action')[index].value;
        if (e.value != '' && button_link != '' && $($(modal_id + ' #block_buttons #block_button_item #button_link')[index]).is(":visible")) {
            buttons.push({ text: e.value, url: button_link });
        }
        if (e.value != '' && button_action != '' && $($(modal_id + ' #block_buttons #block_button_item #button_action')[index]).is(":visible")) {
            buttons.push({ text: e.value, callback_data: button_action });
        }
    });


    var today = new Date();
    var year = today.getFullYear();

    let started_at = year+'-'+$(modal_id + ' #date_month').val()+'-'+$(modal_id + ' #date_day').val()+'T'+$(modal_id + ' #date_hours').val()+':'+$(modal_id + ' #date_minutes').val();
    $.ajax({
        type: "POST",
        url: api_url + url_method,
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function (xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        data: JSON.stringify({
            type: 1,
            title: $(modal_id + ' #title').val(),
            message: document.querySelector(modal_id + ' .ql-editor').innerHTML,
            image: $(modal_id + ' #block_message #block_upload_image').attr('data-attach'),
            buttons: JSON.stringify(buttons),
            disable_web_page_preview: disable_web_page_preview,
            has_spoiler: has_spoiler,
            type_time: $(modal_id + ' #block_message #type_time').val(),
            started_at: started_at,
        }),
        success: function (data) {
            if (data.ok === true) {
                $('#datatable').DataTable().ajax.reload(null, false);
                $(modal_id).modal('hide');
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        },
    });
}




function checkSender(modal) {
    let modal_id = '#'+modal;
    var buttons = [];
    const currentDate = new Date();


    if(modal == 'editSender') {
        var prefix = 'es'
    }
    if(modal == 'createSender') {
        var prefix = 'cs'
    }

    if($(modal_id + ' #block_forward').is(":visible")) {
        var data = {
            type: 2,
            title: $(modal_id + ' #block_forward #title').val(),
            forward_link: $(modal_id + ' #block_forward #forward_link').val(),
        }

    }

    if($(modal_id + ' #block_message').is(":visible")) {

        var has_spoiler = 0;
        var disable_web_page_preview = 0;

        if ($(modal_id + ' #block_message #'+prefix+'_has_spoiler').is(":checked")) {
            has_spoiler = 1;
        }
        if ($(modal_id + ' #block_message #'+prefix+'_disable_web_page_preview').is(":checked")) {
            disable_web_page_preview = 1;
        }

        $(modal_id + ' #block_buttons #block_button_item #button_title').each(function (index, e) {
            var button_link = $(modal_id + ' #block_buttons #block_button_item #button_link')[index].value;
            var button_action = $(modal_id + ' #block_buttons #block_button_item #button_action')[index].value;
            if (e.value != '' && button_link != '' && $($(modal_id + ' #block_buttons #block_button_item #button_link')[index]).is(":visible")) {
                buttons.push({ text: e.value, url: button_link });
            }
            if (e.value != '' && button_action != '' && $($(modal_id + ' #block_buttons #block_button_item #button_action')[index]).is(":visible")) {
                buttons.push({ text: e.value, callback_data: button_action });
            }
        });

        var data = {
            type: 1,
            title: $(modal_id + ' #title').val(),
            message: document.querySelector(modal_id + ' #block_message #text_sender').children[0].innerHTML,
            image: $(modal_id + ' #block_message #block_upload_image').attr('data-attach'),
            buttons: JSON.stringify(buttons),
            disable_web_page_preview: disable_web_page_preview,
            has_spoiler: has_spoiler,
        }

    }

    $.ajax({
        type: "POST",
        url: api_url + '/senders/check',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        data: JSON.stringify(data),
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function senderNavAction(type){
    let modal_id = '#createSender';
    if(type === 'message') {
        $(modal_id + ' #block_' + type).removeClass('d-none');
        $(modal_id + ' #nav_' + type).addClass('nav_bg');
        $(modal_id + ' #nav_forward').removeClass('nav_bg');
        $(modal_id + ' #block_forward').addClass('d-none');
        $(modal_id + ' #btn-save').removeClass('d-none');
        $(modal_id + ' #btn-check').removeClass('d-none');
    }
    if(type === 'forward') {
        $(modal_id + ' #block_' + type).removeClass('d-none');
        $(modal_id + ' #nav_' + type).addClass('nav_bg');
        $(modal_id + ' #nav_message').removeClass('nav_bg');
        $(modal_id + ' #block_message').addClass('d-none');
        $(modal_id + ' #btn-save').removeClass('d-none');
        $(modal_id + ' #btn-check').removeClass('d-none');
    }
}


function signUp() {
    let modal_id = '#register';

    var username = $(modal_id + ' #username').val();
    var password = $(modal_id + ' #password').val();
    var hcaptcha_token = $(modal_id + ' textarea[name="h-captcha-response"]').val();
    if ($(modal_id + ' #terms').is(":checked")) {
        var terms = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/auth/register',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            username: username,
            password: password,
            terms: terms,
            "h-captcha-response": hcaptcha_token
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
                createCookie('session_token', data.result.token, 15);
                if (getCookie('session_token') !== undefined) {
                    setTimeout(function() {
                        location.href = '/admin/stats';
                    }, 2000);
                }
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });

}

function shopInfo() {
    $.ajax({
        type: "GET",
        url: api_url + '/shops/info',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                if (path_b === 'create' && path_c === 'shop') {
                    location.href = '/admin/stats';
                } else {
                    $('#s_link').attr('href', 'https://t.me/' + data.result.username);
                    if(data.result.avatar != ''){
                        $('#s_avatar').attr('src', data.result.avatar);
                    }

                    $('#s_username').text(data.result.username);
                    $('#result-token').val(data.result.token);
                    if(data.result.status == 0){
                        $('#btn-status-change').removeClass('active');
                    } else if(data.result.status == 1){
                        $('#btn-status-change').addClass('active');
                    }
                }
            }
        }

    });

}

function getShopToken() {
    $.ajax({
        type: "GET",
        url: api_url + '/shops/token',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#secretToken #result-token').val(data.result.token);
                $('#secretToken #btn-get-token').remove();
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

}

function getProductAlias(modal_id) {

    let title = $(modal_id + ' #title').val();

    $.ajax({
        type: "POST",
        url: api_url+'/products/alias/check',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        data: JSON.stringify({
            title: title,
        }),
        async: false,
        success: function(data) {
            if(data.ok === true){
                $(modal_id +' #alias').val(data.result);
            } else if(data.ok === false){
                messageSystem(false, data.description, 3000);
            }
        }

    });

}


function getInstructionAlias(modal_id) {

    let title = $(modal_id + ' #title').val();

    $.ajax({
        type: "POST",
        url: api_url+'/instructions/alias/check',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        data: JSON.stringify({
            title: title,
        }),
        async: false,
        success: function(data) {
            if(data.ok === true){
                $(modal_id +' #alias').val(data.result);
            } else if(data.ok === false){
                messageSystem(false, data.description, 3000);
            }
        }

    });

}

function setStatus() {
    $.ajax({
        type: "GET",
        url: api_url + '/shops/status/change',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if(data.ok === true) {
                // shopInfo(); // скрыто — бот временно не нужен
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function roleInfo(id, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/roles/' + id + '/info',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            callback(data);
        }
    });
}

function selectRolesAll(role_id, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/roles/select/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            callback(data);
        }
    });
}

function userInfo(callback) {

    $.ajax({
        type: "GET",
        url: api_url + '/users/info',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            callback(data);
        },
        error: function(xhr, status, error) {
            if (xhr.status === 401) {
                location.href = '/';
            }
        }

    });

}

function permissionsList(id, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/permissions/' + id + '/list',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            callback(data);
        }
    });
}

function instructionsList(in_id, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/instructions/list',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            callback(data);
        }
    });
}

$("#select_period_profits").on('change', function (data) {
    getStats(this.value,2);
});

$("#select_period_sales").on('change', function () {
    getStats(this.value,1);
});

$("#select_period_members").on('change', function () {
    getStats(this.value,3);
});

function getStats(period,type) {

    $.ajax({
        type: "POST",
        url: api_url + '/stats/get',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            period: period,
            type: type,
        }),
        beforeSend: function (xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function (data) {
            if (data.ok === true) {
                if (data.result.profits !== undefined)
                    $('#profits_value').text(data.result.profits);
                if (data.result.sales !== undefined) {
                    $('#sales_value').text(data.result.sales);
                }
                if (data.result.members !== undefined) {
                    $('#members_value').text(data.result.members);
                }
            }
        }
    });
}

function messageSystem(status, description, delay = 3000) {
    if (status == false) {
        $("#system_msg").removeClass().show().html('<span><i class="far fa-exclamation-circle mr-2"></i>' + description + '</span>').addClass('error').delay(delay).fadeOut(200);
    } else if (status == true) {
        $("#system_msg").removeClass().show().html('<span><i class="far fa-check-circle mr-2"></i>' + description + '</span>').addClass('success').delay(delay).fadeOut(200);
    }
}

function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function createCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = ";expires=" + date.toGMTString();
    }
    document.cookie = name + "=" + value + expires + ";path=/";
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}


function createCoupon() {
    let modal_id = '#newCoupon';

    var is_new_users = 0;
    var is_one_time = 0;

    if ($(modal_id + ' #add_is_new_users').is(":checked")) {
        is_new_users = 1;
    }

    if ($(modal_id + ' #add_is_one_time').is(":checked")) {
        is_one_time = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/coupons/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            gids: $(modal_id + ' #gids').val(),
            code: $(modal_id + ' #code').val(),
            sale: $(modal_id + ' #sale').val(),
            sale_type: $(modal_id + ' #sale_type').val(),
            min_sum: $(modal_id + ' #min_sum').val(),
            // count_expired: $(modal_id + ' #count_expired').val(),
            // count_expired_type: $(modal_id + ' #count_expired_type').val(),
            count_uses_min: $(modal_id + ' #count_uses_min').val(),
            count_uses_type: $(modal_id + ' #count_uses_type').val(),
            count_uses_max: $(modal_id + ' #count_uses_max').val(),
            is_new_users: is_new_users,
            is_one_time: is_one_time,
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $(modal_id + ' #code').val('');
                $(modal_id + ' #sale').val('');
                $(modal_id + ' #sale_type').val(0);
                $(modal_id + ' #min_sum').val(0);
                $(modal_id + ' #count_uses_min').val(1);
                $(modal_id + ' #count_uses_type').val(0);
                $(modal_id + ' #count_uses_max').val('');
                // $(modal_id + ' #count_expired').val('');
                // $(modal_id + ' #count_expired_type').val(0);
                $(modal_id + ' #add_is_new_users').prop("checked", false);
                $(modal_id + ' #add_is_one_time').prop("checked", false);
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function changeCoupon(id) {
    let modal_id = '#editCoupon';

    var is_new_users = 0;
    var is_one_time = 0;

    if ($(modal_id + ' #edit_is_new_users').is(":checked")) {
        is_new_users = 1;
    }

    if ($(modal_id + ' #edit_is_one_time').is(":checked")) {
        is_one_time = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/coupons/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            gids: $(modal_id + ' #gids').val(),
            code: $(modal_id + ' #code').val(),
            sale: $(modal_id + ' #sale').val(),
            sale_type: $(modal_id + ' #sale_type').val(),
            min_sum: $(modal_id + ' #min_sum').val(),
            // count_expired: $(modal_id + ' #count_expired').val(),
            // count_expired_type: $(modal_id + ' #count_expired_type').val(),
            count_uses_min: $(modal_id + ' #count_uses_min').val(),
            count_uses_type: $(modal_id + ' #count_uses_type').val(),
            count_uses_max: $(modal_id + ' #count_uses_max').val(),
            is_new_users: is_new_users,
            is_one_time: is_one_time,
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}


function createFaq() {
    let modal_id = '#createFaq';
    $.ajax({
        type: "POST",
        url: api_url + '/faq/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            in_id: $(modal_id + ' #in_id').val(),
            question: $(modal_id + ' #question').val(),
            answer: document.querySelector(modal_id + ' #text_answer').children[0].innerHTML,
            visibility: $(modal_id + ' #visibility').val()
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $(modal_id + ' #in_id').val(0);
                $(modal_id + ' #question').val('');
                $(modal_id + ' #text_answer .ql-editor').html('');
                $(modal_id + ' #visibility').val(1);
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function changeFaq() {
    let modal_id = '#changeFaq';
    let id = $(modal_id).attr('data-id');
    $.ajax({
        type: "POST",
        url: api_url + '/faq/'+id+'/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            in_id: $(modal_id + ' #in_id').val(),
            question: $(modal_id + ' #question').val(),
            answer: document.querySelector(modal_id + ' #text_answer').children[0].innerHTML,
            visibility: $(modal_id + ' #visibility').val()
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function changeRole() {
    let modal_id = '#editRole';
    let id = $(modal_id).attr('data-id');

    $.ajax({
        type: "POST",
        url: api_url + '/roles/'+id+'/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            title: $(modal_id + ' #title').val(),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function createRole() {
    let modal_id = '#addRole';

    $.ajax({
        type: "POST",
        url: api_url + '/roles/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            title: $(modal_id + ' #title').val(),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
                setTimeout(function() {
                    $('a[data-target="#editRole"][data-id="'+data.result.role_id+'"]').click();
                }, 300);

            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function createCheat() {
    let modal_id = '#createCheat';
    $.ajax({
        type: "POST",
        url: api_url + '/statuses/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            game_id: $(modal_id + ' #game_id').val(),
            title: $(modal_id + ' #title').val(),
            status: $(modal_id + ' #status').val()
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $(modal_id + ' #game_id').val('');
                $(modal_id + ' #title').val('');
                $(modal_id + ' #status').val(0);
                statusesCheats();
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function changeCheat() {
    let modal_id = '#changeCheat';
    let id = $(modal_id).attr('data-id');

    var is_notify = 0;
    if ($(modal_id + ' #is_notify').is(":checked")) {
        is_notify = 1;
    }
    $.ajax({
        type: "POST",
        url: api_url + '/statuses/'+id+'/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            game_id: $(modal_id + ' #game_id').val(),
            title: $(modal_id + ' #title').val(),
            status: $(modal_id + ' #status').val(),
            is_notify: is_notify
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                statusesCheats();
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function createInstruction() {
    let modal_id = '#createInstruction';

    var buttons = [];
    var is_link_error = true;

    $(document.querySelectorAll(modal_id + ' #button_title')).each(function (index, e) {
        var button_link = document.querySelectorAll(modal_id + ' #button_link')[index].value;
        if (e.value != '' && button_link != '') {
            // var urlPattern = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i;
            //
            // if (!urlPattern.test(button_link)) {
            //     is_link_error = false;
            //     messageSystem(false, 'Неверная ссылка для кнопки: ' + e.value, 3000);
            // }

            buttons[index] = {text: e.value, url: button_link};
        }
    });

    if (is_link_error) {
        $.ajax({
            type: "POST",
            url: api_url + '/instructions/create',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                pids: JSON.stringify($(modal_id + ' #pids').val()),
                title: $(modal_id + ' #title').val(),
                alias: $(modal_id + ' #alias').val(),
                body: document.querySelector(modal_id + ' #text_instruction').children[0].innerHTML,
                buttons: JSON.stringify(buttons)
            }),
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $(modal_id).modal('hide');
                    $(modal_id + ' #pids').val('');
                    $(modal_id + ' #title').val('');
                    $(modal_id + ' #alias').val('');
                    $(modal_id + ' #text_instruction .ql-editor').html('');
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}


function selectCatsAll(id, type) {
    return $.ajax({
        type: "GET",
        url: api_url + '/categories/'+type+'/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
    });
}


function changeInstruction() {
    let modal_id = '#changeInstruction';
    let id = $(modal_id).attr('data-id');

    var buttons = [];
    var is_link_error = true;

    $(document.querySelectorAll(modal_id + ' #button_title')).each(function (index, e) {
        var button_link = document.querySelectorAll(modal_id + ' #button_link')[index].value;
        if (e.value != '' && button_link != '') {
            // var urlPattern = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i;
            //
            // if (!urlPattern.test(button_link)) {
            //     is_link_error = false;
            //     messageSystem(false, 'Неверная ссылка для кнопки: ' + e.value, 3000);
            // }

            buttons[index] = {text: e.value, url: button_link};
        }
    });

    if (is_link_error) {
        $.ajax({
            type: "POST",
            url: api_url + '/instructions/' + id + '/update',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                pids: JSON.stringify($(modal_id + ' #pids').val()),
                title: $(modal_id + ' #title').val(),
                alias: $(modal_id + ' #alias').val(),
                body: document.querySelector(modal_id + ' #text_instruction').children[0].innerHTML,
                buttons: JSON.stringify(buttons)
            }),
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $(modal_id).modal('hide');
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}

function deleteInstruction(id) {
    if (confirm("Вы уверены, что хотите удалить данную инструкцию?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/instructions/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}

function addCategory() {
    let modal_id = '#addCategory';

    var has_spoiler = 0;
    var disable_web_page_preview = 0;

    if ($(modal_id + ' #ec_has_spoiler').is(":checked")) {
        has_spoiler = 1;
    }
    if ($(modal_id + ' #ec_disable_web_page_preview').is(":checked")) {
        disable_web_page_preview = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/categories/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            cid: $(modal_id + ' #cid').val(),
            title: $(modal_id + ' #title').val(),
            seo_description: $(modal_id + ' #seo_description').val(),
            seo_keywords: $(modal_id + ' #seo_keywords').val(),
            description: document.querySelector(modal_id + ' #text_message').children[0].innerHTML,
            image_site: $(modal_id + ' #image_site #block_upload_image').attr('data-attach'),
            image: $(modal_id + ' #image_bot #block_upload_image').attr('data-attach'),
            alias: $(modal_id + ' #alias').val(),
            count_column: $(modal_id + ' #count_column').val(),
            display_products: $(modal_id + ' #display_products').val(),
            visibility: $(modal_id + ' #visibility').val(),
            has_spoiler: has_spoiler,
            disable_web_page_preview: disable_web_page_preview
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $(modal_id + ' #cid').val(0);
                $(modal_id + ' #title').val('');
                $(modal_id + ' #seo_description').val('');
                $(modal_id + ' #seo_keywords').val('');
                $(modal_id + ' #text_message .ql-editor').html('');
                $(modal_id + ' #count_column').val(1);
                $(modal_id + ' #display_products').val(0);
                $(modal_id + ' #visibility').val(1);
                removeImage(modal_id)
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function editCategory(id) {
    let modal_id = '#editCategory';

    var has_spoiler = 0;
    var disable_web_page_preview = 0;

    if ($(modal_id + ' #ec_has_spoiler').is(":checked")) {
        has_spoiler = 1;
    }
    if ($(modal_id + ' #ec_disable_web_page_preview').is(":checked")) {
        disable_web_page_preview = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/categories/'+id+'/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            cid: $(modal_id + ' #cid').val(),
            title: $(modal_id + ' #title').val(),
            seo_description: $(modal_id + ' #seo_description').val(),
            seo_keywords: $(modal_id + ' #seo_keywords').val(),
            description: document.querySelector(modal_id + ' #text_message').children[0].innerHTML,
            image_site: $(modal_id + ' #image_site #block_upload_image').attr('data-attach'),
            image: $(modal_id + ' #image_bot #block_upload_image').attr('data-attach'),
            alias: $(modal_id + ' #alias').val(),
            count_column: $(modal_id + ' #count_column').val(),
            display_products: $(modal_id + ' #display_products').val(),
            visibility: $(modal_id + ' #visibility').val(),
            has_spoiler: has_spoiler,
            disable_web_page_preview: disable_web_page_preview
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}
function noRights(element, modal_id){
    var checkeds = document.querySelectorAll(modal_id + ' #block_checked[data-is="true"]');
    var checkeds_inputs = document.querySelectorAll(modal_id + ' #block_checked[data-is="true"] input');
    console.log(element.checked);
    if(element.checked === true) {
        checkeds_inputs.forEach(function (element) {
            element.checked = false;
        })
        checkeds.forEach(function (element) {
            element.style.display = 'none';
        });
    } else {
        checkeds.forEach(function (element) {
            element.style.display = 'block';
        });
    }
}

function checkCat(modal_id) {
    let id = $(modal_id + ' #cid').val();
    info('categories', id).done(function(data) {
        if(data.ok === true) {
            var checkeds = document.querySelectorAll(modal_id + ' #block_checked[data-id]');
            checkeds.forEach(function(element) {
                if (element.getAttribute('data-id') === data.result.alias){
                    element.style.display = 'block';
                    element.setAttribute('data-is', 'true');
                } else {
                    element.style.display = 'none';
                    element.setAttribute('data-is', 'false');
                }
            });
        }}
    );
}

function editText(type) {

    var disable_web_page_preview = 0;
    if ($('#disable_web_page_preview').is(":checked")) {
        disable_web_page_preview = 1;
    }

    var has_spoiler = 0;
    if ($('#has_spoiler').is(":checked")) {
        has_spoiler = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/texts/'+type+'/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            text: document.querySelector('#text_'+type).children[0].innerHTML,
            image: $('#block_'+type+' #block_upload_image').attr('data-attach'),
            type: type,
            disable_web_page_preview: disable_web_page_preview,
            is_spoiler: has_spoiler,
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
                constructor_info();
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}
function saveChannelSubSettings(type) {

    if(type === 'text') {
        data = {type: type, text: document.querySelector('#block_join #text_join').children[0].innerHTML}
    }
    if(type === 'columns') {
        data = {type: type, count_columns: $('#block_join #count_columns').val()}
    }

    $.ajax({
        type: "POST",
        url: api_url + '/channels/sub/settings/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(data),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
                constructor_info();
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

$("#switch_join").click(function() {
    if($(this).is(":checked")){
        $('#block_join').removeClass('d-none');
    } else {
        $('#block_join').addClass('d-none');
    }
});

$("#switch_text_pay").click(function() {
    if($(this).is(":checked")){
        $('#block_text_pay').removeClass('d-none');
    } else {
        $('#block_text_pay').addClass('d-none');
    }
});

$("#switch_text_terms").click(function() {
    if($(this).is(":checked")){
        $('#block_text_terms').removeClass('d-none');
    } else {
        $('#block_text_terms').addClass('d-none');
    }
});


function ajaxSaveMethod(modal_id,json) {
    $.ajax({
        type: "POST",
        url: api_url + '/methods_payments/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(json),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                if(data.action === 'open_modal') {
                    $('i[data-type="'+data.type+'"]').click();
                } else {
                    $(modal_id).modal('hide');
                    messageSystem(true, data.description, 2000);
                }

            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}
function saveMethod(action, type) {
    let modal_id = '#editMethod';

    if(type === 'qw'){
        exists('methods_payments', type).done(function(data){
            if(data.ok === true){
                var json = {
                    type: type,
                    action: action,
                    public_key: $(modal_id + ' #method_' + type + ' #public_key').val(),
                    secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
                    theme_code: $(modal_id + ' #method_' + type + ' #theme_code').val(),
                }
                ajaxSaveMethod(modal_id, json);
            } else if(data.ok === false){
                var json = {
                    type: type,
                    action: action,
                    phone: $(modal_id + ' #method_' + type + ' #phone').val(),
                    password: $(modal_id + ' #method_' + type + ' #password').val(),
                    theme_code: $(modal_id + ' #method_' + type + ' #link_widget').val()
                }
                ajaxSaveMethod(modal_id, json);
            }
        });
    }
    if(type === 'et' || type === 'ai'){
        var json = {
            type: type,
            action: action,
            public_id: $(modal_id + ' #method_' + type + ' #public_id').val(),
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
            secret_key_two: $(modal_id + ' #method_' + type + ' #secret_key_two').val(),
        }
        ajaxSaveMethod(modal_id, json);
    }
    if(type === 'cp'){
        var json = {
            type: type,
            action: action,
            public_id: $(modal_id + ' #method_' + type + ' #public_id').val(),
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
            secret_key_two: $(modal_id + ' #method_' + type + ' #secret_key_two').val(),
        }
        ajaxSaveMethod(modal_id, json);
    }
    if(type === 'fk'){
        var json = {
            type: type,
            action: action,
            public_id: $(modal_id + ' #method_' + type + ' #public_id').val(),
            public_key: $(modal_id + ' #method_' + type + ' #public_key').val(),
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
            secret_key_two: $(modal_id + ' #method_' + type + ' #secret_key_two').val(),
        }
        ajaxSaveMethod(modal_id, json);
    }
    if(type === 'lv' || type === 'ap' || type === 'rk' || type === 'ym' || type === 'po'){
        var json = {
            type: type,
            action: action,
            public_id: $(modal_id + ' #method_' + type + ' #public_id').val(),
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
        }
        ajaxSaveMethod(modal_id, json);
    }
    if(type === 'pp'){
        var json = {
            type: type,
            action: action,
            public_id: $(modal_id + ' #method_' + type + ' #public_id').val(),
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
        }
        ajaxSaveMethod(modal_id, json);
    }
    if(type === 'bt' || type === 'bn' || type === 'sp'){
        var json = {
            type: type,
            action: action,
            public_key: $(modal_id + ' #method_' + type + ' #public_key').val(),
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
        }
        ajaxSaveMethod(modal_id, json);
    }
    if(type === 'sm'){
        var json = {
            type: type,
            action: action,
            public_id: $(modal_id + ' #method_' + type + ' #public_id').val(),
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
        }
        ajaxSaveMethod(modal_id, json);
    }
    if(type === 'cb'){

        var assets = [];

        if($('#cb_btc').is(":checked")){assets.push('btc');}
        if($('#cb_ton').is(":checked")){assets.push('ton');}
        if($('#cb_eth').is(":checked")){assets.push('eth');}
        if($('#cb_usdt').is(":checked")){assets.push('usdt');}
        if($('#cb_usdc').is(":checked")){assets.push('usdc');}
        if($('#cb_ltc').is(":checked")){assets.push('ltc');}
        if($('#cb_bnb').is(":checked")){assets.push('bnb');}
        if($('#cb_trx').is(":checked")){assets.push('trx');}

        var json = {
            type: type,
            action: action,
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
            assets: assets
        }
        ajaxSaveMethod(modal_id, json);
    }
    if(type === 'ts'){
        var json = {
            type: type,
            action: action,
            secret_key: $(modal_id + ' #method_' + type + ' #secret_key').val(),
            secret_key_two: $(modal_id + ' #method_' + type + ' #secret_key_two').val(),
        }
        ajaxSaveMethod(modal_id, json);
    }
}

function saveAsset(id) {

    let modal_id = '#editAsset';
    let min = $(modal_id + ' #min').val();
    let max = $(modal_id + ' #max').val();

    $.ajax({
        type: "POST",
        url: api_url + '/payment_assets/'+id+'/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            min: min,
            max: max
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if(data.ok === true) {
                $(modal_id).modal('hide');
                messageSystem(true, data.description, 2000);
            } else if(data.ok === false){
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function saveAssetActive(id) {
    $.ajax({
        type: "GET",
        url: api_url + '/payment_assets/'+id+'/active/update',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if(data.ok === true) {
                messageSystem(true, data.description, 2000);
            } else if(data.ok === false){
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function addProduct() {
    let modal_id = '#addProduct';
    let prefix = 'ap_';

    var count_max = 0;
    var has_spoiler = 0;
    var disable_web_page_preview = 0;

    var functionals = [];
    var tariffs = [];
    var gallery = [];

    var gallery_elements = document.querySelectorAll(modal_id + " .dropzone div[data-id]");

    gallery_elements.forEach(function(element) {
        var preview = element.getAttribute('data-id');
        if (preview) {
            gallery.push(preview);
        }
    });

    $(modal_id + " #blocks_functional textarea").each(function(index) {
        var value = $(this).val();
        functionals.push(value);
    });

    $(modal_id + " #block_tariff [data-id]").each(function(index) {
        var days = $(this).find("#days").val();
        var price = $(this).find("#price").val();

        // Проверка формата цены
        if (!/^\d+(\.\d{1,2})?$/.test(price)) {
            messageSystem(false, "Некорректный формат цены", 3000);
            throw new Error("");
        }

        tariffs.push({ days: days, price: price });
    });

    if ($(modal_id + ' #'+prefix+'add_count_max').is(":checked")) {
        count_max = 1;
    }
    if ($(modal_id + ' #'+prefix+'add_count_max').is(":checked")) {
        count_max = 1;
    }
    if ($(modal_id + ' #'+prefix+'has_spoiler').is(":checked")) {
        has_spoiler = 1;
    }
    if ($(modal_id + ' #'+prefix+'disable_web_page_preview').is(":checked")) {
        disable_web_page_preview = 1;
    }

    let hack_status = $(modal_id + ' #hack_status').val();

    $.ajax({
        type: "POST",
        url: api_url + '/products/create',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            cid: $(modal_id + ' #cid').val(),
            title: $(modal_id + ' #title').val(),
            seo_description: $(modal_id + ' #seo_description').val(),
            seo_keywords: $(modal_id + ' #seo_keywords').val(),
            advantages: $(modal_id + ' #advantages').val(),
            functional: functionals,
            tariffs: tariffs,
            description: (window.descAddQuill ? window.descAddQuill.root.innerHTML : ''),
            image_site: $(modal_id + ' #image_site #block_upload_image').attr('data-attach'),
            image: $(modal_id + ' #image_bot #block_upload_image').attr('data-attach'),
            gallery: gallery,
            system_versions: $(modal_id + ' #system_versions').val(),
            system_auth: $(modal_id + ' #system_auth').val(),
            link_video: $(modal_id + ' #link_video').val(),
            alias: $(modal_id + ' #alias').val(),
            count_max: count_max,
            visibility: $(modal_id + ' #visibility').val(),
            status_id: $(modal_id + ' #status').val(),
            hack_status: hack_status,
            has_spoiler: has_spoiler,
            disable_web_page_preview: disable_web_page_preview
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                clearForm(modal_id)
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function countLines(modal_id){
    console.log(document.getElementById('body').value.split('\n').length);
}

function sendMessage(id) {
    let modal_id = '#sendMessage';
    let image = '';

    $.ajax({
        type: "POST",
        url: api_url + '/members/write',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            text: document.querySelector(modal_id + ' #text_message').children[0].innerHTML,
            image: $(modal_id + ' #block_upload_image').attr('data-attach'),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $(modal_id + ' #text_message .ql-editor').html('');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function addMaterial(id) {
    let modal_id = '#addMaterial';

    $.ajax({
        type: "POST",
        url: api_url + '/materials/add',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            pid: id,
            tid: $(modal_id + ' #tid').val(),
            body: $(modal_id + ' #body').val(),
            file: $(modal_id + ' #block_upload_file').attr('data-attach'),
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function exportMaterials(id) {
    let modal_id = '#exportMaterials';

    var remove_from_stock = 0;
    if ($(modal_id + ' #remove_from_stock').is(":checked")) {
        remove_from_stock = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/materials/export',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            tid: $(modal_id + ' #tid').val(),
            pid: id,
            count: $(modal_id + ' #count').val(),
            remove_from_stock: remove_from_stock
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#datatable').DataTable().ajax.reload(null, false);
                $(modal_id + ' #block-response').removeClass('d-none').addClass('d-block');
                $(modal_id + ' #result-body').html(data.result.body);
                $(modal_id + ' #btn-download').attr('onclick', 'downloadExport(' + data.result.export_id + ');return false;');
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function editProduct(id) {
    let modal_id = '#editProduct';
    let prefix = 'ep_';

    var count_max = 0;
    var has_spoiler = 0;
    var disable_web_page_preview = 0;

    var functionals = [];
    var tariffs = [];
    var gallery = [];


    var gallery_elements = document.querySelectorAll(modal_id + " .dropzone div[data-id]");

    gallery_elements.forEach(function(element) {
        var preview = element.getAttribute('data-id');
        if (preview) {
            gallery.push(preview);
        }
    });

    $(modal_id + " #blocks_functional textarea").each(function(index) {
        var value = $(this).val();
        functionals.push(value);
    });

    $(modal_id + " #block_tariff [data-id]").each(function(index) {
        var t_id = $(modal_id + ' #block_tariff #block_tariff_item[data-num="'+index+'"]').attr('data-id');
        var days = $(this).find("#days").val();
        var price = $(this).find("#price").val();

        // Проверка на корректность формата цены
        if (!/^\d+(\.\d{1,2})?$/.test(price)) {
            messageSystem(false, "Некорректный формат цены", 3000);
            throw new Error("");
        }

        tariffs.push({id: t_id, days: days, price: price });
    });


    if ($(modal_id + ' #'+prefix+'add_count_max').is(":checked")) {
        count_max = 1;
    }
    if ($(modal_id + ' #'+prefix+'has_spoiler').is(":checked")) {
        has_spoiler = 1;
    }
    if ($(modal_id + ' #'+prefix+'disable_web_page_preview').is(":checked")) {
        disable_web_page_preview = 1;
    }

    let hack_status = $(modal_id + ' #hack_status').val();

    $.ajax({
        type: "POST",
        url: api_url + '/products/'+ id +'/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            cid: $(modal_id + ' #cid').val(),
            title: $(modal_id + ' #title').val(),
            seo_description: $(modal_id + ' #seo_description').val(),
            seo_keywords: $(modal_id + ' #seo_keywords').val(),
            advantages: $(modal_id + ' #advantages').val(),
            functional: functionals,
            tariffs: tariffs,
            description: (window.descEditQuill ? window.descEditQuill.root.innerHTML : ''),
            image_site: $(modal_id + ' #image_site #block_upload_image').attr('data-attach'),
            image: $(modal_id + ' #image_bot #block_upload_image').attr('data-attach'),
            gallery: gallery,
            system_versions: $(modal_id + ' #system_versions').val(),
            system_auth: $(modal_id + ' #system_auth').val(),
            link_video: $(modal_id + ' #link_video').val(),
            alias: $(modal_id + ' #alias').val(),
            count_max: count_max,
            visibility: $(modal_id + ' #visibility').val(),
            status_id: $(modal_id + ' #status').val(),
            hack_status: hack_status,
            has_spoiler: has_spoiler,
            disable_web_page_preview: disable_web_page_preview
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function downloadMembersExport() {
    $.ajax({
        url: api_url + '/members/json/export',
        method: 'GET',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(data) {
            var a = document.createElement('a');
            var url = window.URL.createObjectURL(data);
            a.href = url;
            a.download = 'members.json';
            document.body.append(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        }
    });
}

function downloadExport(id) {
    $.ajax({
        url: api_url + '/mexports/' + id + '/download',
        method: 'GET',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(data) {
            var a = document.createElement('a');
            var url = window.URL.createObjectURL(data);
            a.href = url;
            a.download = 'export-' + id + '.txt';
            document.body.append(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        }
    });
}


function downloadOrder(id) {
    $.ajax({
        url: api_url + '/orders/' + id + '/download',
        method: 'GET',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(data) {
            var a = document.createElement('a');
            var url = window.URL.createObjectURL(data);
            a.href = url;
            a.download = 'order-' + id + '.txt';
            document.body.append(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        }
    });
}

function ban(id) {

    $.ajax({
        type: "POST",
        url: api_url + '/members/ban',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function editMemberPercent(id) {
    let modal_id = '#editMember';
    $.ajax({
        type: "POST",
        url: api_url + '/members/change/ref_percent',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            value: $(modal_id + ' #ref_percent').val()
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function editMemberRole(id) {
    let modal_id = '#editMember';
    $.ajax({
        type: "POST",
        url: api_url + '/members/change/role',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            value: $(modal_id + ' #role_id').val()
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function editMemberBalance(type, id) {
    let modal_id = '#editMember';
    $.ajax({
        type: "POST",
        url: api_url + '/members/change/balance',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            id: id,
            type: type,
            value: $(modal_id + ' #balance_value').val()
        }),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #balance').text(data.result.balance_main);
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function visibility(type, id) {

    var method = '';
    if (type === 'product') {
        method = '/products/'+id+'/visibility';
    }
    if (type === 'category') {
        method = '/categories/'+id+'/visibility';
    }
    if (type === 'faq') {
        method = '/faq/'+id+'/visibility';
    }

    $.ajax({
        type: "POST",
        url: api_url + method,
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function setPermission(id, permission){

    $.ajax({
        type: "POST",
        url: api_url + '/permissions/'+id+'/'+permission+'/update',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                loadSidebar();
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function setPermissionLike(id, permission){

    $.ajax({
        type: "POST",
        url: api_url + '/permissions/'+id+'/'+permission+'/like/update',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                loadSidebar();
                permissionsList(id, function (data) {
                    if (data.ok === true) {
                        var roles = '';

                        $(data.result).each(function(index, s) {
                            var permissions = '';

                            var count_allows = 0;

                            $(s.permissions).each(function(index, e) {

                                var checked = '';

                                if (e.allow == 1){
                                    count_allows += 1
                                    checked = ' checked'
                                }

                                permissions += '' +
                                    '<div class="custom-control custom-switch d-block" style="margin-left: 55px;margin-bottom: 10px;">\n' +
                                    '    <input type="checkbox" class="custom-control-input d-block" onclick="setPermission('+id+',\''+e.permission+'\');" id="'+e.permission+'"'+checked+'>\n' +
                                    '    <label class="custom-control-label" for="'+e.permission+'" style="margin-left: 20px;">'+e.title+'</label>\n' +
                                    '</div>';
                            });

                            var checked_item = '';

                            if (count_allows > 0){
                                checked_item = ' checked';
                            }

                            roles += '<hr /><div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">\n' +
                                '    <input type="checkbox" class="custom-control-input d-block" id="'+s.alias+'" onclick="setPermissionLike('+id+',\''+s.alias+'\');"'+checked_item+'>\n' +
                                '    <label class="custom-control-label font-weight-bold" for="'+s.alias+'" style="margin-left: 20px;">'+s.title+'</label>\n' +
                                '</div>\r\n'+permissions;
                        });
                        $('#editRole #permissions').html(roles);
                    } else if (data.ok === false) {
                        messageSystem(false, data.description, 3000);
                    }
                });

                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

function deleteChannel(id) {
    if (confirm("Вы уверены, что хотите удалить канал?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/channels/sub/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    constructor_info();
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}


function deleteFaq(id) {
    if (confirm("Вы уверены, что хотите удалить пункт?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/faq/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}


function createReview() {
    let modal_id = '#createReview';
    let formData = new FormData();
    formData.append('author', $(modal_id + ' #review_author').val());
    formData.append('author_link', $(modal_id + ' #review_author_link').val());
    formData.append('text', $(modal_id + ' #review_text').val());
    formData.append('avatar', $(modal_id + ' #review_avatar').val());
    formData.append('link', $(modal_id + ' #review_link').val());

    var fileInput = $(modal_id + ' #review_avatar_file')[0];
    if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.append('avatar_file', fileInput.files[0]);
    }

    $.ajax({
        type: "POST",
        url: api_url + '/reviews/create',
        dataType: 'json',
        contentType: false,
        processData: false,
        data: formData,
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $(modal_id + ' #review_author').val('');
                $(modal_id + ' #review_author_link').val('');
                $(modal_id + ' #review_text').val('');
                $(modal_id + ' #review_avatar').val('');
                $(modal_id + ' #review_avatar_file').val('');
                $(modal_id + ' #review_avatar_preview').hide();
                $(modal_id + ' #review_link').val('');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function changeReview() {
    let modal_id = '#changeReview';
    let id = $(modal_id).attr('data-id');
    let formData = new FormData();
    formData.append('author', $(modal_id + ' #review_author').val());
    formData.append('author_link', $(modal_id + ' #review_author_link').val());
    formData.append('text', $(modal_id + ' #review_text').val());
    formData.append('avatar', $(modal_id + ' #review_avatar').val());
    formData.append('link', $(modal_id + ' #review_link').val());

    var fileInput = $(modal_id + ' #review_avatar_file')[0];
    if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.append('avatar_file', fileInput.files[0]);
    }

    $.ajax({
        type: "POST",
        url: api_url + '/reviews/'+id+'/update',
        dataType: 'json',
        contentType: false,
        processData: false,
        data: formData,
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id).modal('hide');
                $(modal_id + ' #review_avatar_file').val('');
                $(modal_id + ' #review_avatar_preview').hide();
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function deleteReview(id) {
    if (confirm("Вы уверены, что хотите удалить отзыв?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/reviews/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}


function deleteCheat(id) {
    if (confirm("Вы уверены, что хотите удалить статус чита?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/statuses/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    statusesCheats();
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }
        });
    }
}

function deleteButton(id) {
    if (confirm("Вы уверены, что хотите удалить данную кнопку?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/buttons/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    constructor_info();
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}

function deleteSender(id) {
    if (confirm("Вы уверены, что хотите удалить данную рассылку?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/senders/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}

function deleteLink(id) {
    if (confirm("Вы уверены, что хотите удалить данную ссылку?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/links/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}


function deleteRole(id) {
    if (confirm("Вы уверены, что хотите удалить данную роль?")) {
        $.ajax({
            type: "DELETE",
            url: api_url + '/roles/' + id + '/delete',
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });
    }
}

function changeButtonSet(modal, type) {

    let modal_id = '#'+modal;

    if(type === 'count_columns') {
        data = {count_columns: $(modal_id + ' #count_columns').val()}
    }

    $.ajax({
        type: "POST",
        url: api_url + '/buttons/set/update',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(data),
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                constructor_info();
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function selectPaymentsAll() {
    return $.ajax({
        type: "POST",
        url: api_url + '/methods_payments/ps/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
    });
}


function checkTariff(id, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/tariffs/' + id + '/check',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            callback(data);
        }

    });
}

function remove(type, id) {

    var msg;
    var method;

    if (type === 'files') {
        method = '/attachments/'+id+'/delete';
        msg = "Вы уверены, что хотите удалить данный файл?";
    }
    if (type === 'product') {
        method = '/products/'+id+'/delete';
        msg = "Вы уверены, что хотите удалить данный товар?";
    }
    if (type === 'category') {
        method = '/categories/'+id+'/delete';
        msg = "Вы уверены, что хотите удалить данную категорию?";
    }
    if (type === 'coupon') {
        method = '/coupons/'+id+'/delete';
        msg = "Вы уверены, что хотите удалить данный купон?";
    }
    if (type === 'member') {
        method = '/members/'+id+'/delete';
        msg = "Вы уверены, что хотите удалить данного пользователя?";
    }
    if (type === 'material') {
        method = '/materials/'+id+'/delete';
        msg = "Вы уверены, что хотите удалить материал?";
    }

    if (confirm(msg)) {
        $.ajax({
            type: "DELETE",
            url: api_url + method,
            dataType: 'json',
            contentType: 'application/json',
            beforeSend: function (xhr, settings) {
                setAuthorization(xhr)
            },
            async: true,
            success: function (data) {
                if (data.ok === true) {
                    $('#datatable').DataTable().ajax.reload(null, false);
                    messageSystem(true, data.description, 2000);
                } else if (data.ok === false) {
                    messageSystem(false, data.description, 3000);
                }
            }

        });

    }
}

$('#addMaterial').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#addMaterial';

    $(modal_id + ' #body').val('');

    $.ajax({
        type: "GET",
        url: api_url + '/products/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var tariffs_html = '';
                $(data.result.tariffs).each(function(index, e) {
                    tariffs_html += '<option value="' + e.id + '">' + e.days_full + ' / ' + e.price_full + '</option>';
                });
                $(modal_id + ' #tid').html(tariffs_html);
                $(modal_id + ' #p_title').text(data.result.title);
                $(modal_id + ' #btn-save').attr('onclick', 'addMaterial(' + id + ');return false;');
                removeFile('#addMaterial');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})

$('#editRole').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    $(this).attr('data-id', id);

    roleInfo(id, function (data) {
        if (data.ok === true) {
            $('#editRole #title').val(data.result.title);
        }
    });

    permissionsList(id,function (data) {
        if (data.ok === true) {
            var roles = '';
            $(data.result).each(function(index, s) {
                var permissions = '';

                var count_allows = 0;

                $(s.permissions).each(function(index, e) {

                    var checked = '';

                    if (e.allow == 1){
                        count_allows += 1
                        checked = ' checked'
                    }

                    permissions += '' +
                        '<div class="custom-control custom-switch d-block" style="margin-left: 55px;margin-bottom: 10px;">\n' +
                        '    <input type="checkbox" class="custom-control-input d-block" onclick="setPermission('+id+',\''+e.permission+'\');" id="'+e.permission+'"'+checked+'>\n' +
                        '    <label class="custom-control-label" for="'+e.permission+'" style="margin-left: 20px;">'+e.title+'</label>\n' +
                        '</div>';
                });

                var checked_item = '';

                if (count_allows > 0){
                    checked_item = ' checked';
                }

                roles += '<hr /><div class="custom-control custom-switch d-block" style="margin-left: 35px;margin-bottom: 10px;">\n' +
                    '    <input type="checkbox" class="custom-control-input d-block" id="'+s.alias+'" onclick="setPermissionLike('+id+',\''+s.alias+'\');"'+checked_item+'>\n' +
                    '    <label class="custom-control-label font-weight-bold" for="'+s.alias+'" style="margin-left: 20px;">'+s.title+'</label>\n' +
                    '</div>\r\n'+permissions;
            });
            $('#editRole #permissions').html(roles);
        } else if (data.ok === false) {
            messageSystem(false, data.description, 3000);
        }
    });

})

$('#exportMaterials').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#exportMaterials';

    $(modal_id + ' #count').val('');
    $(modal_id + ' #remove_from_stock').prop('checked', false);
    $(modal_id + ' #block-response').removeClass('d-block').addClass('d-none');

    $.ajax({
        type: "GET",
        url: api_url + '/products/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var tariffs_html = '';
                $(data.result.tariffs).each(function(index, e) {
                    tariffs_html += '<option value="' + e.id + '">' + e.days_full + ' / ' + e.price_full + '</option>';
                });
                $(modal_id + ' #tid').html(tariffs_html);
                $(modal_id + ' #p_title').text(data.result.title);
                $(modal_id + ' #btn-save').attr('onclick', 'exportMaterials(' + id + ');return false;');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})

$('#sendMessage').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#sendMessage';

    $.ajax({
        type: "GET",
        url: api_url + '/members/' + id + '/info',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                var block_member = data.result.id;
                if(data.result.username != ''){
                    block_member = data.result.username;
                }

                $(modal_id + ' #m_user').html(block_member);
                $(modal_id + ' #btn-save').attr('onclick', 'sendMessage(' + id + ');return false;');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})


$('#addCategory').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#addCategory';

    $.ajax({
        type: "GET",
        url: api_url + '/categories/select/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var cats = data.result;

                var select_html = '<option value="0">Без категории</option>';
                $(cats).each(function(index, e) {
                    select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                });

                $(modal_id + ' #cid').html(select_html);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})

$('#editMember').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#editMember';

    $.ajax({
        type: "GET",
        url: api_url + '/members/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var block_member = data.result.id;
                if(data.result.username !== ''){
                    block_member = data.result.username;
                }

                var role_id = data.result.role_id;

                $(modal_id + ' #m_user').html(block_member);
                $(modal_id + ' #balance').text(data.result.balance_main);
                $(modal_id + ' #ref_percent').val(data.result.ref_percent);
                $(modal_id + ' #btn-minus').attr('onclick', 'editMemberBalance(0, ' + id + ');return false;');
                $(modal_id + ' #btn-plus').attr('onclick', 'editMemberBalance(1, ' + id + ');return false;');
                $(modal_id + ' #btn-save').attr('onclick', 'editMemberPercent(' + id + ');return false;');
                $(modal_id + ' #btn-save-role').attr('onclick', 'editMemberRole(' + id + ');return false;');

                selectRolesAll(role_id,function (data) {
                    if(data.ok === true){

                        var select_html = '';
                        if(role_id === 0){
                            select_html = '<option value="0" selected>Пользователь</option>';
                        } else {
                            select_html = '<option value="0">Пользователь</option>';
                        }

                        $(data.result).each(function(index, e) {
                            var selected = '';
                            if(role_id === e.id){
                                selected = ' selected';
                            }
                            select_html += '<option value="' + e.id + '"'+selected+'>' + e.title + '</option>';
                        });
                        $(modal_id + ' #role_id').html(select_html);
                    }
                })
                // setTimeout(function (){
                //     $(modal_id + ' #role_id').val(data.result.role_id);
                // }, 300);



            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})


$('#notifySettings').on('show.bs.modal', function(event) {
    let modal_id = '#'+$(this).attr('id');

    $.ajax({
        type: "GET",
        url: api_url + '/shops/settings/notify',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #notify_target_id').val(data.result.notify_target_id);

                if(data.result.tg_notify_buys == 1){
                    $(modal_id + ' #tg_notify_buys').prop('checked', true);
                }
                if(data.result.tg_notify_balance == 1){
                    $(modal_id + ' #tg_notify_balance').prop('checked', true);
                }
                if(data.result.tg_notify_users == 1){
                    $(modal_id + ' #tg_notify_users').prop('checked', true);
                }
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})



$('#modalBalance').on('show.bs.modal', function(event) {
    let modal_id = '#'+$(this).attr('id');

    $.ajax({
        type: "GET",
        url: api_url + '/shops/settings/topup',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #min_sum_topup').val(data.result.min_sum_topup);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})



$('#displayModal').on('show.bs.modal', function(event) {
    let modal_id = '#'+$(this).attr('id');

    $.ajax({
        type: "GET",
        url: api_url + '/shops/settings/display',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #display_products').val(data.result.display_products);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})



$('#refModal').on('show.bs.modal', function(event) {
    let modal_id = '#'+$(this).attr('id');

    $.ajax({
        type: "GET",
        url: api_url + '/shops/settings/referral',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #ref_percent').val(data.result.ref_percent);
                $(modal_id + ' #min_sum_withdrawal_card').val(data.result.min_sum_withdrawal_card);
                $(modal_id + ' #min_sum_withdrawal_balance').val(data.result.min_sum_withdrawal_balance);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})


$('#editCategory').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#editCategory';

    $.ajax({
        type: "GET",
        url: api_url + '/categories/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var cats = data.result.categories;

                var select_html = '<option value="0">Без категории</option>';
                $(cats).each(function(index, e) {
                    select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                });
                $(modal_id + ' #cid').html(select_html);
                $(modal_id + ' #title').val(data.result.title);
                $(modal_id + ' #seo_description').val(data.result.seo_description);
                $(modal_id + ' #seo_keywords').val(data.result.seo_keywords);
                $(modal_id + ' #cid').val(data.result.cid);
                $(modal_id + ' #text_message .ql-editor').html(data.result.description);
                $(modal_id + ' #count_column').val(data.result.count_column);
                $(modal_id + ' #visibility').val(data.result.visibility);
                $(modal_id + ' #display_products').val(data.result.display_products);
                $(modal_id + ' #alias').val(data.result.alias);

                removeImage(modal_id);

                if (data.result.image_site != '') {
                    $(modal_id + ' #image_site #block_upload_image').attr('data-attach', data.result.image_site);
                    $(modal_id + ' #image_site #block_upload_image').html('<img src="/' + data.result.image_site + '" />');
                    $(modal_id + ' #image_site #block_upload_image').removeClass('d-none');
                    $(modal_id + ' #image_site #btn_upload_image').addClass('d-none');
                    $(modal_id + ' #image_site #text_image_uploaded').removeClass('d-none');
                    $(modal_id + ' #image_site #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'' + modal_id + '\', \'#image_site\');"><i class="far fa-trash fa-xl"></i></a>');
                }

                if (data.result.image != '') {
                    $(modal_id + ' #image_bot #block_upload_image').attr('data-attach', data.result.image);
                    $(modal_id + ' #image_bot #block_upload_image').html('<img src="/' + data.result.image + '" />');
                    $(modal_id + ' #image_bot #block_upload_image').removeClass('d-none');
                    $(modal_id + ' #image_bot #btn_upload_image').addClass('d-none');
                    $(modal_id + ' #image_bot #text_image_uploaded').removeClass('d-none');
                    $(modal_id + ' #image_bot #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'' + modal_id + '\', \'#image_bot\');"><i class="far fa-trash fa-xl"></i></a>');
                    $(modal_id + ' #ec_has_spoiler').prop("disabled", false);
                }

                if (data.result.disable_web_page_preview == 1) {
                    $(modal_id + ' #ec_disable_web_page_preview').prop('checked', true);
                }
                if (data.result.has_spoiler == 1) {
                    $(modal_id + ' #ec_has_spoiler').prop('checked', true);
                }

                $(modal_id + ' #btn-save').attr('onclick', 'editCategory(' + id + ');return false;');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})

function clearInfo(modal_id){
    document.querySelectorAll('#editSender input, #editSender textarea, #editSender select').forEach(function(el) {
        if (el.tagName === 'INPUT') {
            if (el.type === 'text' || el.type === 'password' || el.type === 'email' || el.type === 'search' || el.type === 'tel' || el.type === 'url') {
                el.value = '';
            } else if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = false;
            } else if (el.type === 'number') {
                el.value = '';
            }
        } else if (el.tagName === 'TEXTAREA') {
            el.value = '';
        } else if (el.tagName === 'SELECT') {
            el.selectedIndex = 0;
        }
    });
}

function selectAll(type, body, callback){
    $.ajax({
        type: "GET",
        url: api_url + '/'+type+'/select/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            callback(data);
        }
    });
}

$('#editSender').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#editSender';


    $.ajax({
        type: "GET",
        url: api_url + '/senders/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #title').val(data.result.title);
                $(modal_id + ' #text_sender .ql-editor').html(data.result.message);
                $(modal_id + ' #forward_link').val(data.result.forward_link);
                $(modal_id + ' #type_time').val(1);
                $(modal_id + ' #started_at').removeClass('d-none');

                var num = 0;

                $(modal_id + ' #blocks_buttons').html('');

                $(data.result.buttons).each(function(index, e) {
                    addBlockButtonCustom('editSender', num, e.text, e.url, e.callback_data)
                    num++;
                });

                const timestamp = data.result.started_at;
                const date = new Date(timestamp);

                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                const hours = date.getHours().toString().padStart(2, '0');
                const minutes = date.getMinutes().toString().padStart(2, '0');

                console.log(`${day}.${month} ${hours}:${minutes}`)

                $(modal_id + ' #date_day').val(day);
                $(modal_id + ' #date_month').val(month);
                $(modal_id + ' #date_hours').val(hours);
                $(modal_id + ' #date_minutes').val(minutes);

                removeImage(modal_id);

                if (data.result.image != '') {
                    $(modal_id + ' #block_upload_image').attr('data-attach', data.result.image);
                    $(modal_id + ' #block_upload_image').html('<img src="/' + data.result.image + '" />');
                    $(modal_id + ' #block_upload_image').removeClass('d-none');
                    $(modal_id + ' #btn_upload_image').addClass('d-none');
                    $(modal_id + ' #text_image_uploaded').removeClass('d-none');
                    $(modal_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'' + modal_id + '\');"><i class="far fa-trash fa-xl"></i></a>');
                    $(modal_id + ' #es_has_spoiler').prop("disabled", false);
                } else {
                    $(modal_id + ' #es_has_spoiler').attr('disabled', 'disabled');
                }

                if (data.result.disable_web_page_preview == 1) {
                    $(modal_id + ' #es_disable_web_page_preview').prop('checked', true);
                }
                if (data.result.has_spoiler == 1) {
                    $(modal_id + ' #es_has_spoiler').prop('checked', true);
                }

                $(modal_id + ' #btn-save').attr('onclick', 'saveSender(' + id + ');return false;');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})
$('#editChannel').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#editChannel';

    $.ajax({
        type: "GET",
        url: api_url + '/channels/sub/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #title_header').val(data.result.title);
                $(modal_id + ' #channel_title').val(data.result.title);
                $(modal_id + ' #channel_link').val(data.result.link);
                $(modal_id + ' #btn-save').attr('onclick', 'checkSaveChannel("editChannel",' + id + ');return false;');
                if (data.result.is_active == 1) {$(modal_id + ' #ec_is_active').prop('checked', true);}
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})

$('#editButtonCheck').on('show.bs.modal', function(event) {
    let modal_id = '#editButtonCheck';

    $.ajax({
        type: "GET",
        url: api_url + '/channels/sub/settings/button_check/info',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #button_check').val(data.result);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})

$('#editButton').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#editButton';

    $.ajax({
        type: "GET",
        url: api_url + '/buttons/'+id+'/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #title').val(data.result.title);
                $(modal_id + ' #text_message .ql-editor').html(data.result.text);
                $(modal_id + ' #eb_disable_web_page_preview').val(data.result.disable_web_page_preview);
                $(modal_id + ' #eb_has_spoiler').val(data.result.image_spoiler);
                $(modal_id + ' #visible').val(data.result.visible);
                $(modal_id + ' #btn-save').attr('onclick', 'saveButton("editButton",' + id + ');return false;');

                var num = 0;

                $(modal_id + ' #blocks_buttons').html('');

                $(data.result.buttons).each(function(index, e) {
                    addBlockButtonCustom('editButton', num, e.text, e.url, e.callback_data)
                    num++;
                });

                removeImage(modal_id);

                if (data.result.image != '') {
                    $(modal_id + ' #block_upload_image').attr('data-attach', data.result.image);
                    $(modal_id + ' #block_upload_image').html('<img src="/' + data.result.image + '" />');
                    $(modal_id + ' #block_upload_image').removeClass('d-none');
                    $(modal_id + ' #btn_upload_image').addClass('d-none');
                    $(modal_id + ' #text_image_uploaded').removeClass('d-none');
                    $(modal_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'' + modal_id + '\');"><i class="far fa-trash fa-xl"></i></a>');
                    $(modal_id + ' #eb_has_spoiler').prop("disabled", false);
                } else {
                    $(modal_id + ' #eb_has_spoiler').attr('disabled', 'disabled');
                }

            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})


$('#editAsset').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var type = button.data('type');
    var id = button.data('id');
    var title = button.data('title');
    let modal_id = '#editAsset';

    $(modal_id + ' #title').text(title);
    $('#editMethod').modal('hide');

    fullinfo('payment_assets', id).done(function(data) {
        if (data.ok === true) {
            $(modal_id + ' #min').val(data.result.min);
            $(modal_id + ' #max').val(data.result.max);
            $(modal_id + ' #btn-save').attr('onclick', 'saveAsset('+id+')');
        }
    });
});


$('#editMethod').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var type = button.data('type');
    var title = button.data('title');
    let modal_id = '#editMethod';

    $(modal_id + ' #title').text(title);
    $("div[id^='method_']").not("#method_"+type).addClass("d-none");
    $(modal_id + ' #method_'+type).removeClass('d-none');
    $(modal_id + ' #btn-save').attr('onclick', 'saveMethod("info", "'+type+'");return false;');

    exists('methods_payments', type).done(function(data) {
        if(data.ok === true) {
            fullinfo('methods_payments', type).done(function(data) {
                if(data.ok === true) {
                    if(type === 'qw'){
                        $(modal_id + ' #method_' + type + ' #block_phone').addClass('d-none');
                        $(modal_id + ' #method_' + type + ' #block_keys').removeClass('d-none');
                        $(modal_id + ' #method_' + type + ' #public_key').val(data.result.public_key);
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                        $(modal_id + ' #method_' + type + ' #theme_code').val(data.result.theme_code);
                    }
                    if(type === 'et' || type === 'ai'){
                        $(modal_id + ' #method_' + type + ' #public_id').val(data.result.public_id);
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                        $(modal_id + ' #method_' + type + ' #secret_key_two').val(data.result.secret_key_two);
                    }
                    if(type === 'cp'){
                        $(modal_id + ' #method_' + type + ' #public_id').val(data.result.public_id);
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                        $(modal_id + ' #method_' + type + ' #secret_key_two').val(data.result.secret_key_two);
                    }
                    if(type === 'fk'){
                        $(modal_id + ' #method_' + type + ' #public_id').val(data.result.public_id);
                        $(modal_id + ' #method_' + type + ' #public_key').val(data.result.public_key);
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                        $(modal_id + ' #method_' + type + ' #secret_key_two').val(data.result.secret_key_two);
                    }
                    if(type === 'ym' || type === 'lv' || type === 'ap' || type === 'rk' || type === 'po'){
                        $(modal_id + ' #method_' + type + ' #public_id').val(data.result.public_id);
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                    }
                    if(type === 'pp'){
                        $(modal_id + ' #method_' + type + ' #public_id').val(data.result.public_id);
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                    }
                    if(type === 'bt' || type === 'bn' || type === 'sp') {
                        $(modal_id + ' #method_' + type + ' #public_key').val(data.result.public_key);
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                    }
                    if(type === 'sm'){
                        $(modal_id + ' #method_' + type + ' #public_id').val(data.result.public_id);
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                    }
                    if(type === 'cb'){
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);

                        // Reset all CryptoBot checkboxes first
                        $(modal_id + ' #method_' + type + ' [id^="cb_"]').prop('checked', false);

                        $(data.result.assets).each(function(index, e) {
                            $(modal_id + ' #method_' + type + ' #cb_'+e).prop('checked', true);
                        })
                    }
                    if(type === 'ts'){
                        $(modal_id + ' #method_' + type + ' #secret_key').val(data.result.secret_key);
                        $(modal_id + ' #method_' + type + ' #secret_key_two').val(data.result.secret_key_two);
                    }
                    if(type !== 'cb' && type !== 'sm' && type !== 'ts'){
                        payment_systems_by_psid(data.result.id, function (data) {
                            if (data.ok === true) {
                                var assets_html = '';

                                $(data.result).each(function (index, e) {
                                    var block_checked = '';
                                    if (e.is_active == 1){
                                        block_checked = ' checked';
                                    }
                                    assets_html += '<div class="col-md-12 my-2"><li class="d-flex justify-content-between align-items-center p-4" style="border: 2px solid #222933;border-radius: 15px"><h6 class="mb-0 font-weight-bold">'+e.title+'</h6><div><i class="far fa-pencil-alt ms-auto cursor-pointer mr-3" style="color:#5c5c5c" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" aria-label="Редактировать метод" data-toggle="modal" data-title="'+e.title+'" data-id="'+e.id+'"  data-target="#editAsset"></i><span class="sr-only">Редактировать</span><div class="custom-control custom-switch" style="margin-left: 40px;float: right"><input type="checkbox" class="custom-control-input" onclick="saveAssetActive('+e.id+');" id="asset_'+e.id+'"'+block_checked+'><label class="custom-control-label" for="asset_'+e.id+'"></label></div></div></li></div>';
                                });

                                $(modal_id + ' #method_' + type + ' #assets').html(assets_html);
                            }
                        })
                    }
                }
            });
        } else {
            if(type === 'qw'){
                $(modal_id + ' #method_' + type + ' #block_phone').removeClass('d-none');
                $(modal_id + ' #method_' + type + ' #block_keys').addClass('d-none');
            }
        }
    })
})

$('#newCoupon').on('show.bs.modal', function(event) {
    let modal_id = '#newCoupon';

    $.ajax({
        type: "GET",
        url: api_url + '/products/select/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var products = data.result;

                var select_html = '';
                $(products).each(function(index, e) {
                    select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                });

                $(modal_id + ' #gids').html(select_html).selectpicker('refresh');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})


$('#createInstruction').on('show.bs.modal', function(event) {
    let modal_id = '#createInstruction';
    $.ajax({
        type: "GET",
        url: api_url + '/products/select/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var products = data.result;

                var select_html = '';
                $(products).each(function(index, e) {
                    select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                });

                $(modal_id + ' #pids').html(select_html).selectpicker('refresh');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})

$('#editCoupon').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#editCoupon';

    $.ajax({
        type: "GET",
        url: api_url + '/coupons/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var products = data.result.products;

                var select_html = '';
                $(products).each(function(index, e) {
                    select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                });
                $(modal_id + ' #gids').html(select_html).selectpicker('refresh').selectpicker('val', data.result.gids);
                $(modal_id + ' #code').val(data.result.code);
                $(modal_id + ' #sale').val(data.result.sale);
                $(modal_id + ' #sale_type').val(data.result.sale_type);
                $(modal_id + ' #min_sum').val(data.result.min_sum);
                $(modal_id + ' #count_uses_min').val(data.result.count_uses_min);
                $(modal_id + ' #count_uses_type').val(data.result.count_uses_type);
                $(modal_id + ' #count_uses_max').val(data.result.count_uses_max);

                // if (data.result.count_expired > 0) {
                //     $(modal_id + ' #count_expired').val(data.result.count_expired);
                //     $(modal_id + ' #count_expired_type').val(data.result.count_expired_type);
                // }
                if (data.result.is_new_users == 1) {
                    $(modal_id + ' #edit_is_new_users').prop('checked', true);
                }
                if (data.result.is_one_time == 1) {
                    $(modal_id + ' #edit_is_one_time').prop('checked', true);
                }

                $(modal_id + ' #btn-save').attr('onclick', 'changeCoupon(' + id + ');return false;');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
})


$('#getOrder').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#getOrder';

    $.ajax({
        type: "GET",
        url: api_url + '/orders/'+id+'/get',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #result-body').html(data.result.body);
                $(modal_id + ' #o_id').text(data.result.id);
                $(modal_id + ' #btn-download').attr('onclick', 'downloadOrder(' + id + ');return false;');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})

function cancelOrder(id) {
    if(!confirm('Вы уверены, что хотите отменить заказ #' + id + '?')) return;
    $.ajax({
        type: "GET",
        url: api_url + '/orders/' + id + '/cancel',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if(data.ok === true) {
                messageSystem(true, data.description, 3000);
                $('#datatable').DataTable().ajax.reload(null, false);
            } else {
                messageSystem(false, data.description || 'Ошибка при отмене', 3000);
            }
        }
    });
}

$('#createFaq').on('show.bs.modal', function (event) {
    let modal_id = '#'+$(this).attr('id');
    instructionsList(0,function (data) {
        if(data.ok === true) {
            var select_html = '<option value="0">По умолчанию</option>';
            $(data.result).each(function(index, e) {
                select_html += '<option value="' + e.id + '">' + e.title + '</option>';
            });

            $(modal_id + ' #in_id').html(select_html);
        }
    });
})

$('#changeFaq').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#'+$(this).attr('id');
    $(this).attr('data-id', id);

    $.ajax({
        type: "GET",
        url: api_url + '/faq/'+id+'/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                let in_id = data.result.in_id;
                instructionsList(in_id, function (data) {
                   if(data.ok === true) {
                       var select_html = '<option value="0">По умолчанию</option>';
                       $(data.result).each(function(index, e) {
                           select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                       });

                       $(modal_id + ' #in_id').html(select_html);
                       $(modal_id + ' #in_id').val(in_id);
                   }
                });
                $(modal_id + ' #question').val(data.result.question);
                $(modal_id + ' #text_answer .ql-editor').html(data.result.answer);
                $(modal_id + ' #visibility').val(data.result.visibility);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})


$('#changeReview').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#'+$(this).attr('id');
    $(this).attr('data-id', id);

    $.ajax({
        type: "GET",
        url: api_url + '/reviews/'+id+'/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $(modal_id + ' #review_author').val(data.result.author);
                $(modal_id + ' #review_author_link').val(data.result.author_link);
                $(modal_id + ' #review_text').val(data.result.text);
                $(modal_id + ' #review_avatar').val(data.result.avatar);
                $(modal_id + ' #review_link').val(data.result.link);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})


$('#changeCheat').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#'+$(this).attr('id');
    $(this).attr('data-id', id);

    $.ajax({
        type: "GET",
        url: api_url + '/statuses/'+id+'/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var games = data.result.games;

                var select_html = '<option value="0">Не выбрана</option>';
                $(games).each(function(index, e) {
                    select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                });

                $(modal_id + ' #game_id').html(select_html);
                $(modal_id + ' #game_id').val(data.result.cid);
                $(modal_id + ' #title').val(data.result.title);
                $(modal_id + ' #status').val(data.result.status);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})


$('#createCheat').on('show.bs.modal', function(event) {
    let modal_id = '#'+$(this).attr('id');
    $.ajax({
        type: "GET",
        url: api_url + '/statuses/games/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var games = data.result;

                var select_html = '<option value="0">Не выбрана</option>';
                $(games).each(function(index, e) {
                    select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                });

                $(modal_id + ' #game_id').html(select_html);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})

$('#changeInstruction').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#'+$(this).attr('id');
    $(this).attr('data-id', id);

    $.ajax({
        type: "GET",
        url: api_url + '/instructions/'+id+'/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                var buttons = '';
                var num = 0;
                var products = data.result.products;
                var select_html = '';

                $(products).each(function(index, e) {
                    select_html += '<option value="' + e.id + '">' + e.title + '</option>';
                });
                $(modal_id + ' #pids').html(select_html).selectpicker('refresh').selectpicker('val', JSON.parse(data.result.pids));
                $(modal_id + ' #title').val(data.result.title);
                $(modal_id + ' #alias').val(data.result.alias);
                $(modal_id + ' #text_instruction .ql-editor').html(data.result.body);

                $(JSON.parse(data.result.buttons)).each(function(index, e) {
                    buttons += '<div class="row" id="block_button_item" data-id="'+index+'"><div class="col-6"><div class="mb-3"><input type="text" class="form-control" id="button_title" placeholder="Название" value="'+e.text+'"></div></div><div class="col-5"><div class="mb-3"><input type="text" class="form-control" id="button_link" placeholder="Ссылка" value="'+e.url+'"></div></div><div class="col-1"><i class="far fa-trash fa-xl text-danger" style="cursor:pointer;margin: 13px -7px;" onclick="deleteBlockButton('+index+',\'changeInstruction\');return false;"></i></div></div>';
                    num++
                });

                $(modal_id +' #blocks_buttons').html(buttons);

            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})



function ticketMessagesByID(type) {

    let id = $('#chatTicket').attr('data-id');
    let modal_id = '#chatTicket';

    var url = '';

    if(type == 'all'){url = '/tickets/'+id+'/messages';}
    if(type == 'new'){url = '/tickets/'+id+'/last/messages';}

    $.ajax({
        type: "GET",
        url: api_url + url,
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                var messages_html = '';

                $(data.result).each(function(index, e) {

                    if (e.user_id == 0){var style = '1D2533';}
                    if (e.user_id > 0){var style = '263042';}

                    var block_image = '';
                    var block_message = '';

                    if(e.image != ''){
                        block_image = '<a target="_blank" class="mb-2 mt-1" style="border-radius: 16px;display:block;width:250px;overflow: hidden" href="/i'+e.image+'"><img width="250" src="/i'+e.image+'" /></a>';
                    }

                    if(e.message != ''){
                        block_message = '<p class="mb-0">'+e.message+'</p>';
                    }

                    messages_html += '<div class="list-group-item list-group-item-action p-3 mb-2" style="background-color: #'+style+'">\n' +
                        '                            <div class="d-flex w-100 justify-content-between">\n' +
                        '                                <h6 class="mb-1 font-weight-bold">'+e.user+'</h6>\n' +
                        '                                <small>'+e.created_at+'</small>\n' +
                        '                            </div>\n' + block_image + block_message +
                        '                        </div>';

                    if(type == 'new') {
                        $(modal_id + ' #dialog').append(messages_html);
                        // playNotifyNewMessage();
                        // $('#chatAudio').remove();
                        lastMessageScroll();
                    }
                });

                if(type == 'all') {
                    $(modal_id + ' #dialog').html(messages_html);
                }


            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}


function ticketCreateByID() {
    let modal_id = '#chatTicket';
    let id = $(modal_id).attr('data-id');
    let message = $(modal_id + ' #text_ticket').val();
    let image = $(modal_id + ' #block_image_uploaded').attr('data-attach');

    $(modal_id + ' #btn_ticket_create').prop("disabled", true);

    $.ajax({
        type: "POST",
        url: api_url + '/tickets/' + id + '/create',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        data: JSON.stringify({
            ticket_id: id,
            message: message,
            image: image
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#chatTicket #text_ticket').val('');
                $('#chatTicket #block_image_uploaded').html('');
                $('#chatTicket #block_image_uploaded').attr('data-attach', '');
                ticketMessagesByID('all');
                getStatsCounter();
                $('#datatable').DataTable().ajax.reload(null, false);

                setTimeout(function () {
                    lastMessageScroll();
                }, 300);

                $(modal_id + ' #btn_ticket_create').prop("disabled", false);


            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
                $(modal_id + ' #btn_ticket_create').prop("disabled", false);
            }
        }

    });

}

function getStatsCounter() {
    $.ajax({
        type: "GET",
        url: api_url + '/stats/counter',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function (xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function (data) {
            if (data.ok === true) {
                if (data.result.new_tickets > 0) {
                    // $('#badge-tickets').removeClass('d-none');
                    $('#badge-tickets').text(data.result.new_tickets);

                    if(path_b == 'tickets') {
                        $('#datatable').DataTable().ajax.reload(null, false);
                    }
                } else {
                    $('#badge-tickets').addClass('d-none');
                    $('#badge-tickets').text('0');
                }
                if (data.result.new_withdrawals > 0) {
                    // $('#badge-withdrawals').removeClass('d-none');
                    $('#badge-withdrawals').text(data.result.new_withdrawals);
                    if(path_b == 'withdrawals') {
                        $('#datatable').DataTable().ajax.reload(null, false);
                    }
                } else {
                    $('#badge-withdrawals').addClass('d-none');
                    $('#badge-withdrawals').text('0');
                }
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }

        }
    });
}


function ticketCloseByID() {
    let modal_id = '#chatTicket'; 
    let id = $(modal_id).attr('data-id');
    $(modal_id + ' #btn_ticket_close').prop("disabled", true);

    $.ajax({
        type: "POST",
        url: api_url + '/tickets/' + id + '/close',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#chatTicket #block_ticket_text').hide();
                $('#chatTicket #btns_block').hide();
                $('#datatable').DataTable().ajax.reload(null, false);
                getStatsCounter();
                $(modal_id + ' #btn_ticket_close').prop("disabled", false);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
                $(modal_id + ' #btn_ticket_close').prop("disabled", false);
            }
        }

    });

}


function startUpdatesMessages() {
    intervalId = setInterval(function (){ticketMessagesByID('new');}, 1000);
}

function stopUpdatesMessages() {
    clearInterval(intervalId);
}

$('#chatTicket').on('hidden.bs.modal', function(event) {
    stopUpdatesMessages();
});

$('#chatTicket').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#'+$(this).attr('id');
    $(this).attr('data-id', id);

    $.ajax({
        type: "GET",
        url: api_url + '/tickets/'+id+'/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                if (data.result.status == 1){
                    $('#chatTicket #block_ticket_text').hide();
                    $('#chatTicket #btns_block').hide();
                } else {
                    $('#chatTicket #block_ticket_text').show();
                    $('#chatTicket #btns_block').show();
                    ticketMessagesByID('all');
                    startUpdatesMessages();
                }

                var messages_html = '';

                $(data.result.messages).each(function(index, e) {

                    if (e.user_id == 0){var style = '1D2533';}
                    if (e.user_id > 0){var style = '263042';}

                    messages_html += '<div class="list-group-item list-group-item-action p-3 mb-2" style="background-color: #'+style+'">\n' +
                        '                            <div class="d-flex w-100 justify-content-between">\n' +
                        '                                <h6 class="mb-1 font-weight-bold">'+e.user+'</h6>\n' +
                        '                                <small>'+e.created_at+'</small>\n' +
                        '                            </div>\n' +
                        '                            <p class="mb-0">'+e.message+'</p>\n' +
                        '                        </div>';
                });

                $(modal_id + ' #dialog_user').text(data.result.user);
                $(modal_id + ' #dialog_subject').text(data.result.subject_title);
                $(modal_id + ' #dialog').html(messages_html);

                setTimeout(function () {
                    lastMessageScroll();
                }, 300);

            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})

function cat_select_all(cid, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/categories/select/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                callback(data)
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });
}

$('#addProduct').on('shown.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#addProduct';

    selectAll('categories', '', function (data) {
        if (data.ok === true) {
            var cats = data.result;

            var select_html = '';
            $(cats).each(function(index, e) {
                select_html += '<option value="' + e.id + '">' + e.title + '</option>';
            });

            $(modal_id + ' #cid').html(select_html);
        } else if (data.ok === false) {
            messageSystem(false, data.description, 3000);
        }
    });

    selectAllStatuses().done(function (data) {
        if(data.ok === true){
            var statuses = data.result;

            var select_html = '<option value="0">Не выбран</option>';

            $(statuses).each(function(index, e) {
                select_html += '<option value="' + e.id + '">' + e.title + '</option>';
            });

            $(modal_id + ' #status').html(select_html);
        }
    });

})

function createQuillIfVisible(selector, quillOptions) {
    var element = document.querySelector(selector);

    if (element) {
        var styles = window.getComputedStyle(element);
        var isVisible = styles.display !== 'none' && styles.visibility !== 'hidden';

        if (isVisible) {
            new Quill(element, quillOptions);
        }
    }
}

function createDropzoneIfVisible(selector, dropzoneOptions) {
    var element = document.querySelector(selector);

    if (element) {
        var styles = window.getComputedStyle(element);
        var isVisible = styles.display !== 'none' && styles.visibility !== 'hidden';

        if (isVisible) {
            new Dropzone(element, dropzoneOptions);
        }
    }
}


function loadDropzone(type){

    Dropzone.autoDiscover = false;

    var api_url = "/api"; // Replace with your API endpoint
    var dropzoneOptions = {
        dictDefaultMessage: '<i class="far fa-upload mr-1" style="font-size:15px"></i> Выберите изображения',
        url:  api_url + "/attachments/image/upload",
        addRemoveLinks: true,
        maxFilesize: 5,
        acceptedFiles: ".png, .jpg, .gif",
        headers: {'Authorization': 'Bearer ' + getCookie('session_token')},
        init: function() {
            this.on('success', function(file, resp) {
                file.previewElement.setAttribute('data-id', resp.result.id);
            });
            this.on('error', function(file, errorMessage) {
                alert('Error uploading file: ' + errorMessage);
            });
        }
    };

    new Dropzone(document.querySelector(type+' #uploader'), dropzoneOptions);
}

$('#editProduct').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#editProduct';
    let prefix = 'ep_';

    $.ajax({
        type: "GET",
        url: api_url + '/products/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                var status_id = data.result.status_id;

                var cid = data.result.cid;

                selectAllStatuses().done(function (data) {
                    if(data.ok === true){
                        var statuses = data.result;

                        var select_html = '<option value="0">Не выбран</option>';

                        $(statuses).each(function(index, e) {
                            var selected = '';
                            if(e.id === status_id){selected = ' selected';}
                            select_html += '<option value="' + e.id + '"'+selected+'>' + e.title + '</option>';
                        });

                        $(modal_id + ' #status').html(select_html);
                    }
                });

                cat_select_all(cid, function (data){
                    var cats = data.result;

                    var select_html = '';
                    $(cats).each(function(index, e) {
                        var selected = '';
                        if(e.id === cid){selected = ' selected';}
                        select_html += '<option value="' + e.id + '"'+selected+'>' + e.title + '</option>';
                    });

                    $(modal_id + ' #cid').html(select_html);

                });


                var tariffs_html = '';
                $(data.result.tariffs).each(function(index, e) {
                    tariffs_html += '<div class="row" id="block_tariff_item" data-id="'+e.id+'" data-num="'+index+'"><div class="col-6"><div class="mb-3"><input type="number" class="form-control" id="days" placeholder="Кол-во дней" value="'+e.days+'" disabled></div></div><div class="col-5"><div class="mb-3"><input type="text" class="form-control" id="price" placeholder="Цена" value="'+e.price+'"></div></div><div class="col-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;position: relative;top: 7px;" onclick="deleteBlockTariff('+index+',\'editProduct\', '+e.id+');return false;"></i></div></div>';
                });

                var functional_html = '';
                $(data.result.functional).each(function(index, e) {
                    functional_html += '<div class="row" id="block_functional_item" data-id="'+index+'"><div class="col-11"><div class="mb-3"><textarea class="form-control" id="functional" placeholder="Напишите информацию" rows="3">'+e.title+'\n'+e.lines.join("\n")+'</textarea></div></div><div class="col-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;position: relative;top: 7px;" onclick="deleteBlockFunctional('+index+',\'editProduct\');return false;"></i></div></div>';
                });

                var advantages = data.result.advantages;

                $(modal_id + ' #blocks_functional').html(functional_html);
                $(modal_id + ' #blocks_tariffs').html(tariffs_html);
                $(modal_id + ' #title').val(data.result.title);
                $(modal_id + ' #seo_description').val(data.result.seo_description);
                $(modal_id + ' #seo_keywords').val(data.result.seo_keywords);
                $(modal_id + ' #cid').val(data.result.cid);
                if (window.descEditQuill) {
                    window.descEditQuill.root.innerHTML = data.result.description || '';
                }
                $(modal_id + ' #advantages').val(advantages.join("\n"));
                $(modal_id + ' #system_versions').val(data.result.system_versions);
                $(modal_id + ' #system_auth').val(data.result.system_auth);
                $(modal_id + ' #link_video').val(data.result.link_video);
                $(modal_id + ' #status').val(data.result.status_id);
                $(modal_id + ' #hack_status').val(data.result.hack_status);
                $(modal_id + ' #alias').val(data.result.alias);
                $(modal_id + ' #visibility').val(data.result.visibility);

                var previews = document.querySelectorAll(modal_id + ' .dz-preview');

                previews.forEach(function(preview) {
                    preview.remove();
                });

                $(modal_id + ' .dropzone .dz-message').removeClass('d-none');

                if(data.result.gallery.length > 0) {
                    $(modal_id + ' .dropzone .dz-message').addClass('d-none');
                    $(data.result.gallery).each(function (index, e) {
                        $(modal_id + ' .dropzone').append('<div class="dz-preview dz-processing dz-image-preview dz-success dz-complete" data-id="' + e + '"> <div class="dz-image"><img data-dz-thumbnail="" src="/' + e + '"></div> <div class="dz-progress"> <span class="dz-upload" data-dz-uploadprogress="" style="width: 100%;"></span> </div> <div class="dz-error-message"><span data-dz-errormessage=""></span></div> <a class="dz-remove" href="javascript:;" onclick="removeImg(\'#editProduct\', \'' + e + '\')" data-dz-remove="">Remove file</a></div>');
                    });
                }

                removeImage(modal_id);

                if (data.result.image_site != '') {
                    $(modal_id + ' #image_site #block_upload_image').attr('data-attach', data.result.image_site);
                    $(modal_id + ' #image_site #block_upload_image').html('<img src="/' + data.result.image_site + '" />');
                    $(modal_id + ' #image_site #block_upload_image').removeClass('d-none');
                    $(modal_id + ' #image_site #btn_upload_image').addClass('d-none');
                    $(modal_id + ' #image_site #text_image_uploaded').removeClass('d-none');
                    $(modal_id + ' #image_site #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'' + modal_id + '\', \'#image_site\');"><i class="far fa-trash fa-xl"></i></a>');
                }

                if (data.result.image != '') {
                    $(modal_id + ' #image_bot #block_upload_image').attr('data-attach', data.result.image);
                    $(modal_id + ' #image_bot #block_upload_image').html('<img src="/' + data.result.image + '" />');
                    $(modal_id + ' #image_bot #block_upload_image').removeClass('d-none');
                    $(modal_id + ' #image_bot #btn_upload_image').addClass('d-none');
                    $(modal_id + ' #image_bot #text_image_uploaded').removeClass('d-none');
                    $(modal_id + ' #image_bot #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'' + modal_id + '\', \'#image_bot\');"><i class="far fa-trash fa-xl"></i></a>');
                    $(modal_id + ' #'+prefix+'has_spoiler').prop("disabled", false);
                } else {
                    $(modal_id + ' #'+prefix+'has_spoiler').attr('disabled', 'disabled');
                }

                if (data.result.count_max == 1) {
                    $(modal_id + ' #'+prefix+'edit_count_max').prop('checked', true);
                }
                if (data.result.disable_web_page_preview == 1) {
                    $(modal_id + ' #'+prefix+'disable_web_page_preview').prop('checked', true);
                }
                if (data.result.has_spoiler == 1) {
                    $(modal_id + ' #'+prefix+'has_spoiler').prop('checked', true);
                }

                $(modal_id + ' #btn-save').attr('onclick', 'editProduct(' + id + ');return false;');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        },
    });

})


$('#editPage').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    let modal_id = '#editPage';

    $.ajax({
        type: "GET",
        url: api_url + '/pages/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                $(modal_id + ' #title').val(data.result.title);
                $(modal_id + ' #meta_description').val(data.result.meta_description);
                $(modal_id + ' #meta_keywords').val(data.result.meta_keywords);
                $(modal_id + ' #text_message .ql-editor').html(data.result.text);
                $(modal_id + ' #shortname').val(data.result.shortname);
                $(modal_id + ' #visibility').val(data.result.visibility);

                removeImage(modal_id);

                if (data.result.image != '') {
                    $(modal_id + ' #block_upload_image').attr('data-attach', data.result.image);
                    $(modal_id + ' #block_upload_image').html('<img src="/' + data.result.image + '" />');
                    $(modal_id + ' #block_upload_image').removeClass('d-none');
                    $(modal_id + ' #btn_upload_image').addClass('d-none');
                    $(modal_id + ' #text_image_uploaded').removeClass('d-none');
                    $(modal_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'' + modal_id + '\');"><i class="far fa-trash fa-xl"></i></a>');
                    $(modal_id + ' #ep_has_spoiler').prop("disabled", false);
                } else {
                    $(modal_id + ' #ep_has_spoiler').attr('disabled', 'disabled');
                }

                $(modal_id + ' #btn-save').attr('onclick', 'editPage(' + id + ');return false;');
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

})

function removeImageTicket(modal_id) {
    $(modal_id + ' #block_image_uploaded').attr('data-attach', '');
    $(modal_id + ' #block_image_uploaded').html('');
    $(modal_id + ' #block_image_uploaded').addClass('d-none');
    $(modal_id + ' #btn_upload_image').removeClass('d-none');
    $(modal_id + ' #btn_delete_image').html('');
}

function removeImage(modal_id, block_id = '') {
    $(modal_id + ' ' + block_id + ' #block_upload_image').attr('data-attach', '');
    $(modal_id + ' ' + block_id + ' #block_upload_image').html('');
    $(modal_id + ' ' + block_id + ' #text_image_uploaded').addClass('d-none');
    $(modal_id + ' ' + block_id + ' #block_upload_image').addClass('d-none');
    $(modal_id + ' ' + block_id + ' #btn_upload_image').removeClass('d-none');
    $(modal_id + ' ' + block_id + ' #btn_delete_image').html('');

    if(modal_id == '#addButton'){
        $(modal_id + ' #nb_has_spoiler').attr('disabled', 'disabled');
        $(modal_id + ' #nb_has_spoiler').prop("checked", false);
    }

    if(modal_id == '#editButton'){
        $(modal_id + ' #eb_has_spoiler').attr('disabled', 'disabled');
        $(modal_id + ' #eb_has_spoiler').prop("checked", false);
    }

    if(modal_id == '#addProduct'){
        $(modal_id + ' #np_has_spoiler').attr('disabled', 'disabled');
        $(modal_id + ' #np_has_spoiler').prop("checked", false);
    }

    if(modal_id == '#editProduct'){
        $(modal_id + ' #ep_has_spoiler').attr('disabled', 'disabled');
        $(modal_id + ' #ep_has_spoiler').prop("checked", false);
    }

    if(modal_id == '#addCategory'){
        $(modal_id + ' #nc_has_spoiler').attr('disabled', 'disabled');
        $(modal_id + ' #nc_has_spoiler').prop("checked", false);
    }

    if(modal_id == '#editCategory'){
        $(modal_id + ' #ec_has_spoiler').attr('disabled', 'disabled');
        $(modal_id + ' #ec_has_spoiler').prop("checked", false);
    }

    if(modal_id == '#createSender'){
        $(modal_id + ' #cs_has_spoiler').attr('disabled', 'disabled');
        $(modal_id + ' #cs_has_spoiler').prop("checked", false);
    }

}

function removeFile(modal_id) {
    $(modal_id + ' #block_upload_file').attr('data-attach', '');
    $(modal_id + ' #block_upload_file').html('');
    $(modal_id + ' #block_upload_file').addClass('d-none');
    $(modal_id + ' #btn_delete_file').html('');
}

function copy(element, type) {

    if (type == 0) {
        var $temp = $('<input>');
        $("body").append($temp);
        $temp.val(element).select();
        document.execCommand("copy");
        $temp.remove();
    }
    if (type == 1) {
        let textarea = document.getElementById(element);
        textarea.select();
        document.execCommand("copy");
    }

    messageSystem(true, 'Скопировано', 2000);
}

$('#sendMessage #image_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#sendMessage #block_upload_image').attr('data-attach', data.result.id);
            $('#sendMessage #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#sendMessage #block_upload_image').removeClass('d-none');
            $('#sendMessage #btn_upload_image').addClass('d-none');
            $('#sendMessage #text_image_uploaded').removeClass('d-none');
            $('#sendMessage #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#sendMessage\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            messageSystem(true, data.description, 2000);
        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#ep_image_upload_site').change(function() {

    let modal_id = '#editProduct';
    let block_id = '#image_site';

    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $(modal_id + ' ' + block_id + ' #block_upload_image').attr('data-attach', data.result.id);
            $(modal_id + ' ' + block_id + ' #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $(modal_id + ' ' + block_id + ' #block_upload_image').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_upload_image').addClass('d-none');
            $(modal_id + ' ' + block_id + ' #text_image_uploaded').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#editProduct\', \''+block_id+'\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});
$('#ep_image_upload').change(function() {

    let modal_id = '#editProduct';
    let block_id = '#image_bot';

    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $(modal_id + ' ' + block_id + ' #block_upload_image').attr('data-attach', data.result.id);
            $(modal_id + ' ' + block_id + ' #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $(modal_id + ' ' + block_id + ' #block_upload_image').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_upload_image').addClass('d-none');
            $(modal_id + ' ' + block_id + ' #text_image_uploaded').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#editProduct\', \''+block_id+'\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $(modal_id + ' #ep_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#ticket_image_upload').change(function() {

    let modal_id = '#chatTicket';

    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $(modal_id + ' #block_image_uploaded').attr('data-attach', data.result.id.substring(1));
            $(modal_id + ' #block_image_uploaded').html('<a target="_blank" href="/' + data.result.id + '" style="display:block;width:90px;overflow: hidden"><img style="border-radius: 16px;" width="90" src="/' + data.result.id + '" /></a>');
            $(modal_id + ' #block_image_uploaded').removeClass('d-none');
            $(modal_id + ' #block_image_uploaded a').append('<a href="javascript:;" class="text-danger d-block text-center mt-2" onclick="removeImageTicket(\''+modal_id+'\');" style="">Удалить</a>')
            messageSystem(true, data.description, 2000);
        },
        error: function(error) {
            //upload failed
        }
    });
});


function lastMessageScroll() {
    $('#chatTicket #dialog').animate({ scrollTop: $('#dialog')[0].scrollHeight });
}

function setAuthorization(xhr) {
    xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
}

$('#ap_image_upload_site').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#addProduct #image_site #block_upload_image').attr('data-attach', data.result.id);
            $('#addProduct #image_site #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#addProduct #image_site #block_upload_image').removeClass('d-none');
            $('#addProduct #image_site #btn_upload_image').addClass('d-none');
            $('#addProduct #image_site #text_image_uploaded').removeClass('d-none');
            $('#addProduct #image_site #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#addProduct\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#upload_files').change(function() {
    $(this).simpleUpload(api_url_cdn + "/attachments/files/upload", {

        beforeSend: function(xhr, settings) {
            setAuthorization(xhr),
            xhr.setRequestHeader('Admin', 'ps_d7dyYdydyyY664');
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            // console.log("upload progress: " + Math.round(progress) + "%");
            $('#fileUpload .custom-file-upload').html('Загружено '+Math.round(progress)+'%');
        },
        success: function(data) {
            if(data.ok === true) {
                // $('#fileUpload').modal('hide');
                $('#fileUpload #block_result').removeClass('d-none');
                $('#fileUpload #block_result #result_url').val('https://fnrus.com/file/' + data.result.id);
                $('#fileUpload #block_result #result_url').attr('onclick', 'copy(\'result_url\', 1)');
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, 'Загружено', 2000);
                $('#fileUpload .custom-file-upload').html('<i class="far fa-upload mr-1" style="font-size:15px"></i> Выберите файл');
            }

        },
        error: function(error) {
            //upload failed
        }
    });
});


$('#ap_image_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#addProduct #image_bot #block_upload_image').attr('data-attach', data.result.id);
            $('#addProduct #image_bot #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#addProduct #image_bot #block_upload_image').removeClass('d-none');
            $('#addProduct #image_bot #btn_upload_image').addClass('d-none');
            $('#addProduct #image_bot #text_image_uploaded').removeClass('d-none');
            $('#addProduct #image_bot #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#addProduct\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $('#addProduct #ap_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});

//
// Dropzone.autoDiscover = false;
//
// window.onload = function () {
//     var api_url = "/api"; // Replace with your API endpoint
//
//     if (newDropzone) {
//         newDropzone.destroy();
//     }
//
//     var dropzoneOptions = {
//         dictDefaultMessage: 'Drop Here!',
//         url:  api_url + "/attachments/image/upload",
//         addRemoveLinks: true,
//         maxFilesize: 5,
//         acceptedFiles: ".png, .jpg, .gif",
//         headers: {'Authorization': 'Bearer ' + getCookie('session_token')},
//         init: function() {
//             this.on('success', function(file, resp) {
//                 alert('File uploaded successfully: ' + file.name);
//             });
//             this.on('error', function(file, errorMessage) {
//                 alert('Error uploading file: ' + errorMessage);
//             });
//         }
//     };
//
//     var uploader = document.querySelector('#uploader');
//     newDropzone = new Dropzone(uploader, dropzoneOptions);
//
//     console.log("Loaded");
// };


$('#cs_image_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#createSender #block_upload_image').attr('data-attach', data.result.id);
            $('#createSender #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#createSender #block_upload_image').removeClass('d-none');
            $('#createSender #btn_upload_image').addClass('d-none');
            $('#createSender #text_image_uploaded').removeClass('d-none');
            $('#createSender #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#createSender\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $('#createSender #cs_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#es_image_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#editSender #block_upload_image').attr('data-attach', data.result.id);
            $('#editSender #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#editSender #block_upload_image').removeClass('d-none');
            $('#editSender #btn_upload_image').addClass('d-none');
            $('#editSender #text_image_uploaded').removeClass('d-none');
            $('#editSender #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#editSender\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $('#editSender #es_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#eb_image_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#editButton #block_upload_image').attr('data-attach', data.result.id);
            $('#editButton #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#editButton #block_upload_image').removeClass('d-none');
            $('#editButton #btn_upload_image').addClass('d-none');
            $('#editButton #text_image_uploaded').removeClass('d-none');
            $('#editButton #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#editButton\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $('#editButton #eb_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);
        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#nb_image_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#addButton #block_upload_image').attr('data-attach', data.result.id);
            $('#addButton #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#addButton #block_upload_image').removeClass('d-none');
            $('#addButton #btn_upload_image').addClass('d-none');
            $('#addButton #text_image_uploaded').removeClass('d-none');
            $('#addButton #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#addButton\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $('#addButton #nb_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);
        },
        error: function(error) {
            //upload failed
        }
    });
});


$('#np_image_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#addProduct #block_upload_image').attr('data-attach', data.result.id);
            $('#addProduct #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#addProduct #block_upload_image').removeClass('d-none');
            $('#addProduct #btn_upload_image').addClass('d-none');
            $('#addProduct #text_image_uploaded').removeClass('d-none');
            $('#addProduct #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\'#addProduct\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $('#addProduct #np_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);
        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#ec_image_upload_site').change(function() {

    let modal_id = '#editCategory';
    let block_id = '#image_site';

    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $(modal_id + ' ' + block_id + ' #block_upload_image').attr('data-attach', data.result.id);
            $(modal_id + ' ' + block_id + ' #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $(modal_id + ' ' + block_id + ' #block_upload_image').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_upload_image').addClass('d-none');
            $(modal_id + ' ' + block_id + ' #text_image_uploaded').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\''+modal_id+'\', \''+block_id+'\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});
$('#ec_image_upload').change(function() {

    let modal_id = '#editCategory';
    let block_id = '#image_bot';

    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $(modal_id + ' ' + block_id + ' #block_upload_image').attr('data-attach', data.result.id);
            $(modal_id + ' ' + block_id + ' #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $(modal_id + ' ' + block_id + ' #block_upload_image').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_upload_image').addClass('d-none');
            $(modal_id + ' ' + block_id + ' #text_image_uploaded').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\''+modal_id+'\', \''+block_id+'\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $(modal_id + ' #ec_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#ac_image_upload_site').change(function() {

    let modal_id = '#addCategory';
    let block_id = '#image_site';

    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $(modal_id + ' ' + block_id + ' #block_upload_image').attr('data-attach', data.result.id);
            $(modal_id + ' ' + block_id + ' #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $(modal_id + ' ' + block_id + ' #block_upload_image').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_upload_image').addClass('d-none');
            $(modal_id + ' ' + block_id + ' #text_image_uploaded').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\''+modal_id+'\', \''+block_id+'\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});
$('#ac_image_upload').change(function() {

    let modal_id = '#addCategory';
    let block_id = '#image_bot';

    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $(modal_id + ' ' + block_id + ' #block_upload_image').attr('data-attach', data.result.id);
            $(modal_id + ' ' + block_id + ' #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $(modal_id + ' ' + block_id + ' #block_upload_image').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_upload_image').addClass('d-none');
            $(modal_id + ' ' + block_id + ' #text_image_uploaded').removeClass('d-none');
            $(modal_id + ' ' + block_id + ' #btn_delete_image').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeImage(\''+modal_id+'\', \''+block_id+'\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            $(modal_id + ' #ac_has_spoiler').prop("disabled", false);
            messageSystem(true, data.description, 2000);

        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#bw_image_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/image/upload", {

        allowedExts: ["jpg", "jpeg", "png", "gif"],
        allowedTypes: ["image/jpeg", "image/png", "image/x-png", "image/gif", "image/x-gif"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#block_welcome #block_upload_image').attr('data-attach', data.result.id);
            $('#block_welcome #block_upload_image').html('<img src="/' + data.result.id + '" />');
            $('#block_welcome #block_upload_image').removeClass('d-none');
            $('#block_welcome #btn_upload_image').addClass('d-none');
            $('#block_welcome #text_image_uploaded').removeClass('d-none');
            $('#block_welcome #btn_delete_image').html('<a href="javascript:;" style="line-height: 50px;" class="text-danger d-block text-center" onclick="removeImage(\'#block_welcome\');"><i class="far fa-trash fa-xl"></i></a>').removeClass('d-none');
            messageSystem(true, data.description, 2000);
        },
        error: function(error) {
            //upload failed
        }
    });
});

$('#addMaterial #file_upload').change(function() {
    $(this).simpleUpload(api_url + "/attachments/txt/upload", {

        allowedExts: ["txt"],
        allowedTypes: ["text/plain"],
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        start: function(file) {
            //upload started
        },
        progress: function(progress) {
            //received progress
        },
        success: function(data) {
            $('#addMaterial #block_upload_file').attr('data-attach', data.result.id);
            $('#addMaterial #block_upload_file').html('<div class="alert alert-primary d-flex align-items-center"><span class="mr-auto">Файл загружен!</span><span>'+data.result.count+' строк</span></div>');
            $('#addMaterial #block_upload_file').removeClass('d-none');
            $('#addMaterial #btn_delete_file').html('<a href="javascript:;" class="text-danger d-block text-center my-3" onclick="removeFile(\'#addMaterial\');">Удалить файл</a>').removeClass('d-none');
            messageSystem(true, data.description, 2000);
        },
        error: function(error) {
            //upload failed
        }
    });
});

function fullinfo(type, id) {
    return $.ajax({
        type: "GET",
        url: api_url + '/'+type+ '/'+id+'/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            setAuthorization(xhr)
        },
        async: true
    });
}

function info(type, id) {
    return $.ajax({
        type: "GET",
        url: api_url + '/'+type+ '/'+id+'/info',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            setAuthorization(xhr)
        },
        async: true
    });
}

function addBlockFunctional(num,id) {
    var html = '<div class="row" id="block_functional_item" data-id="'+num+'"><div class="col-11"><div class="mb-3"><textarea class="form-control" id="functional" placeholder="Напишите информацию" rows="3"></textarea></div></div><div class="col-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;position: relative;top: 7px;" onclick="deleteBlockFunctional('+num+',\''+id+'\');return false;"></i></div></div>';
    num++
    $('#'+id+' #blocks_functional').append(html);
    $('#'+id+' #btn-add-functional').attr('onclick','addBlockFunctional('+num+',\''+id+'\')');
}

function addBlockTariff(id) {
    var num = $("#"+id+" #block_tariff [data-id]").last().attr('data-num');
    num++
    var html = '<div class="row" id="block_tariff_item" data-id="0" data-num="'+num+'"><div class="col-6"><div class="mb-3"><input type="number" class="form-control" id="days" placeholder="Кол-во дней"></div></div><div class="col-5"><div class="mb-3"><input type="text" class="form-control" id="price" placeholder="Цена"></div></div><div class="col-1"><i class="far fa-trash-alt" style="cursor:pointer;margin: 10px -7px;position: relative;top: 7px;" onclick="deleteBlockTariff('+num+',\''+id+'\');return false;"></i></div></div>';
    $('#'+id+' #blocks_tariffs').append(html);
    $('#'+id+' #btn-add-tariff').attr('onclick','addBlockTariff(\''+id+'\')');
}

function deleteBlockTariff(num, modal_id, id = undefined) {
    if(modal_id == 'editProduct'){
        checkTariff(id, function (data) {
            if (data.ok === true) {
                if(data.count == 0) {
                    $('#' + modal_id + ' #block_tariff_item[data-num="' + num + '"]').remove();
                } else {
                    messageSystem(false, 'В тарифе существует '+data.count+' материалов', 3000);
                }
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        })
    }
    $('#'+id+' #block_tariff_item[data-num="'+num+'"]').remove();
}
function deleteBlockFunctional(num,id) {
    $('#'+id+' #block_functional_item[data-id="'+num+'"]').remove();
}

function removeImg(modal_id, id){
    $(modal_id + ' .dz-preview[data-id="'+id+'"]').remove();
    if (document.querySelectorAll(modal_id + ' .dz-preview').length == 0){
        $(modal_id + ' .dropzone .dz-message').removeClass('d-none');
    }
}

function clearForm(type) {

    if (type == '#addProduct' || type == '#editProduct') {
        var previews = document.querySelectorAll('.dz-preview');
        previews.forEach(preview => {
            preview.parentNode.removeChild(preview);
        });

        var messages = document.querySelectorAll('.dz-message.d-none');
        messages.forEach(message => {
            message.classList.remove('d-none');
            message.classList.add('d-block');
        });

        setTimeout(function () {
            document.querySelector('.dropzone.dz-started .dz-message').style.display = 'block';
        }, 500);

        removeImage(type);

        if (type == '#addProduct' && window.descAddQuill) { window.descAddQuill.setText(''); }
        if (type == '#editProduct' && window.descEditQuill) { window.descEditQuill.setText(''); }
        $(type + ' #text_message .ql-editor').html('');
    }

    document.querySelectorAll( type + ' input, ' + type + ' textarea, ' + type + ' select').forEach(function(el) {
        if (el.tagName === 'INPUT') {
            if (['text', 'number'].includes(el.type)) {
                el.value = '';
            } else if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = false;
            }
        } else if (el.tagName === 'TEXTAREA') {
            el.value = '';
        } else if (el.tagName === 'SELECT') {
            el.selectedIndex = 0;
        }
    });
}


function ticketBlockedMemberByID(id) {
    $.ajax({
        type: "POST",
        url: api_url + '/tickets/' + id + '/blocked',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#datatable').DataTable().ajax.reload(null, false);
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }

    });

}

function selectAllProducts(){
    return $.ajax({
        type: "GET",
        url: api_url + '/products/select/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true
    });
}


function selectAllStatuses(){
    return $.ajax({
        type: "GET",
        url: api_url + '/statuses/select/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr, settings) {
            setAuthorization(xhr)
        },
        async: true
    });
}

function exists(type, id) {
    return $.ajax({
        type: "GET",
        url: api_url + '/'+type+ '/'+id+'/exists',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            setAuthorization(xhr)
        },
        async: true
    });
}

// ========== 2.1 Site Buttons ==========

function loadSiteButtons() {
    $.ajax({
        type: "GET",
        url: api_url + '/shops/settings/buttons',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok) {
                var r = data.result;
                $('#btn_tg_bot_url').val(r.btn_tg_bot_url || '');
                $('#btn_tg_bot_text').val(r.btn_tg_bot_text || '');
                $('#btn_tg_bot_icon').val(r.btn_tg_bot_icon || 'telegram');
                $('#btn_buy_bot_url').val(r.btn_buy_bot_url || '');
                $('#btn_buy_bot_text').val(r.btn_buy_bot_text || '');
                $('#btn_buy_bot_icon').val(r.btn_buy_bot_icon || 'telegram');
                $('#btn_reviews_url').val(r.btn_reviews_url || '');
                $('#btn_reviews_text').val(r.btn_reviews_text || '');
                $('#btn_reviews_icon').val(r.btn_reviews_icon || 'telegram');
            }
        }
    });
}

function saveSiteButtons() {
    $.ajax({
        type: "POST",
        url: api_url + '/shops/settings/buttons/save',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            btn_tg_bot_url: $('#btn_tg_bot_url').val(),
            btn_tg_bot_text: $('#btn_tg_bot_text').val(),
            btn_tg_bot_icon: $('#btn_tg_bot_icon').val(),
            btn_buy_bot_url: $('#btn_buy_bot_url').val(),
            btn_buy_bot_text: $('#btn_buy_bot_text').val(),
            btn_buy_bot_icon: $('#btn_buy_bot_icon').val(),
            btn_reviews_url: $('#btn_reviews_url').val(),
            btn_reviews_text: $('#btn_reviews_text').val(),
            btn_reviews_icon: $('#btn_reviews_icon').val()
        }),
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

// ========== 3.1-3.2 Support Settings ==========

function loadSupport() {
    $.ajax({
        type: "GET",
        url: api_url + '/shops/settings/support',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok) {
                var r = data.result;
                $('#support_text').val(r.support_text || '');
                $('#support_btn1_text').val(r.support_btn1_text || '');
                $('#support_btn1_url').val(r.support_btn1_url || '');
                $('#support_btn2_text').val(r.support_btn2_text || '');
                $('#support_btn2_url').val(r.support_btn2_url || '');
                $('#support_btn3_text').val(r.support_btn3_text || '');
                $('#support_btn3_url').val(r.support_btn3_url || '');
            }
        }
    });
}

function saveSupport() {
    $.ajax({
        type: "POST",
        url: api_url + '/shops/settings/support/save',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            support_text: $('#support_text').val(),
            support_btn1_text: $('#support_btn1_text').val(),
            support_btn1_url: $('#support_btn1_url').val(),
            support_btn2_text: $('#support_btn2_text').val(),
            support_btn2_url: $('#support_btn2_url').val(),
            support_btn3_text: $('#support_btn3_text').val(),
            support_btn3_url: $('#support_btn3_url').val()
        }),
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

// ========== 2.2 Policy Editor ==========

var policyEditorRu = null;
var policyEditorEn = null;

function initPolicyEditors() {
    if (typeof Quill === 'undefined') {
        $('#policy_editor_ru').html('<div class="alert alert-danger">Quill editor not loaded. Please refresh the page.</div>');
        return;
    }

    // Register custom font sizes
    var Size = Quill.import('attributors/class/size');
    Size.whitelist = ['14px', '16px', '18px', '20px', '24px'];
    Quill.register(Size, true);

    var toolbarOptions = [
        [{ 'header': [1, 2, 3, false] }],
        [{ 'size': ['14px', '16px', false, '18px', '20px', '24px'] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'align': [] }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'indent': '-1' }, { 'indent': '+1' }],
        ['blockquote'],
        ['link'],
        ['clean']
    ];
    try {
        policyEditorRu = new Quill('#policy_editor_ru', {
            modules: { toolbar: toolbarOptions },
            theme: 'snow',
            placeholder: 'Введите текст пользовательского соглашения (RU)...'
        });
    } catch(e) {
        $('#policy_editor_ru').html('<div class="alert alert-danger">Error init RU editor: ' + e.message + '</div>');
    }
    try {
        policyEditorEn = new Quill('#policy_editor_en', {
            modules: { toolbar: toolbarOptions },
            theme: 'snow',
            placeholder: 'Enter privacy policy text (EN)...'
        });
    } catch(e) {
        $('#policy_editor_en').html('<div class="alert alert-danger">Error init EN editor: ' + e.message + '</div>');
    }
}

function loadPolicy() {
    $.ajax({
        type: "GET",
        url: api_url + '/shops/settings/policy',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok && data.result) {
                var r = data.result;
                if (r.policy_content_ru && policyEditorRu) {
                    policyEditorRu.root.innerHTML = r.policy_content_ru;
                }
                if (r.policy_content_en && policyEditorEn) {
                    policyEditorEn.root.innerHTML = r.policy_content_en;
                }
            }
        }
    });
}

function savePolicy() {
    $.ajax({
        type: "POST",
        url: api_url + '/shops/settings/policy/save',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            policy_content_ru: policyEditorRu ? policyEditorRu.root.innerHTML : '',
            policy_content_en: policyEditorEn ? policyEditorEn.root.innerHTML : ''
        }),
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

// ========== 2.3 About Items ==========

function loadAboutItems() {
    $.ajax({
        type: "GET",
        url: api_url + '/about-items/all',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok && data.result) {
                var html = '';
                var items = data.result;
                if (items.length === 0) {
                    html = '<p class="text-muted">Нет элементов. Нажмите «Добавить» чтобы создать.</p>';
                }
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    html += '<div class="d-flex align-items-center justify-content-between p-3 mb-2 border rounded">';
                    html += '<div><strong>' + escapeHtml(item.icon) + '</strong> &mdash; ' + escapeHtml(item.label_ru) + ' / ' + escapeHtml(item.label_en) + '<br><small class="text-muted">' + escapeHtml(item.url) + '</small></div>';
                    html += '<div>';
                    html += '<button class="btn btn-sm btn-outline-primary mr-1" onclick="showEditAboutItem(' + item.id + ');"><i class="far fa-edit"></i></button>';
                    html += '<button class="btn btn-sm btn-outline-danger" onclick="deleteAboutItem(' + item.id + ');"><i class="far fa-trash"></i></button>';
                    html += '</div></div>';
                }
                $('#about_items_list').html(html);
                if (items.length >= 5) {
                    $('#btn_add_about_item').hide();
                } else {
                    $('#btn_add_about_item').show();
                }
            }
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

function showAddAboutItem() {
    $('#about_item_id').val(0);
    $('#about_icon').val('telegram');
    $('#about_label_ru').val('');
    $('#about_label_en').val('');
    $('#about_url').val('');
    $('#about_url_text').val('');
    $('#aboutItemModalTitle').text('Добавить элемент');
    $('#aboutItemModal').modal('show');
}

function showEditAboutItem(id) {
    $.ajax({
        type: "GET",
        url: api_url + '/about-items/' + id + '/fullinfo',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok && data.result) {
                var r = data.result;
                $('#about_item_id').val(r.id);
                $('#about_icon').val(r.icon);
                $('#about_label_ru').val(r.label_ru);
                $('#about_label_en').val(r.label_en);
                $('#about_url').val(r.url);
                $('#about_url_text').val(r.url_text);
                $('#aboutItemModalTitle').text('Редактировать элемент');
                $('#aboutItemModal').modal('show');
            }
        }
    });
}

function saveAboutItem() {
    var id = $('#about_item_id').val();
    var url = id == 0 ? api_url + '/about-items/create' : api_url + '/about-items/' + id + '/update';

    $.ajax({
        type: "POST",
        url: url,
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            icon: $('#about_icon').val(),
            label_ru: $('#about_label_ru').val(),
            label_en: $('#about_label_en').val(),
            url: $('#about_url').val(),
            url_text: $('#about_url_text').val()
        }),
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok === true) {
                $('#aboutItemModal').modal('hide');
                messageSystem(true, data.description, 2000);
                loadAboutItems();
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

function deleteAboutItem(id) {
    if (!confirm('Удалить элемент?')) return;
    $.ajax({
        type: "DELETE",
        url: api_url + '/about-items/' + id + '/delete',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
                loadAboutItems();
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

// ========== 2.5 Delivery Text Editor ==========

var deliveryEditorRu = null;
var deliveryEditorEn = null;

function initDeliveryEditors() {
    if (typeof Quill === 'undefined') {
        $('#delivery_editor_ru').html('<div class="alert alert-danger">Quill editor not loaded. Please refresh the page.</div>');
        return;
    }
    var toolbarOptions = [
        [{ 'header': [1, 2, 3, false] }],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'align': [] }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'indent': '-1' }, { 'indent': '+1' }],
        ['blockquote'],
        ['link'],
        ['clean']
    ];
    try {
        deliveryEditorRu = new Quill('#delivery_editor_ru', {
            modules: { toolbar: toolbarOptions },
            theme: 'snow',
            placeholder: 'Введите текст (RU)...'
        });
    } catch(e) {
        $('#delivery_editor_ru').html('<div class="alert alert-danger">Error init RU editor: ' + e.message + '</div>');
    }
    try {
        deliveryEditorEn = new Quill('#delivery_editor_en', {
            modules: { toolbar: toolbarOptions },
            theme: 'snow',
            placeholder: 'Enter text (EN)...'
        });
    } catch(e) {
        $('#delivery_editor_en').html('<div class="alert alert-danger">Error init EN editor: ' + e.message + '</div>');
    }
}

function loadDeliveryText() {
    $.ajax({
        type: "GET",
        url: api_url + '/shops/settings/delivery-text',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok && data.result) {
                var r = data.result;
                if (r.delivery_text_ru && deliveryEditorRu) {
                    deliveryEditorRu.root.innerHTML = r.delivery_text_ru;
                }
                if (r.delivery_text_en && deliveryEditorEn) {
                    deliveryEditorEn.root.innerHTML = r.delivery_text_en;
                }
            }
        }
    });
}

function saveDeliveryText() {
    $.ajax({
        type: "POST",
        url: api_url + '/shops/settings/delivery-text/save',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            delivery_text_ru: deliveryEditorRu ? deliveryEditorRu.root.innerHTML : '',
            delivery_text_en: deliveryEditorEn ? deliveryEditorEn.root.innerHTML : ''
        }),
        beforeSend: function(xhr) { setAuthorization(xhr); },
        success: function(data) {
            if (data.ok === true) {
                messageSystem(true, data.description, 2000);
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}

// Video upload handler for product forms
$(document).on('change', '.video-upload-input', function() {
    var input = this;
    var $group = $(this).closest('.form-group');
    var $linkInput = $group.find('#link_video');
    var $status = $group.find('.video-upload-status');
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (file.size > 512 * 1024 * 1024) {
        $status.text('Файл слишком большой (макс. 512 МБ)').addClass('text-danger').removeClass('text-muted');
        return;
    }
    var formData = new FormData();
    formData.append('video', file);
    $status.text('Загрузка: 0%').removeClass('text-danger').addClass('text-muted');
    $.ajax({
        url: api_url + '/products/upload-video',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function(xhr) { setAuthorization(xhr); },
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var pct = Math.round(e.loaded / e.total * 100);
                    $status.text('Загрузка: ' + pct + '%');
                }
            });
            return xhr;
        },
        success: function(data) {
            if (data.ok) {
                $linkInput.val(data.url);
                $status.text('Видео загружено!').removeClass('text-danger').addClass('text-muted');
            } else {
                $status.text('Ошибка: ' + data.description).addClass('text-danger').removeClass('text-muted');
            }
        },
        error: function() {
            $status.text('Ошибка загрузки').addClass('text-danger').removeClass('text-muted');
        }
    });
    $(input).val('');
});
