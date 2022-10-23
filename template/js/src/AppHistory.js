let History = {
    static_dt: null,
    postObject: {op: 'Controller', object: 'HistoryController', args: {mode: 'refreshHistory', on_submit: 1}},
    
    initialize: function ($wrapper) {
        this.$wrapper = $wrapper;

        dom_val = "<'row'<'col-sm-6'f><'col-sm-6'>>" + "<'row'<'col-sm-12'tr>>" + "<'row datatable-footer'<'col-sm-4'li><'col-sm-4'p><'col-sm-4 summ'>>";

        datatable_array = {
            "autoWidth": false,
            'ordering' : true,
            'serverSide': true,
            'deferLoading': 0,
            'fixedHeader': true,
            'stateSave': true,
            'ajax': {
                'type': "POST",
                'url': "/admin/",
                'dataType': 'json',
                'data': function(args) {
                    args.mode = 'refreshHistory';
                    args.s_mode = 1;
                    args.default_sort = $('#default_sort').text();  
                    if($('#invalid').hasClass('checked')) {
                        args.invalid = true;
                    }
                    else {
                        args.invalid = false;
                    }
                    args.status = $('#status').val();   
                    args.active = $('#active').val();
                    $.extend(args, AppPageFilters.getFiltersMas());
                    obj = {op: 'Controller', object: 'HistoryController', args: args, load: true, responce: true};
                    return obj;
                },
                'complete':function() {                                              
                    
                }
            },

            "initComplete": function(settings, json) {
                
            },

            "fnCreatedRow": function(nRow, aData, iDataIndex) {                
                $(nRow).addClass('list-order_item grey');

                $(nRow).find('td').each(function() {
                    $(this).addClass('highlight');
                });

                if (aData.DT_RowData) {
                    if (aData.DT_RowData['history_status']) {
                        $(nRow).attr('history_status', aData.DT_RowData['history_status']);
                        if ($(nRow).attr('history_status') != 'Успешно') {
                            $('td:eq(6)', nRow).html($('td:eq(6)', nRow).html() + '&nbsp;<i class="fa fa-exclamation-triangle" aria-hidden="true"></i></div>');
                        }
                    }
                }
            },
            
            "dom": dom_val,
            "lengthMenu": [10, 25, 50, 75, 100],
            "pageLength": 50,
            "language": {
                "processing": "Подождите...",
                "search": "_INPUT_",
                "searchPlaceholder": "Поиск...",
                "lengthMenu": "_MENU_",
                "info": "из _TOTAL_",
                "infoEmpty": "0",
                "infoFiltered": "(отфильтровано из _MAX_ записей)",
                "infoPostFix": "",
                "loadingRecords": "Загрузка записей...",
                "zeroRecords": "Записи отсутствуют.",
                "emptyTable": "В таблице отсутствуют данные",
                "paginate": {
                    "next": "<i class=\"fa fa-chevron-right\"></i>",
                    "previous": "<i class=\"fa fa-chevron-left\"></i>"
                },
                "aria": {
                    "sortAscending": ": активировать для сортировки столбца по возрастанию",
                    "sortDescending": ": активировать для сортировки столбца по убыванию"
                }
            }
        };        
        
        this.static_dt = $("#table_history").DataTable(datatable_array); 
        $('#body_history').on('update:filter', History.static_dt, function() {
            $("#table_history").find('tbody').css('filter', 'blur(3px)');
            History.static_dt.ajax.reload(function (json) {
                $("#table_history").find('tbody').css('filter', 'none');              
            });           
        });        
        this.static_dt.ajax.reload(function (json) {
            if ($('#default_sort').text() == '1') {
                $('#default_sort').text(0);
                //fix default sorting arrow
                $('#table_history').find('tr').first().find('th').eq(0).removeClass('sorting_asc');                    
                $('#table_history').find('tr').first().find('th').eq(0).addClass('sorting_desc');
                $('#table_history').find('tr').first().find('th').eq(0).removeClass('sorting');
                $('#table_history').find('tr').first().find('th').eq(0).attr('aria-sort', 'descending');
            }   
        });
        $("body").on("click", '#invalid', function(){
            if ($(this).hasClass('checked')) {
                $(this).removeClass('checked');
            }
            else {
                $(this).addClass('checked');            
            }
            History.static_dt.ajax.reload(); 
        });
        $('#status').on("change", function (e) {
            History.static_dt.ajax.reload(); 
        });
        $('#active').on("change", function (e) {
            History.static_dt.ajax.reload(); 
        });
    }

};