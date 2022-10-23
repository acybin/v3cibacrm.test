let Domains = {
    static_dt: null,
    postObject: {op: 'Controller', object: 'DomainsController', args: {mode: 'refreshDomains', on_submit: 1}},
    
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
                    args.mode = 'refreshDomains';
                    args.s_mode = 1;
					args.status = $('#status').val();   
                    args.mirror = $('#mirror').val();
                    args.no_site = $('#no_site').attr('value');
                    $.extend(args, AppPageFilters.getFiltersMas());
                    obj = {op: 'Controller', object: 'DomainsController', args: args, load: true, responce: true};
                    return obj;
                },
                'complete':function() {                                              
                    
                }
            },

			columnDefs: [
			  { targets: 'no-sort', orderable: false }
			],

            "initComplete": function(settings, json) {
                
            },

            "fnCreatedRow": function(nRow, aData, iDataIndex) {
                $(nRow).addClass('list-order_item grey');

                /*$(nRow).find('td').each(function() {
                    $(this).addClass('highlight');
                });*/

				if (aData.DT_RowData['no_active']) { 
                	if (aData.DT_RowData['no_active'] == '1') { 
                    	$(nRow).addClass('disabled');
                    }
                }
                
                /*if (aData.DT_RowData['mirror']) { 
                	if (aData.DT_RowData['mirror'] == '1') { 
                    	$(nRow).addClass('yellow');
                    }
                }*/

				if (aData.DT_RowData['fields']) {
					for (var i = 0; i < aData.DT_RowData['fields'].length; i++) {						
						$(nRow).find('td:eq('+ i + ')').attr('name', aData.DT_RowData['fields'][i]);
						if (aData.DT_RowData['fields'][i] != 'id' && aData.DT_RowData['fields'][i] != 'delete_domain') {
							$(nRow).find('td:eq('+ i + ')').addClass('edit-td');
							$(nRow).find('td:eq('+ i + ')').html('<div class="cell">' + $(nRow).find('td:eq('+ i + ')').text() + '</div>');
						}
						if (aData.DT_RowData['fields'][i] == 'delete_domain') {
							let action = 'fa-trash delete_domain';
							if ($(nRow).hasClass('disabled')) {
								action = 'fa-undo enable_domain';
							} 
							$(nRow).find('td:eq('+ i + ')').html('<i class="fa ' + action +'" aria-hidden="true"></i>');
						}
                        if (aData.DT_RowData['fields'][i] == 'name') {
							$(nRow).find('td:eq(' + i + ')').find('.cell').html($(nRow).find('td:eq(' + i + ')').find('.cell').html() + '<i class="fa fa-files-o copy" aria-hidden="true"></i>')
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

        this.static_dt = $("#table_domains").DataTable(datatable_array); 
        $('#body_domains').on('update:filter', Domains.static_dt, function() {
            $("#table_domains").find('tbody').css('filter', 'blur(3px)');
            Domains.static_dt.ajax.reload(function (json) {
                $("#table_domains").find('tbody').css('filter', 'none');              
            });           
        });        
        this.static_dt.ajax.reload(function (json) {
              
        });
		$('#status').on("change", function (e) {
            Domains.static_dt.ajax.reload(); 
        });
        $('#mirror').on("change", function (e) {
            Domains.static_dt.ajax.reload(); 
        });
    }

};




let DomainSites = {
    static_dt: null,
    postObject: {op: 'Controller', object: 'DomainsController', args: {mode: 'refreshDomainSites', on_submit: 1}},
    
    initialize: function ($wrapper, domain_id) {
        this.$wrapper = $wrapper;

        dom_val = "<'row'<'col-sm-6'f><'col-sm-6'>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row datatable-footer'<'col-sm-4'li><'col-sm-4'p><'col-sm-4 summ'>>";

        datatable_array = {
            'processing': true,
            'serverSide': true,
			"autoWidth": false,
            'ordering' : true,
            'ajax': {
                'type': "POST",
                'url': "/admin/",
                'dataType': 'json',
                'data': function(args) {
					args.s_mode = 1;
                    args.mode = 'refreshDomainSites';
                    args.domain_id = domain_id;
                    obj = {op: 'Controller', object: 'DomainsController', args: args, responce: true};
                    return obj;
                },
                'complete':function() {
                   
                },
				beforeSend: function(){
					$("#table_domain_sites").find('tbody').css('filter', 'blur(3px)');
				},
				complete: function(){
					$("#table_domain_sites").find('tbody').css('filter', 'none'); 
				}               
            },             
            
			columnDefs: [
			  { targets: 'no-sort', orderable: false }
			],

            "initComplete": function(settings, json) {
                
            },

            "drawCallback": function(settings) {
                
            },

            "fnCreatedRow": function(nRow, aData, iDataIndex) {                
                $(nRow).addClass('list-order_item grey');

                /*$(nRow).find('td').each(function() {
                    $(this).addClass('highlight');
                });*/
            },
            
            "dom": dom_val,
            "lengthMenu": [10, 25, 50, 75, 100],
            "pageLength": 10,
			"pagingType": "simple",
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
        
		this.static_dt = $("#table_domain_sites").DataTable(datatable_array);
		
        this.static_dt.ajax.reload(function (json) {
			
        });
    }
};




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

$.notify.addStyle('warning_boot', {
    html: '<div><span data-notify-text></span></div>',
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
});

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
});