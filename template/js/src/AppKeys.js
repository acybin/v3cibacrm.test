let AppKeys = {
    static_dt: null,
    postObject: {op: 'Controller', object: 'KeyController', args: {mode: 'refreshKeys', on_submit: 1}},
    
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
                    args.mode = 'refreshKeys';
                    args.s_mode = 1;
                    args.no_tags = $('#no_tags').attr('value');
                    $.extend(args, AppPageFilters.getFiltersMas());
                    obj = {op: 'Controller', object: 'KeyController', args: args, load: true, responce: true};
                    return obj;
                },
                beforeSend: function(){
					$("#key_table").find('tbody').css('filter', 'blur(3px)');
				},
				complete: function(){
					$("#key_table").find('tbody').css('filter', 'none'); 
				}
            },

			columnDefs: [
			  { targets: 'no-sort', orderable: false }
			],

            "initComplete": function(settings, json) {
                
            },

            "fnCreatedRow": function(nRow, aData, iDataIndex) {
                $(nRow).addClass('list-order_item grey');
                
                if (aData.DT_RowData['no_active']) { 
                	if (aData.DT_RowData['no_active'] == '1') { 
                    	$(nRow).addClass('disabled');
                    }
                }
                
                $(nRow).attr('data-id', aData.DT_RowData['data-id']);
                
                if (aData.DT_RowData['fields']) {
					for (var i = 0; i < aData.DT_RowData['fields'].length; i++) {						
						$(nRow).find('td:eq('+ i + ')').attr('name', aData.DT_RowData['fields'][i]);
                        
                        if (aData.DT_RowData['fields'][i] != 'id' && aData.DT_RowData['fields'][i] != 'tags' && aData.DT_RowData['fields'][i] != 'delete_key') {
                       	    $(nRow).find('td:eq('+ i + ')').addClass('edit-td');
							$(nRow).find('td:eq('+ i + ')').html('<div class="cell">' + $(nRow).find('td:eq('+ i + ')').text() + '</div>');
                        }
                            
                       	if (aData.DT_RowData['fields'][i] == 'tags') {
							$(nRow).find('td:eq('+ i + ')').addClass('edit-td');
                            
                            var text = [];
                            var j = 0;
                            $.each(aData.DT_RowData['tags'], function(tag, ids_names) {
                                $.each(ids_names, function(id, name) {
                                    text[j] = '<span class="label label-grey" tag-id="' + id + '" tag-name="' + tag + '">' + name + '</span>&nbsp;';
                                    j = j + 1;
                                });
                            });
                            text = text.join('');
                            text = '<div class="cell">' + text + '</div>';
                            
                            $(nRow).find('td:eq('+ i + ')').html(text);
						}
                        
                        if (aData.DT_RowData['fields'][i] == 'delete_key') {
							let action = 'fa-trash delete_key';
							if ($(nRow).hasClass('disabled')) {
								action = 'fa-undo enable_key';
							} 
							$(nRow).find('td:eq('+ i + ')').html('<i class="fa ' + action +'" aria-hidden="true"></i>');
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

        this.static_dt = $("#key_table").DataTable(datatable_array); 
      
        this.static_dt.ajax.reload(function (json) {
              
        });
    }
};

let AppKeyCampaigns = {
    static_dt: null,
    postObject: {op: 'Controller', object: 'KeyController', args: {mode: 'showCampaigns', on_submit: 1}},
    initialize: function ($wrapper) {
        this.$wrapper = $wrapper;
        
        dom_val = "<'row'<'col-sm-6'f><'col-sm-6'>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row datatable-footer'<'col-sm-4'li><'col-sm-4'p><'col-sm-4 summ'>>";
        
        datatable_array = {
            "dom": dom_val,
            "lengthMenu": [ [10, -1], [10, "Все"] ],
            "pageLength": 10,
            'ordering': false,
            //"info": false,
            "pagingType": "simple",
            "language": {
                "processing": "Подождите...",
                //"search": "Поиск:",
                "search": "_INPUT_",
                "lengthMenu": "_MENU_",
                "info": "из _TOTAL_",
                "infoEmpty": "0",
                "infoFiltered": "(всего _MAX_)",
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
        this.static_dt = $("#datatable_campaigns").DataTable(datatable_array);
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

$.notify.addStyle('submit_form', {
    html: 
        "<div>" +
            "<div class='clearfix'>" +
                "<div class='title' data-notify-html='title' style='margin-bottom: 5px;'></div>" +
                "<div class='buttons'>" +
                    "<div class='col-xs-6' style='text-align: center;'><button class='yes btn btn-success' style='width: 70px;'>Да</button></div>" +
                    "<div class='col-xs-6' style='text-align: center;'><button class='no btn btn-danger' style='width: 70px;'>Нет</button></div>" +                    
                "</div>" +
            "</div>" +
        "</div>",
    classes: {
        base : {
            "position": "relative",
            "padding": ".75rem 1.25rem",
            "margin-bottom": "1rem",
            "border": "1px solid transparent",
            "border-radius": ".25rem",
            "color": "#856404",
            "background-color": "#fff3cd",
            "border-color": "#ffeeba"
        }
    }    
})