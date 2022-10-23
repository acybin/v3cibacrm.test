let AppCampaigns = {
    static_dt: null,
    postObject: {op: 'Controller', object: 'CampaignController', args: {mode: 'refreshCampaigns', on_submit: 1}},
    
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
                    args.mode = 'refreshCampaigns';
                    args.s_mode = 1;
                    args.active = $('#active').val();
                    args.suffics = $('#suffics').val();
                    args.no_partner = $('#no_partner').attr('value');
                    $.extend(args, AppPageFilters.getFiltersMas());
                    obj = {op: 'Controller', object: 'CampaignController', args: args, load: true, responce: true};
                    return obj;
                },
                beforeSend: function(){
					$("#campaign_table").find('tbody').css('filter', 'blur(3px)');
				},
				complete: function(){
					$("#campaign_table").find('tbody').css('filter', 'none'); 
				}
            },

			columnDefs: [
			  { targets: 'no-sort', orderable: false }
			],

            "initComplete": function(settings, json) {
                
            },

            "fnCreatedRow": function(nRow, aData, iDataIndex) {
                $(nRow).addClass('list-order_item grey');
                
                if (aData.DT_RowData['active']) { 
                	if (aData.DT_RowData['active'] == '1' || aData.DT_RowData['active'] == '2') { 
                    	$(nRow).addClass('disabled');
                    }
                }
                
                $(nRow).attr('data-id', aData.DT_RowData['data-id']);
                $(nRow).attr('data-suffics', aData.DT_RowData['suffics']);  
                
                if (aData.DT_RowData['nls_source_id']) {
                    $(nRow).attr('nls_source_id', aData.DT_RowData['nls_source_id']);
                }
                
                if (aData.DT_RowData['fields']) {
					for (var i = 0; i < aData.DT_RowData['fields'].length; i++) {						
						$(nRow).find('td:eq('+ i + ')').attr('name', aData.DT_RowData['fields'][i]);
                        
                       	if (aData.DT_RowData['fields'][i] == 'tags') {
							$(nRow).find('td:eq('+ i + ')').addClass('edit-td');
                            
                            var text = [];
                            var j = 0;
                            $.each(aData.DT_RowData['tags'], function(tag, ids_names) {
                                $.each(ids_names, function(id, name) {
                                    if (name[2] == 0) {
                                        label_color = 'grey';
                                    }
                                    else {
                                        if (name[1] == 1)
                                            label_color = 'green';
                                        else
                                            label_color = 'red';
                                    }
                                    
                                    text[j] = '<span class="label label-' + label_color + '" tag-id="' + id + '" tag-name="' + tag + '">' + name[0] + '</span>&nbsp;';
                                    j = j + 1;
                                });
                            });
                            text = text.join('');
                            text = '<div class="cell">' + text + '</div>';
                            
                            $(nRow).find('td:eq('+ i + ')').html(text);
						}
                        else {
                            $(nRow).find('td:eq('+ i + ')').html('<div class="cell">' + $(nRow).find('td:eq('+ i + ')').html() + '</div>');
                            
                            if (aData.DT_RowData['fields'][i] == 'nls_source_name' 
                                            //|| aData.DT_RowData['fields'][i] == 'count_keys'
                                        ) {
							     $(nRow).find('td:eq('+ i + ')').addClass('edit-td');
                            }
                        }
                    }
                }
                
                if (aData.DT_RowData['suffics']) {
                    if (aData.DT_RowData['suffics'] == 0) {
                        $(nRow).find('td[name=name]').find('.cell').append('<span class="yd_letter"></span>');
                    }
                    if (aData.DT_RowData['suffics'] == 1) {
                        $(nRow).find('td[name=name]').find('.cell').append('<span class="ga_letter"></span>');
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

        this.static_dt = $("#campaign_table").DataTable(datatable_array); 
      
        this.static_dt.ajax.reload(function (json) {
              
        });
    }
};

let AppCampaignKeys = {
    static_dt: null,
    postObject: {op: 'Controller', object: 'CampaignController', args: {mode: 'showKeys', on_submit: 1}},
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
        this.static_dt = $("#datatable_keys").DataTable(datatable_array);
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