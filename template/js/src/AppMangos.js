let AppMangos = {
    static_dt: null,
    postObject: {op: 'Controller', object: 'MangoController', args: {mode: 'refreshMangos', on_submit: 1}},
    
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
                    args.mode = 'refreshMangos';
                    args.s_mode = 1;
                    args.dt_filt_red = $('#dt_filt_red').attr('value');
                    args.dt_filt_yellow = $('#dt_filt_yellow').attr('value');
                    args.dt_filt_blue = $('#dt_filt_blue').attr('value');
                    args.no_scenario = $('#no_scenario').attr('value');
                    args.type = $('#type').val();
                    args.status = $('#status').val();   
                    args.channel = $('#channel').val(); 
                    $.extend(args, AppPageFilters.getFiltersMas());
                    obj = {op: 'Controller', object: 'MangoController', args: args, load: true, responce: true};
                    return obj;
                },
                beforeSend: function(){
					$("#mango_table").find('tbody').css('filter', 'blur(3px)');
				},
				complete: function(){
					$("#mango_table").find('tbody').css('filter', 'none'); 
				}
            },

			columnDefs: [
			  { targets: 'no-sort', orderable: false }
			],

            "initComplete": function(settings, json) {
                
            },

            "fnCreatedRow": function(nRow, aData, iDataIndex) {
                $(nRow).addClass('list-order_item grey');
                               
                $(nRow).attr('data-id', aData.DT_RowData['data-id']);      
                
                if (aData.DT_RowData['nls_source_id']) {
                    $(nRow).attr('nls_source_id', aData.DT_RowData['nls_source_id']);
                }
                
                if (aData.DT_RowData['error']) {
                    $(nRow).attr('error', aData.DT_RowData['error']);
                }
                if (aData.DT_RowData['error_value']) {
                    $(nRow).attr('error_value', aData.DT_RowData['error_value']);
                }
                
				if (aData.DT_RowData['fields']) {
					for (var i = 0; i < aData.DT_RowData['fields'].length; i++) {						
						$(nRow).find('td:eq('+ i + ')').attr('name', aData.DT_RowData['fields'][i]);
                        
                       	if (aData.DT_RowData['fields'][i] != 'name' && aData.DT_RowData['fields'][i] != 'analytics') {
                            $(nRow).find('td:eq('+ i + ')').html('<div class="cell">' + $(nRow).find('td:eq('+ i + ')').html() + '</div>');
                            
                        }
                        
                        if (aData.DT_RowData['fields'][i] == 'name') {
                            text = $(nRow).find('td:eq('+ i + ')').html();
                            
                            if ($(nRow).attr('error') == 2)
                                 text += '&nbsp;<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>';
                                 
                            if ($(nRow).attr('error') == 1)
                                 text += '&nbsp;<i class="fa fa-exclamation-triangle yellow" aria-hidden="true"></i>';
                                 
                            if ($(nRow).attr('error') == 3)
                                 text += '&nbsp;<i class="fa fa-exclamation blue" aria-hidden="true"></i>';
  
							$(nRow).find('td:eq('+ i + ')').html('<div class="cell">' + text + '</div>');
						}
                        
                        if (aData.DT_RowData['fields'][i] == 'analytics' && $(nRow).attr('data-id')) {
							$(nRow).find('td:eq('+ i + ')').html('<i class="fa js-chart fa-area-chart" aria-hidden="true"></i>');
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

        this.static_dt = $("#mango_table").DataTable(datatable_array); 
      
        this.static_dt.ajax.reload(function (json) {
              
        });
    }
};

$.notify.addStyle('success_boot', {
    html: '<div><span data-notify-text></span></div>',
    classes: {
        base : {
            "position": "relative",
            "padding": ".75rem 1.25rem",
            "margin-bottom": "1rem",
            "border": "1px solid transparent",
            "border-radius": ".25rem",
            "color": "#155724",
            "background-color": "#d4edda",
            "border-color": "#c3e6cb"
        }
    }
});

$.notify.addStyle('error_boot', {
    html: '<div><span data-notify-text></span></div>',
    classes: {
        base : {
            "position": "relative",
            "padding": ".75rem 1.25rem",
            "margin-bottom": "1rem",
            "border": "1px solid transparent",
            "border-radius": ".25rem",
            "color": "#721c24",
            "background-color": "#f8d7da",
            "border-color": "#f5c6cb"
        }
    }
});