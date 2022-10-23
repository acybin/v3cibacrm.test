$(function () {
	setTagsFromCookies();
	
    Domains.initialize($('#DomainsController-showDomains'));

	moment.locale('ru');
	
	const datepicker_start = $('#start').text();
	const datepicker_end = $('#end').text();
	AppPageFilters.filterDateRangePickerConfigDefault.maxDate = moment(datepicker_end + ' 23:59:59',  "DD.MM.YYYY HH:mm:ss").locale('ru');
	AppPageFilters.filterDateRangePickerConfigDefault.ranges = {            
        'Сегодня': [moment().startOf('days'), moment().endOf('days')],
        'Вчера': [moment().subtract(1, 'days').startOf('days'), moment().subtract(1, 'days').endOf('days')],
        'Позавчера': [moment().subtract(2, 'days').startOf('days'), moment().subtract(2, 'days').endOf('days')],
        'Последние 7 Дней': [moment().subtract(6, 'days').startOf('days'), moment().endOf('days')],
        'Последние 30 Дней': [moment().subtract(29, 'days').startOf('days'), moment().endOf('days')],
        'Последние 90 Дней': [moment().subtract(89, 'days').startOf('days'), moment().endOf('days')],
        'Текущая неделя': [moment().startOf('isoweek').startOf('days'), moment().endOf('days')],                    
        'Прошлая неделя': [moment().subtract(1, 'week').startOf('isoweek').startOf('days'), moment().subtract(1, 'week').endOf('isoweek').endOf('days')],                    
        'Текущий месяц': [moment().startOf('month').startOf('days'), moment().endOf('days')],
        'Прошлый месяц': [moment().subtract(1, 'month').startOf('month').startOf('days'), moment().subtract(1, 'month').endOf('month').endOf('days')],  
        'Позапрошлый месяц': [moment().subtract(2, 'month').startOf('month').startOf('days'), moment().subtract(2, 'month').endOf('month').endOf('days')],                   
        'Позапозапрошлый месяц': [moment().subtract(3, 'month').startOf('month').startOf('days'), moment().subtract(3, 'month').endOf('month').endOf('days')],
        'За все время': [moment(datepicker_start + ' 00:00:00',  "DD.MM.YYYY HH:mm:ss").locale('ru'), moment(datepicker_end + ' 23:59:59',  "DD.MM.YYYY HH:mm:ss").locale('ru')]
    };

    $('body').find('.datepicker').daterangepicker(AppPageFilters.filterDateRangePickerConfigDefault, AppPageFilters.filterDatePickerCallback);


    $("#status").select2({minimumResultsForSearch:-1});
    $("#status").data('select2').$selection.css('height', '34px');
    $("#div_status").find('.select2-container').css('width', '115px');
    $("#status").data('select2').$selection.find('span').first().css('padding-top', '2px'); 


    $("#mirror").select2({minimumResultsForSearch:-1});
    $("#mirror").data('select2').$selection.css('height', '34px');
    $("#div_mirror").find('.select2-container').css('width', '115px');
    $("#mirror").data('select2').$selection.find('span').first().css('padding-top', '2px');


    $("body").on("click", ".dropdown-menu", function(e){
        e.preventDefault();
        e.stopPropagation();
        return false;
    });


	$('#FilterController-showFilters[page=domains]').find('.dropdown-menu').find('.flat').on('ifClicked', function(event){
        const checkbox_count = $(this).closest('span[class="dropdown tag open"]').find('.count');
        const checkbox_back = $(this).closest('span[class="dropdown tag open"]').find('.rigthtbutton');
        const tag_type = $(this).closest('.wrapper').attr('data-filter_type');       

        if ($(this).closest('div').hasClass('checked')) {
            deleteTag($(this));
        }
        else {          
            addTag($(this));
        }

        //refreshTagsOnPage();
        hideTags();

        var new_count = countTagsByType(tag_type);
        checkbox_count.text(new_count);        
        if (new_count === 0) {
            checkbox_back.removeAttr('style');
        }
        else {
            if (!checkbox_back.attr('style')) {
                checkbox_back.attr('style', 'background: ' + FilterControllerClasses[tag_type][1]);
            }
        }
    });



    $('#FilterController-showFilters[page=domains]').find('.switch_check').on('click', function() {
        const checkbox_count = $(this).closest('span[class="dropdown tag open"]').find('.count');
        const checkbox_back = $(this).closest('span[class="dropdown tag open"]').find('.rigthtbutton');        
        const tag_type = $(this).closest('.wrapper').attr('data-filter_type');

        $(this).closest('.dropdown-menu').find('.overflow').find('.flat').each(function() {
            if ($(this).closest('div').hasClass('checked')) {
                $(this).iCheck('uncheck');
                deleteTag($(this));
            }
            else {
                $(this).iCheck('check');
                addTag($(this));
            }
        });

        //refreshTagsOnPage();
        hideTags();

        var new_count = countTagsByType(tag_type);
        checkbox_count.text(new_count);        
        if (new_count === 0) {
            checkbox_back.removeAttr('style');
        }
        else {
            if (!checkbox_back.attr('style')) {
                checkbox_back.attr('style', 'background: ' + FilterControllerClasses[tag_type][1]);
            }
        }
    });    


    $('#FilterController-showFilters[page=domains]').find('.all_checked').on('click', function() {
        const checkbox_count = $(this).closest('span[class="dropdown tag open"]').find('.count');
        const checkbox_back = $(this).closest('span[class="dropdown tag open"]').find('.rigthtbutton');        
        const tag_type = $(this).closest('.wrapper').attr('data-filter_type');
        
        $(this).closest('.dropdown-menu').find('.overflow').find('.flat').each(function() {
            if (!$(this).parent().hasClass('checked')) {
                $(this).iCheck('check');
                addTag($(this));
            }
        });

        //refreshTagsOnPage();
        hideTags();

        var new_count = countTagsByType(tag_type);
        checkbox_count.text(new_count);
        if (new_count === 0) {
            checkbox_back.removeAttr('style');
        }
        else {
            if (!checkbox_back.attr('style')) {
                checkbox_back.attr('style', 'background: ' + FilterControllerClasses[tag_type][1]);
            }
        }             
    });


    $('#FilterController-showFilters[page=domains]').find('.clean_checked').on('click', function() {
        const checkbox_count = $(this).closest('span[class="dropdown tag open"]').find('.count');
        const checkbox_back = $(this).closest('span[class="dropdown tag open"]').find('.rigthtbutton');        
        const tag_type = $(this).closest('.wrapper').attr('data-filter_type');

        $(this).closest('.dropdown-menu').find('.overflow').find('.flat').each(function() {
            if ($(this).parent().hasClass('checked')) {
                $(this).iCheck('uncheck');
                deleteTag($(this));
            }
        });

        //refreshTagsOnPage();
        hideTags();

        var new_count = countTagsByType(tag_type);
        checkbox_count.text(new_count);
        if (new_count === 0) {
            checkbox_back.removeAttr('style');
        }
        else {
            if (!checkbox_back.attr('style')) {
                checkbox_back.attr('style', 'background: ' + FilterControllerClasses[tag_type][1]);
            }
        }
    });


    $('body > div.daterangepicker > div.ranges').on('click', function() {
        $('#table_domains').trigger('update:filter');
    });

    
    $(document).on('click', 'body', function() {
        $('#FilterController-showFilters[page=domains]').find('.wrapper').find('.dropdown').each(function() {
            if ($(this).hasClass('open')) {
                var check_count = 0;
                $(this).find('.flat').each(function() {
                    if ($(this).attr('prev')) {                        
                        var cur_val = 'off';
                        if ($(this).parent().hasClass('checked')) {
                            cur_val = 'on';
                        }
                        else {
                            cur_val = 'off';
                        }
                        if ($(this).attr('prev') != cur_val) {
                            $(this).attr('prev', cur_val);
                            check_count = check_count + 1;
                        }
                    }
                    else {
                        if ($(this).parent().hasClass('checked')) {
                            check_count = check_count + 1;
                            $(this).attr('prev', 'on');
                        }
                        else {
                            $(this).attr('prev', 'off');
                        }                        
                    }
                });
                if (check_count > 0) {
                    $('#table_domains').trigger('update:filter');
                }
            }
        });        
    });
    
    
    $('body').on('click', '#FilterController-showFilter', function() {
        if ($(this).closest('.page-filter').attr('page') == 'domains') {
            var opened = false;
            $(this).closest('.filter-listing').find('.dropdown').each(function() {
                if ($(this).hasClass('open')) {
                    opened = true;
                }            
            });
            if (opened) {
                $("body").trigger("click");
            }
        }
    });


	$('#body_domains').on("click", '#export_domains', function(e){
        var docHeight = $(document).height();
        $("body").append("<div id='overlay'></div>");
        $("#overlay")
            .height(docHeight)
            .css({
                'opacity' : 0.4,
                'position': 'absolute',
                'top': 0,
                'left': 0,
                'background-color': 'black',
                'width': '100%',
                'z-index': 5000
            });
        var args = Domains.static_dt.ajax.params().args;
        args.save = 1;

        $.ajax({
            type: 'POST',
            url: '/admin/',
            dataType: "json",
            timeout: 40000, 
            data: {
                op: 'Controller',
                object: 'DomainsController',
                args: args,
                responce: true
            }
        }).done(function(data){
            var $a = $("<a>");
            $a.attr("href",data.data);
            $("body").append($a);
            $a.attr("download","Domains.csv");
            $a[0].click();
            $a.remove();
            $("#overlay").remove();
        });        
    });


    $('body').on("click", '#cancel_filter', function(e){
        $('#FilterController-showFilters[page=domains]').find('.wrapper').each(function() {
            $(this).find('.dropdown-menu').find('.overflow').find('.flat').each(function() {
                $(this).iCheck('uncheck');
            });
            $(this).find('.tag').find('.count').text(0);
            $(this).find('.tag').find('.rigthtbutton').removeAttr('style');
        });
        $('#filter_tags').html('<div id="empty_tag" style="margin-top: 30px;"></div><div id="tags_menu" hidden="true"></div>');
        deleteCookie('filterTags');
        $('#table_domains').trigger('update:filter');
    });
    
    $("body").on("click", '#no_site', function(){
        if ($(this).hasClass('checked')) {
            $(this).removeClass('checked');
			$(this).attr('value', '0');
        }
        else {
            $(this).addClass('checked');
			$(this).attr('value', '1');
        }
		$('#table_domains').trigger('update:filter');
    });    


    /*$('#body_domains').on('mouseenter', '.highlight', function () {
		$('#table_domains').find('tr').removeAttr('style');  
        $(this).closest('tr').attr('style', 'background-color: rgba(38,185,354,0.1)');        
    });
    

    $('#body_domains').on('mouseleave', '.highlight', function () {
    	$(this).closest('tr').removeAttr('style');
    });*/


    $("body").on("mouseenter", '#table_domains tr td:not(:nth-child(1)):not(:nth-child(11))', function(){
       	$('#table_domains').find('.tooltip').remove();
		const td_html = $(this).html();
        const td_text = $(this).text();
        const fix_text = $(this).text().replace(/\s/g, '');
        if ($(this).find('.tooltip').length == 0) {
            if ($(this).find('.open').length == 0) {
                if (fix_text != '') {
                    $(this).html(td_html + '<div class="tooltip" style="font-size: 11px;">' + td_text + '</div>');
                    $(this).find('.tooltip').tooltip('show'); 
                }
            } 
        }                    
    });

    $("body").on("mouseleave", '#table_domains tr td:not(:nth-child(1)):not(:nth-child(11))', function(){
        $('#table_domains').find('.tooltip').remove();        
    }); 


	//fix overflow x for filters
    $('#FilterController-showFilters').find('.wrapper').each(function() {
        $(this).find('.overflow').attr('style', 'overflow-x:hidden');
    });


    //move datatable's search to other filters
    $('#body_domains').find('.col-xs-5').first().prepend($('#table_domains_filter'));
    $('#table_domains_filter').attr('style', 'width: 165px !important;');
    $('#table_domains_filter').addClass('col-xs-3 maxcontent zero-padd mr10');
    $('#table_domains_filter > label').attr('style', 'font-weight: normal !important; margin-bottom: 0px;');
    $('#table_domains_filter > label > input').attr('style', 'height: 34px; font-size: 14px');
    $('#table_domains_wrapper').find('.row').first().remove();
    $('#table_domains_filter').append($('#search_label'));
       
    //move download button to datatable's footer
    $('#table_domains_wrapper').find('.datatable-footer').find('.summ').append($('#div_export_domains'));
    $('#table_domains_wrapper').find('.datatable-footer').find('.summ').attr('style', 'margin-top: 7px;');

    //move cancel filter button
    $('#FilterController-showFilters').find('.wrapper').last().after($('#cancel_filter'));

    //move tag info tooltip button after cancel filter
    //$('#cancel_filter').after($('#tag_info'));	


	
	$("body").on("click", '.edit-td', function(){
		$('#table_domains').find('.cell').show();
        $(this).find('.tooltip').remove();
        $(this).attr('style', 'overflow: unset;text-overflow: unset;white-space: unset;position: relative;');
        $(this).find('.cell').hide();

        $('.edit-td').unbind('click');
        
        $(this).bind('click', function (e) { 
            if ($(e.target).attr('class') !== undefined) {
                if ($.inArray($(e.target).attr('class'), ['js-close', 'save_name'])) {
                    return;
                }
                else {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
            else {                
                e.preventDefault();
                e.stopPropagation();
                return false;                                 
            }            
        });

		$(this).closest('tbody').find('.open').remove();
	});
	
		
		
	$("body").on("click", '.js-close', function(){
        $(this).closest('td').find('.cell').show();
        $(this).closest('td').attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
        $('.edit-td').unbind('click');
        $(this).closest('.open').remove();
    });
	
	
	
	$("body").on("click", '.edit-td[name="name"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsName', domain_name: selectText}}, function(code, answer){
            if (code === "success") {
                td.html(html + answer);
			}
		});
	});
	
	
	
	$("body").on("click", '.save_name', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const domain_name = $(this).closest('div.dropdown-block').find('input').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		const old_domain = cell.text();

		$('.notifyjs-corner').remove();
		
		if (domain_name == '') {
			$.notify(
                'Ошибка: Введите название домена!',
                {style: 'error_boot'}
            );
			return false;
		}
		else {
			doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsName', domain_id: domain_id, domain_name: domain_name, old_domain: old_domain}}, function(code, answer){
	            if (code === "success") {
	                if (answer != cell.text()) {
						cell.text(answer);
						$.notify(
	                        'Успешно: Изменения сохранены!',
	                        {style: 'success_boot'}
	                    );
                        cell.show();
					    td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
					    $(".js-close").trigger("click"); 						
	                }
                    else {
                        $.notify(
	                        'Ошибка: Такой домен уже существует!',
	                        {style: 'error_boot'}
	                    );
                    }					            
				}
			});
		}
	});
    
    $("body").on("click", '.edit-td[name="comment"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsComment', domain_comment: selectText}}, function(code, answer){
            if (code === "success") {
                td.html(html + answer);
			}
		});
	});	
	
	$("body").on("click", '.save_comment', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const domain_comment = $(this).closest('div.dropdown-block').find('input').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');

		$('.notifyjs-corner').remove();
        
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsComment', domain_id: domain_id, domain_comment: domain_comment}}, function(code, answer){
            if (code === "success") {
                if (answer != cell.text()) {
					cell.text(answer);
					$.notify(
                        'Успешно: Изменения сохранены!',
                        {style: 'success_boot'}
                    );
                    cell.show();
				    td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
				    $(".js-close").trigger("click"); 						
                }				            
			}
		});		
	});
	
	
	$("body").on("click", '.edit-td[name="setka"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		const limit_rows = 10;

		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsSetkas'}}, function(code, answer){
            if (code === "success") {
				td.html(html + answer);
							
				td.find('.select2').select2({
                    ajax: {
                        type: "POST",
                        url: "/admin/",                                
                        dataType: 'json',
                        data: function (params) {
                            return {
                                op: 'Controller',
                                object: 'DomainsController',
                                args: {
                                    mode : 'selectDomainsSetkas', 
                                    q : params.term, 
                                    page_limit : limit_rows,
                                    page : params.page
                                }
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.answer.items,
                                pagination: {
                                    more: (params.page * limit_rows) < data.answer.total           
                                }
                            };
                        },
                        cache: true,
                        delay: 250,
                    },
                    templateResult: function formatRepo(repo) {
                        if (repo.name) {
                            return repo.name;
                        }
                    },
                    templateSelection: function formatRepoSelection (repo) {
                        if (!repo.name) {                            
                        	return selectText;
                        }
                        else {
                            if (repo.name != '-Не задан-') {
                                $('.open').find(".select2-selection").addClass("passed");  
                            }
                            else {
                                $('.open').find(".select2-selection").removeClass("passed"); 
                            }                                                                      
                            return repo.name;
                        }
                    },
                });


								 
                $.ajax({
                    type: 'POST',
                    url: '/admin/',
                    dataType: "json",
                    timeout: 40000, 
                    data: {
                        op: 'Controller',
                        object: 'DomainsController',
                        args: {
                            mode : 'selectCurrentSetkas',
                            option_data : selectText,
                        },
                        responce: true
                    }
                }).then(function (data) {
                    for (var i = 0; i < data.length; i++) {
                        var option = new Option(data[i].text, data[i].id, true, true);
                        $('.open').find('.select2').append(option).trigger('change');
                        $('.open').find('.select2').trigger({
                            type: 'select2:select',
                            params: {
                                data: data[i]
                            }
                        });
                    }  
                    var option = new Option(data.name, data.id, true, true);
                    $('.open').find('.select2').append(option).trigger('change');
                    $('.open').find('.select2').trigger({
                        type: 'select2:select',
                        params: {
                            data: {'id' : data.id, 'text' : data.name}
                        }
                    }); 
                    $('.open').find("span.select2").find('option').remove();
                });

				
				if ($('.open').find("span [title]").text() != '') {
	                if ($('.open').find("span [title]").text() != "-Не задан-") {
	                    $('.open').find(".select2-selection").addClass("passed");
	                }
	            }   
	            else {
	                $('.open').find("span [title]").text("-Не задан-");
	            } 
			}
		});
	});
	
	
	
	$("body").on("click", '.save_setka', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const setka_id = $(this).closest('div.dropdown-block').find('select').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsSetkas', domain_id: domain_id, setka_id: setka_id}}, function(code, answer){
            if (code === "success") {
                if (answer != cell.text()) {
					cell.text(answer);
					$.notify(
                        'Успешно: Изменения сохранены!',
                        {style: 'success_boot'}
                    );						
                }   
				cell.show();
				td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
				$(".js-close").trigger("click");             
			}
		});
	});
	
	
	
	$("body").on("click", '.edit-td[name="expired"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsExpired', expired: selectText}}, function(code, answer){
            if (code === "success") {
                td.html(html + answer);
				//td.find('input').mask("99.99.9999", {placeholder: '01.01.2001', autoclear: false});
                
                td.find('.single-datepicker').daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true,
                    minYear: 2020,
                    maxYear: parseInt(moment().format('YYYY'),10),
                    locale: {
                        format: 'DD.MM.YYYY',
                        cancelLabel: 'Очистить'
                    }
                }, function(start, end, label) {
                    
                });   

                $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').off('show.daterangepicker');
                $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').off('cancel.daterangepicker');
                
                $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').on('show.daterangepicker', function(ev, picker) {
                    $('body > div.daterangepicker.dropdown-menu.single.show-calendar > div.ranges').attr('style', 'display:block;float: none;text-align: right;');
                    $('body > div.daterangepicker.dropdown-menu.single.show-calendar > div.ranges').find('.applyBtn').hide();
                });
            
            
                $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').on('cancel.daterangepicker', function(ev, picker) {
                    $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').val('');
                });
			}
		});
	});
	
	
	
	$("body").on("click", '.save_expired', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const domain_expired = $(this).closest('div.dropdown-block').find('input').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		if (domain_expired == '') {
			$.notify(
                'Ошибка: Введите дату окончания домена!',
                {style: 'error_boot'}
            );
			return false;
		}
		else {
			doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsExpired', domain_id: domain_id, domain_expired: domain_expired}}, function(code, answer){
	            if (code === "success") {
	                if (answer != cell.text()) {
						cell.text(answer);
						$.notify(
	                        'Успешно: Изменения сохранены!',
	                        {style: 'success_boot'}
	                    );						
	                }   
					cell.show();
					td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
					$(".js-close").trigger("click");             
				}
			});
		}
	});
	
	
	
	$("body").on("click", '.edit-td[name="no_active"]', function(){
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		const limit_rows = 10;

		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsNoActive'}}, function(code, answer){
            if (code === "success") {
				td.html(html + answer);
							
				td.find('.select2').select2({
                    ajax: {
                        type: "POST",
                        url: "/admin/",                                
                        dataType: 'json',
                        data: function (params) {
                            return {
                                op: 'Controller',
                                object: 'DomainsController',
                                args: {
                                    mode : 'selectDomainsNoActive', 
                                    q : params.term, 
                                    page_limit : limit_rows,
                                    page : params.page
                                }
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.answer.items,
                                pagination: {
                                    more: (params.page * limit_rows) < data.answer.total           
                                }
                            };
                        },
                        cache: true,
                        delay: 250,
                    },
                    templateResult: function formatRepo(repo) {
                        if (repo.name) {
                            return repo.name;
                        }
                    },
                    templateSelection: function formatRepoSelection (repo) {
                        if (!repo.name) {                            
                        	return selectText;
                        }
                        else {
                            if (repo.name != '-Не задан-') {
                                $('.open').find(".select2-selection").addClass("passed");  
                            }
                            else {
                                $('.open').find(".select2-selection").removeClass("passed"); 
                            }                                                                      
                            return repo.name;
                        }
                    },
                });


								 
                $.ajax({
                    type: 'POST',
                    url: '/admin/',
                    dataType: "json",
                    timeout: 40000, 
                    data: {
                        op: 'Controller',
                        object: 'DomainsController',
                        args: {
                            mode : 'selectCurrentNoActive',
                            option_data : selectText,
                        },
                        responce: true
                    }
                }).then(function (data) {
                    for (var i = 0; i < data.length; i++) {
                        var option = new Option(data[i].text, data[i].id, true, true);
                        $('.open').find('.select2').append(option).trigger('change');
                        $('.open').find('.select2').trigger({
                            type: 'select2:select',
                            params: {
                                data: data[i]
                            }
                        });
                    }  
                    var option = new Option(data.name, data.id, true, true);
                    $('.open').find('.select2').append(option).trigger('change');
                    $('.open').find('.select2').trigger({
                        type: 'select2:select',
                        params: {
                            data: {'id' : data.id, 'text' : data.name}
                        }
                    }); 
                    $('.open').find("span.select2").find('option').remove();
                });

				
				if ($('.open').find("span [title]").text() != '') {
	                if ($('.open').find("span [title]").text() != "-Не задан-") {
	                    $('.open').find(".select2-selection").addClass("passed");
	                }
	            }   
	            else {
	                $('.open').find("span [title]").text("-Не задан-");
	            } 
			}
		});
	});
	
	
	
	$("body").on("click", '.save_no_active', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const no_active = $(this).closest('div.dropdown-block').find('select').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsNoActive', domain_id: domain_id, no_active: no_active}}, function(code, answer){
            if (code === "success") {
                if (answer != cell.text()) {
					cell.text(answer);
					$.notify(
                        'Успешно: Изменения сохранены!',
                        {style: 'success_boot'}
                    );	
					if (no_active == 1) {
						td.closest('tr').addClass('disabled');
					}	
					else {
						td.closest('tr').removeClass('disabled');
					}				
                }   
				cell.show();
				td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
				$(".js-close").trigger("click");             
			}
		});
	});
	
	
		
	$("body").on("click", '.edit-td[name="server"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		const limit_rows = 10;

		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsServer'}}, function(code, answer){
            if (code === "success") {
				td.html(html + answer);
							
				td.find('.select2').select2({
                    ajax: {
                        type: "POST",
                        url: "/admin/",                                
                        dataType: 'json',
                        data: function (params) {
                            return {
                                op: 'Controller',
                                object: 'DomainsController',
                                args: {
                                    mode : 'selectDomainsServer', 
                                    q : params.term, 
                                    page_limit : limit_rows,
                                    page : params.page
                                }
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.answer.items,
                                pagination: {
                                    more: (params.page * limit_rows) < data.answer.total           
                                }
                            };
                        },
                        cache: true,
                        delay: 250,
                    },
                    templateResult: function formatRepo(repo) {
                        if (repo.name) {
                            return repo.name;
                        }
                    },
                    templateSelection: function formatRepoSelection (repo) {
                        if (!repo.name) {                            
                        	return selectText;
                        }
                        else {
                            if (repo.name != '-Не задан-') {
                                $('.open').find(".select2-selection").addClass("passed");  
                            }
                            else {
                                $('.open').find(".select2-selection").removeClass("passed"); 
                            }                                                                      
                            return repo.name;
                        }
                    },
                });


								 
                $.ajax({
                    type: 'POST',
                    url: '/admin/',
                    dataType: "json",
                    timeout: 40000, 
                    data: {
                        op: 'Controller',
                        object: 'DomainsController',
                        args: {
                            mode : 'selectCurrentServer',
                            option_data : selectText,
                        },
                        responce: true
                    }
                }).then(function (data) {
                    for (var i = 0; i < data.length; i++) {
                        var option = new Option(data[i].text, data[i].id, true, true);
                        $('.open').find('.select2').append(option).trigger('change');
                        $('.open').find('.select2').trigger({
                            type: 'select2:select',
                            params: {
                                data: data[i]
                            }
                        });
                    }  
                    var option = new Option(data.name, data.id, true, true);
                    $('.open').find('.select2').append(option).trigger('change');
                    $('.open').find('.select2').trigger({
                        type: 'select2:select',
                        params: {
                            data: {'id' : data.id, 'text' : data.name}
                        }
                    }); 
                    $('.open').find("span.select2").find('option').remove();
                });

				
				if ($('.open').find("span [title]").text() != '') {
	                if ($('.open').find("span [title]").text() != "-Не задан-") {
	                    $('.open').find(".select2-selection").addClass("passed");
	                }
	            }   
	            else {
	                $('.open').find("span [title]").text("-Не задан-");
	            } 
			}
		});
	});
	
	
	
	$("body").on("click", '.save_server', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const server_id = $(this).closest('div.dropdown-block').find('select').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsServer', domain_id: domain_id, server_id: server_id}}, function(code, answer){
            if (code === "success") {
                if (answer != cell.text()) {
					cell.text(answer);
					$.notify(
                        'Успешно: Изменения сохранены!',
                        {style: 'success_boot'}
                    );						
                }   
				cell.show();
				td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
				$(".js-close").trigger("click");             
			}
		});
	});
	
	
	
	$("body").on("click", '.edit-td[name="mirror"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		const limit_rows = 10;

		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsMirror'}}, function(code, answer){
            if (code === "success") {
				td.html(html + answer);
							
				td.find('.select2').select2({
                    ajax: {
                        type: "POST",
                        url: "/admin/",                                
                        dataType: 'json',
                        data: function (params) {
                            return {
                                op: 'Controller',
                                object: 'DomainsController',
                                args: {
                                    mode : 'selectDomainsMirror', 
                                    q : params.term, 
                                    page_limit : limit_rows,
                                    page : params.page
                                }
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.answer.items,
                                pagination: {
                                    more: (params.page * limit_rows) < data.answer.total           
                                }
                            };
                        },
                        cache: true,
                        delay: 250,
                    },
                    templateResult: function formatRepo(repo) {
                        if (repo.name) {
                            return repo.name;
                        }
                    },
                    templateSelection: function formatRepoSelection (repo) {
                        if (!repo.name) {                            
                        	return selectText;
                        }
                        else {
                            if (repo.name != '-Не задан-') {
                                $('.open').find(".select2-selection").addClass("passed");  
                            }
                            else {
                                $('.open').find(".select2-selection").removeClass("passed"); 
                            }                                                                      
                            return repo.name;
                        }
                    },
                });


								 
                $.ajax({
                    type: 'POST',
                    url: '/admin/',
                    dataType: "json",
                    timeout: 40000, 
                    data: {
                        op: 'Controller',
                        object: 'DomainsController',
                        args: {
                            mode : 'selectCurrentMirror',
                            option_data : selectText,
                        },
                        responce: true
                    }
                }).then(function (data) {
                    for (var i = 0; i < data.length; i++) {
                        var option = new Option(data[i].text, data[i].id, true, true);
                        $('.open').find('.select2').append(option).trigger('change');
                        $('.open').find('.select2').trigger({
                            type: 'select2:select',
                            params: {
                                data: data[i]
                            }
                        });
                    }  
                    var option = new Option(data.name, data.id, true, true);
                    $('.open').find('.select2').append(option).trigger('change');
                    $('.open').find('.select2').trigger({
                        type: 'select2:select',
                        params: {
                            data: {'id' : data.id, 'text' : data.name}
                        }
                    }); 
                    $('.open').find("span.select2").find('option').remove();
                });

				
				if ($('.open').find("span [title]").text() != '') {
	                if ($('.open').find("span [title]").text() != "-Не задан-") {
	                    $('.open').find(".select2-selection").addClass("passed");
	                }
	            }   
	            else {
	                $('.open').find("span [title]").text("-Не задан-");
	            } 
			}
		});
	});
	
	
	
	$("body").on("click", '.save_mirror', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const mirror_id = $(this).closest('div.dropdown-block').find('select').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsMirror', domain_id: domain_id, mirror_id: mirror_id}}, function(code, answer){
            if (code === "success") {
                if (answer != cell.text()) {
					cell.text(answer);
					$.notify(
                        'Успешно: Изменения сохранены!',
                        {style: 'success_boot'}
                    );						
                }   
				cell.show();
				td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
				$(".js-close").trigger("click");             
			}
		});
	});
	
	
	
	$("body").on("click", '.edit-td[name="owner"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		const limit_rows = 10;

		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsOwner'}}, function(code, answer){
            if (code === "success") {
				td.html(html + answer);
							
				td.find('.select2').select2({
                    ajax: {
                        type: "POST",
                        url: "/admin/",                                
                        dataType: 'json',
                        data: function (params) {
                            return {
                                op: 'Controller',
                                object: 'DomainsController',
                                args: {
                                    mode : 'selectDomainsOwner', 
                                    q : params.term, 
                                    page_limit : limit_rows,
                                    page : params.page
                                }
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.answer.items,
                                pagination: {
                                    more: (params.page * limit_rows) < data.answer.total           
                                }
                            };
                        },
                        cache: true,
                        delay: 250,
                    },
                    templateResult: function formatRepo(repo) {
                        if (repo.name) {
                            return repo.name;
                        }
                    },
                    templateSelection: function formatRepoSelection (repo) {
                        if (!repo.name) {  
							let answer = selectText;
							if (selectText == '') {
								answer = '-Не задан-';
							}
							else {
								$('.open').find(".select2-selection").addClass("passed");
							}                        
                        	return answer;
                        }
                        else {
                            if (repo.name != '-Не задан-') {
                                $('.open').find(".select2-selection").addClass("passed");  
                            }
                            else {
                                $('.open').find(".select2-selection").removeClass("passed"); 
                            }                                                                      
                            return repo.name;
                        }
                    },
                });

								 
                $.ajax({
                    type: 'POST',
                    url: '/admin/',
                    dataType: "json",
                    timeout: 40000, 
                    data: {
                        op: 'Controller',
                        object: 'DomainsController',
                        args: {
                            mode : 'selectCurrentOwner',
                            option_data : selectText,
                        },
                        responce: true
                    }
                }).then(function (data) {
                    for (var i = 0; i < data.length; i++) {
                        var option = new Option(data[i].text, data[i].id, true, true);
                        $('.open').find('.select2').append(option).trigger('change');
                        $('.open').find('.select2').trigger({
                            type: 'select2:select',
                            params: {
                                data: data[i]
                            }
                        });
                    }  
                    var option = new Option(data.name, data.id, true, true);
                    $('.open').find('.select2').append(option).trigger('change');
                    $('.open').find('.select2').trigger({
                        type: 'select2:select',
                        params: {
                            data: {'id' : data.id, 'text' : data.name}
                        }
                    }); 
                    $('.open').find("span.select2").find('option').remove();
                });
			}
		});
	});
	
	
	
	$("body").on("click", '.edit-td[name="purchased"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsPurchased', purchased: selectText}}, function(code, answer){
            if (code === "success") {
                td.html(html + answer);
				//td.find('input').mask("99.99.9999", {placeholder: '01.01.2001', autoclear: false});
                
                td.find('.single-datepicker').daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true,
                    minYear: 2020,
                    maxYear: parseInt(moment().format('YYYY'),10),
                    locale: {
                        format: 'DD.MM.YYYY',
                        cancelLabel: 'Очистить'
                    }
                }, function(start, end, label) {
                    
                });   

                $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').off('show.daterangepicker');
                $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').off('cancel.daterangepicker');
                
                $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').on('show.daterangepicker', function(ev, picker) {
                    $('body > div.daterangepicker.dropdown-menu.single.show-calendar > div.ranges').attr('style', 'display:block;float: none;text-align: right;');
                    $('body > div.daterangepicker.dropdown-menu.single.show-calendar > div.ranges').find('.applyBtn').hide();
                });
            
            
                $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').on('cancel.daterangepicker', function(ev, picker) {
                    $('#table_domains > tbody > tr > td > div.open').find('input.single-datepicker').val('');
                });                
			}
		});
	});
	
	
	
	$("body").on("click", '.save_purchased', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const domain_purchased = $(this).closest('div.dropdown-block').find('input').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		if (domain_purchased == '') {
			$.notify(
                'Ошибка: Введите дату покупки домена!',
                {style: 'error_boot'}
            );
			return false;
		}
		else {
			doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsPurchased', domain_id: domain_id, domain_purchased: domain_purchased}}, function(code, answer){
	            if (code === "success") {
	                if (answer != cell.text()) {
						cell.text(answer);
						$.notify(
	                        'Успешно: Изменения сохранены!',
	                        {style: 'success_boot'}
	                    );						
	                }   
					cell.show();
					td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
					$(".js-close").trigger("click");             
				}
			});
		}
	});
	
	
	
	$("body").on("click", '.save_owner', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const owner_id = $(this).closest('div.dropdown-block').find('select').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsOwner', domain_id: domain_id, owner_id: owner_id}}, function(code, answer){
            if (code === "success") {
                if (answer != cell.text()) {
					cell.text(answer);
					$.notify(
                        'Успешно: Изменения сохранены!',
                        {style: 'success_boot'}
                    );						
                }   
				cell.show();
				td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
				$(".js-close").trigger("click");             
			}
		});
	});
	
	
	
	$("body").on("click", '.edit-td[name="cost"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainsCost', domain_cost: selectText}}, function(code, answer){
            if (code === "success") {
                td.html(html + answer);
				td.find('input').numeric({ decimal : ".",  negative : false, scale: 0 });
			}
		});
	});
	
	
	
	$("body").on("click", '.save_cost', function(){
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const domain_cost = $(this).closest('div.dropdown-block').find('input').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		if (domain_cost == '') {
			$.notify(
                'Ошибка: Введите стоимость домена!',
                {style: 'error_boot'}
            );
			return false;
		}
		else {
			doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'saveDomainsCost', domain_id: domain_id, domain_cost: domain_cost}}, function(code, answer){
	            if (code === "success") {
	                if (answer != cell.text()) {
						cell.text(answer);
						$.notify(
	                        'Успешно: Изменения сохранены!',
	                        {style: 'success_boot'}
	                    );						
	                }   
					cell.show();
					td.attr('style', 'overflow: hidden;text-overflow: ellipsis;white-space: nowrap;');
					$(".js-close").trigger("click");             
				}
			});
		}
	});
	
	
	
	/*$("body").on("click", '#add_domain', function(){
        doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'addNewDomain'}}, function(code, answer){
            if (code === "success") {
                Domains.static_dt.ajax.reload();
                Domains.static_dt.page('last').draw('page');
                window.scrollTo(0, document.body.scrollHeight);
            }
            else {

            }
        });
    });*/

    $("body").on("click", '.delete_domain', function() {
		$('#table_domains').find('tr').removeClass('deleted_row');
        $('.notifyjs-corner').remove();
        $.notify(
            {
                title: "Вы действительно хотите сделать домен '" + $(this).closest('tr').find('td[name="name"]').text() +"' неактивным?"
            },
            { 
                style: 'submit_form',
                autoHide: false,
                clickToHide: false
            }
        );
        $(this).closest('tr').addClass('deleted_row');
        $('.notifyjs-submit_form-base .no').addClass('deleteDomain');
		$('.notifyjs-submit_form-base .yes').addClass('deleteDomain');
        $('.notifyjs-corner').attr('domain_name', $(this).closest('tr').find('td[name="name"]').text());
    });



	$(document).on('click', '.notifyjs-submit_form-base .no.deleteDomain', function() {
        $('#table_domains').find('tr').removeClass('deleted_row');
        $('.notifyjs-corner').remove();
    });

    

    $(document).on('click', '.notifyjs-submit_form-base .yes.deleteDomain', function() {
        var domain_name = $(this).closest('.notifyjs-corner').attr('domain_name');
        if (domain_name === undefined) {
            domain_name = '';
        }

        doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'deleteDomain', domain_name : domain_name}}, function(code, answer){
            if (code === "success" && answer != '') {
                $.notify(
                    'Успешно: домен ' + answer +' деактивирован!',
                    {style: 'success_boot', autoHideDelay: 5000}
                );
				$('#table_domains').find('td[name="name"]:contains(' + answer + ')').parent().addClass('disabled');
				$('#table_domains').find('td[name="name"]:contains(' + answer + ')').parent().removeClass('deleted_row');
				$('#table_domains').find('td[name="name"]:contains(' + answer + ')').parent().find('.fa-trash').addClass('fa-undo enable_domain');
				$('#table_domains').find('td[name="name"]:contains(' + answer + ')').parent().find('.fa-trash').removeClass('fa-trash delete_domain');		
            }
            else {
                $.notify(
                    'Ошибка: домен не был деактивирован!',
                    {style: 'error_boot', autoHideDelay: 5000}
                );
            }
        });
        
        $('.notifyjs-corner').remove();
    });



	$("body").on("click", '.enable_domain', function() {
		$(this).closest('tr').removeClass('disabled');
        $('.notifyjs-corner').remove();
        $.notify(
            {
                title: "Вы действительно хотите сделать домен '" + $(this).closest('tr').find('td[name="name"]').text() +"' активным?"
            },
            { 
                style: 'submit_form',
                autoHide: false,
                clickToHide: false
            }
        );
        $('.notifyjs-submit_form-base .no').addClass('enableDomain');
		$('.notifyjs-submit_form-base .yes').addClass('enableDomain');
        $('.notifyjs-corner').attr('domain_name', $(this).closest('tr').find('td[name="name"]').text());
    });



	$(document).on('click', '.notifyjs-submit_form-base .no.enableDomain', function() {
        $(this).closest('tr').addClass('disabled');
        $('.notifyjs-corner').remove();
    });


	
	$(document).on('click', '.notifyjs-submit_form-base .yes.enableDomain', function() {
        var domain_name = $(this).closest('.notifyjs-corner').attr('domain_name');
        if (domain_name === undefined) {
            domain_name = '';
        }

        doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'enableDomain', domain_name : domain_name}}, function(code, answer){
            if (code === "success" && answer != '') {
                $.notify(
                    'Успешно: домен ' + answer +' активирован!',
                    {style: 'success_boot', autoHideDelay: 5000}
                );
				$('#table_domains').find('td[name="name"]:contains(' + answer + ')').parent().find('.fa-undo').addClass('fa-trash delete_domain');
				$('#table_domains').find('td[name="name"]:contains(' + answer + ')').parent().find('.fa-undo').removeClass('fa-undo enable_domain');		
            }
            else {
                $.notify(
                    'Ошибка: домен не был активирован!',
                    {style: 'error_boot', autoHideDelay: 5000}
                );
            }
        });
        
        $('.notifyjs-corner').remove();
    });


	$("body").on("click", '.edit-td[name="domain_sites"]', function(){	
		const domain_id = $(this).closest('tr').find('td[name="id"]').text();
		const html = $(this).html();
		const td = $(this);
		
		doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'showDomainSites'}}, function(code, answer){
            if (code === "success") {
				td.html(html + answer);
            	DomainSites.initialize($('#DomainsController-showDomainSites'), domain_id);	
			}
        });			
	});
    
    $('body').on('click', '.copy', function(e){		
		$('#table_domains').find('.tooltip').remove(); 
		
        var copytext = document.createElement('input');
        copytext.value = $(this).closest('td').find('.cell').text();
        document.body.appendChild(copytext);
        copytext.select();
        document.execCommand('copy');
        document.body.removeChild(copytext);
        
       	$.notify(
            'Успешно: Скопировано!',
            {style: 'success_boot'}
        );	
	
		e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    $("body").on("click", '#add_domain', function(){
         
         doPostAjax({op: 'Controller', object: 'DomainsController', args: {mode: 'ShowModalDomain'}}, function(code, answer){
           if (code === "success") {    
                $("#openDomain").remove();
                var pop_html =  $('#popups').html();            
                $('#popups').html(pop_html + answer);             
                
                $("#popups select").each(function(){
                    $(this).select2();
                    $(this).data('select2').$selection.css('height', '34px');
                    $(this).parent().find('.select2-container').css({'width': '100%', 'margin-bottom' : '0px'});
                    $(this).data('select2').$selection.find('span').first().css('padding-top', '2px');
                });
                
                date_config = {
                    singleDatePicker: true,
                    showDropdowns: true,
                    minYear: 2020,
                    maxYear: parseInt(moment().format('YYYY'),10),
                    locale: {
                        format: 'DD.MM.YYYY',
                        cancelLabel: 'Очистить'
                    }
                };                    
                
                $("#popups .date").each(function(){
                    
                    $(this).daterangepicker(date_config); 
                
                    $(this).on('apply.daterangepicker', function(ev, picker) {
                     
                    });
                    
                    $(this).on('cancel.daterangepicker', function(ev, picker) {
        
                    });
                }); 
                
                $("#openDomain").modal("show");
            } 
         });
         
         return false;        
    });
    
    $("body").on("click", ".js-modal-domain-save", function(){
       
        args_v = fill_mas([$("#openDomain")]);
        args_v['mode'] = 'saveDomain';
         
        doPostAjax({op: 'Controller', object: 'DomainsController', args: args_v}, function(code, answer){
                            
            if (code == "error") {
                $.notify(
                    'Ошибка: ' + answer,
                    {style: 'error_boot'}
                );
            }
            
            if (code == "success") { 
       	        $.notify(
                    'Успешно: Домен добавлен!',
                    {style: 'success_boot'}
                );
                
                $('#openDomain .alert').text('ID домена ' + answer).show(); 
                $('#openDomain [name=name]').val('');                 
             }
        });
         
        return false; 
    });
});