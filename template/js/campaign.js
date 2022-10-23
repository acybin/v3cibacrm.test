$(function () {

    AppCampaigns.initialize($('#CampaignController-showCampaigns'));
    
    $('#campaign_table').on('update:filter', AppCampaigns.static_dt, function() {
        AppCampaigns.static_dt.ajax.reload(function (json) {
			             
        });           
    }); 
    
    //move datatable's search to other filters
    $('#body_campaigns').find('.col-xs-5').first().prepend($('#campaign_table_filter'));	
    $('#campaign_table_filter').attr('style', 'width: 165px !important;');
    $('#campaign_table_filter').addClass('col-xs-3 maxcontent zero-padd mr10');
    $('#campaign_table_filter > label').attr('style', 'font-weight: normal !important; margin-bottom: 0px;');
    $('#campaign_table_filter > label > input').attr('style', 'height: 34px; font-size: 14px');
	$('#campaign_table_filter').append($('#search_label'));
    
    //move download button to datatable's footer
    $('#campaign_table_wrapper').find('.datatable-footer').find('.summ').append($('#div_export_campaigns'));
    $('#campaign_table_wrapper').find('.datatable-footer').find('.summ').append($('#div_lid_requests'));
    $('#campaign_table_wrapper').find('.datatable-footer').find('.summ').attr('style', 'margin-top: 7px;');
    
    //move cancel filter button
    $('#FilterController-showFilters').find('.wrapper').last().after($('#cancel_filter'));

    //move tag info tooltip button after cancel filter
    //$('#cancel_filter').after($('#tag_info'));
    
    //fix overflow x for filters
    $('#FilterController-showFilters').find('.wrapper').each(function() {
        $(this).find('.overflow').attr('style', 'overflow-x:hidden');
    });
    
    $("#active").select2({minimumResultsForSearch:-1});
    $("#active").data('select2').$selection.css('height', '34px');
    $("#div_active").find('.select2-container').css('width', '115px');
    $("#active").data('select2').$selection.find('span').first().css('padding-top', '2px');
    
   	$("#suffics").select2({minimumResultsForSearch:-1});
    $("#suffics").data('select2').$selection.css('height', '34px');
    $("#div_suffics").find('.select2-container').css('width', '115px');
    $("#suffics").data('select2').$selection.find('span').first().css('padding-top', '2px');
    
    $('#active').on("change", function (e) {
		$('#campaign_table').trigger('update:filter');
	});
    
    $('#suffics').on("change", function (e) {
		$('#campaign_table').trigger('update:filter');
	});
    
    $("body").on("click", '#no_partner', function(){
        if ($(this).hasClass('checked')) {
            $(this).removeClass('checked');
            $(this).attr('value', '0');
        }
        else {
            $(this).addClass('checked');  
            $(this).attr('value', '1');          
        }
        $('#campaign_table').trigger('update:filter');
    });
    
    $("body").on("mouseenter", '#campaign_table tr td', function(){       	
		const td_html = $(this).html();
        var td_text = $(this).text();        
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
    
    $("body").on("mouseleave", '#campaign_table tr td', function(){
        $('#campaign_table').find('.tooltip').remove();        
    });
    
    $('#body_campaigns').on("click", '#export_campaigns', function(e){
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
        var args = AppCampaigns.static_dt.ajax.params().args;
        args.save = 1;

        $.ajax({
            type: 'POST',
            url: '/admin/',
            dataType: "json",
            timeout: 40000, 
            data: {
                op: 'Controller',
                object: 'CampaignController',
                args: args,
                responce: true
            }
        }).done(function(data){
            var $a = $("<a>");
            $a.attr("href",data.data);
            $("body").append($a);
            $a.attr("download","Campaigns.csv");
            $a[0].click();
            $a.remove();
            $("#overlay").remove();
        });        
    });
    
    $('#body_campaigns').on("click", '#lid_requests', function(e){
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
        var args = {mode: 'lidRequests'};

        $.ajax({
            type: 'POST',
            url: '/admin/',
            dataType: "json",
            timeout: 40000, 
            data: {
                op: 'Controller',
                object: 'CampaignController',
                args: args,
                responce: true
            }
        }).done(function(data){
            var $a = $("<a>");
            $a.attr("href",data.data);
            $("body").append($a);
            $a.attr("download","LidRequests.csv");
            $a[0].click();
            $a.remove();
            $("#overlay").remove();
        });        
    });
    
    $('#FilterController-showFilters[page=campaign]').find('.dropdown-menu').find('.flat').on('ifClicked', function(event){
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
    
    $('#FilterController-showFilters[page=campaign]').find('.switch_check').on('click', function() {
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
    
    $('#FilterController-showFilters[page=campaign]').find('.all_checked').on('click', function() {
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
    
    $('#FilterController-showFilters[page=campaign]').find('.clean_checked').on('click', function() {
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
    
    $(document).on('click', 'body', function() {
        $('#FilterController-showFilters[page=campaign]').find('.wrapper').find('.dropdown').each(function() {
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
                    $('#campaign_table').trigger('update:filter');
                }
            }
        });        
    });
    
    $('body').on('click', '#FilterController-showFilter', function() {
        if ($(this).closest('.page-filter').attr('page') == 'campaign') {
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
    
    $('body').on("click", '#cancel_filter', function(e){
        $('#FilterController-showFilters[page=campaign]').find('.wrapper').each(function() {
            $(this).find('.dropdown-menu').find('.overflow').find('.flat').each(function() {
                $(this).iCheck('uncheck');
            });
            $(this).find('.tag').find('.count').text(0);
            $(this).find('.tag').find('.rigthtbutton').removeAttr('style');
        });
        $('#filter_tags').html('<div id="empty_tag" style="margin-top: 30px;"></div><div id="tags_menu" hidden="true"></div>');
        deleteCookie('filterTags');
        $('#campaign_table').trigger('update:filter');
    }); 
    
    
    $("body").on("click", '.edit-td[name="tags"]', function(){	
		const td = $(this);
        const html = $(this).html();
		const limit_rows = 10;		
		const cell = $(this).find('.cell');
	
        campaign_id = $(this).closest('tr').data('id');
        suffics = $(this).closest('tr').data('suffics');

		doPostAjax({op: 'Controller', object: 'CampaignController', args: {mode: 'showCampaignsTags'}}, function(code, answer){
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
                                object: 'CampaignController',
                                args: {
                                    mode : 'selectCampaignsTags', 
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
			            return repo.text;                                                     
			        },
			        templateSelection: function formatRepoSelection (repo) {
			        	return repo.text;
			        },
                });
												               
			    var tagSelect = $('.open').find('.select2');
                var all_tags = {};
                var selectedTags = tagSelect.closest('td').find('.cell');
                                
                $(selectedTags).find('span').each(function(number, span) {
                    var tag_id = $(this).attr('tag-id');
                    var tag_name = $(this).attr('tag-name');
                    if (!all_tags[tag_name]) {
                        all_tags[tag_name] = [];
                        all_tags[tag_name].push(tag_id);
                    }
                    else {
                        all_tags[tag_name].push(tag_id);
                    }
                });

                $.ajax({
                    type: 'POST',
                    url: '/admin/',
                    dataType: "json",
                    timeout: 40000, 
                    data: {
                        op: 'Controller',
                        object: 'CampaignController',
                        args: {
                            mode : 'getCampaignsTags',
                            campaign_id : campaign_id,
                            suffics : suffics,
                            all_tags: all_tags
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
                    $('.open').find("span.select2").find('option').remove();
					if (all_tags.length != 0) {
						$('.open').find(".select2-selection").addClass("passed");
					}
                });				
			}
		});
	});
    
    $("body").on("click", '.save_tags', function(){
        const campaign_id = $(this).closest('tr').data('id');
        const suffics = $(this).closest('tr').data('suffics');
        
		const tags = $(this).closest('div.dropdown-block').find('select').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		let labels = [];
		
		$('.notifyjs-corner').remove();
		
		$(this).closest('div.dropdown-block').find('.select2').find('li').each(function() {
			if ($(this).hasClass('select2-selection__choice')) {
				labels.push($(this).attr('title'));
			}			
		});
		
		doPostAjax({op: 'Controller', object: 'CampaignController', args: {mode: 'saveCampaignsTags', campaign_id: campaign_id, suffics: suffics, tags: tags}}, function(code, answer){
            if (code === "success") {
                if (answer == 'success') {					
					let tags = "";
					for (let i = 0; i < labels.length; i++) {
						tags = tags + '<span class="label label-grey">' + labels[i] + '</span>&nbsp;';
					}
					cell.html(tags);
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
    
    $("body").on("click", ".dropdown-menu", function(e){
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    $("body").on("click", '.edit-td', function(){
		$('#campaign_table').find('.cell').show();
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
    
    $("body").on("click", '.edit-td[name="nls_source_name"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		const limit_rows = 10;
        
        source_id = $(this).closest('tr').attr('nls_source_id');
        if (source_id === undefined) {
            source_id = false;
        }

		doPostAjax({op: 'Controller', object: 'CampaignController', args: {mode: 'showSourceName'}}, function(code, answer){
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
                                object: 'CampaignController',
                                args: {
                                    mode : 'selectSourceName', 
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
                      	     if (source_id != 0) {
                                $('.open').find(".select2-selection").addClass("passed"); 
                                return selectText + ' ' + source_id;
                            }
                            else {
                                $('.open').find(".select2-selection").removeClass("passed"); 
                                return '-Не задан-';
                            }
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
                        object: 'CampaignController',
                        args: {
                            mode : 'selectCurrentSource',
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
    
    $("body").on("click", '.save_source', function(){
        
		const campaign_id = $(this).closest('tr').data('id');
        const suffics = $(this).closest('tr').data('suffics');
        
		const source_id = $(this).closest('div.dropdown-block').find('select').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');
		
		$('.notifyjs-corner').remove();
		
		doPostAjax({op: 'Controller', object: 'CampaignController', args: {mode: 'saveSourceName', campaign_id: campaign_id, source_id: source_id,
                        suffics: suffics}}, function(code, answer){
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
    
    $("body").on("click", '.edit-td[name="count_keys"]', function(){
        
	    const campaign_id = $(this).closest('tr').data('id');
		const html = $(this).html();
		const td = $(this);
		
		doPostAjax({op: 'Controller', object: 'CampaignController', args: {mode: 'showKeys', campaign_id: campaign_id}}, function(code, answer){
            if (code === "success") {
				td.html(html + answer);
            	AppCampaignKeys.initialize($('#CampaignController-showKeys'));	
                
                //fix some datatable's styles
                $('#datatable_keys_filter').find('input').attr('placeholder', 'Поиск...');
			}
        });			
	});
    
    $("body").on("click", '#refresh_key', function(){
        doPostAjax({op: 'Controller', object: 'CampaignController', args: {mode: 'refreshKey'}}, function(code, answer){
            if (code === "success") {
                $.notify(
                    'Успешно: ключи обновлены!',
                    {style: 'success_boot', autoHideDelay: 10000}
                );
            }
            else {
                $.notify(
                    'Ошибка: ключи не были обновлен!',
                    {style: 'error_boot', autoHideDelay: 10000}
                );
            }
        });
    });
    
 });