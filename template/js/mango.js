$(function () {

    AppMangos.initialize($('#MangoController-showMangos'));
    
    $('#mango_table').on('update:filter', AppMangos.static_dt, function() {
        AppMangos.static_dt.ajax.reload(function (json) {
			             
        });           
    }); 
    
    //move datatable's search to other filters
    $('#body_mangos').find('.col-xs-5').first().prepend($('#mango_table_filter'));	
    $('#mango_table_filter').attr('style', 'width: 165px !important;');
    $('#mango_table_filter').addClass('col-xs-3 maxcontent zero-padd mr10');
    $('#mango_table_filter > label').attr('style', 'font-weight: normal !important; margin-bottom: 0px;');
    $('#mango_table_filter > label > input').attr('style', 'height: 34px; font-size: 14px');
	$('#mango_table_filter').append($('#search_label'));
    
    //move download button to datatable's footer
    $('#mango_table_wrapper').find('.datatable-footer').find('.summ').append($('#div_export_mangos'));
    $('#mango_table_wrapper').find('.datatable-footer').find('.summ').attr('style', 'margin-top: 7px;');
    
    //move cancel filter button
    $('#FilterController-showFilters').find('.wrapper').last().after($('#cancel_filter'));

    //move tag info tooltip button after cancel filter
    //$('#cancel_filter').after($('#tag_info'));
    
    //fix overflow x for filters
    $('#FilterController-showFilters').find('.wrapper').each(function() {
        $(this).find('.overflow').attr('style', 'overflow-x:hidden');
    });
    
    $("#status").select2({minimumResultsForSearch:-1});
    $("#status").data('select2').$selection.css('height', '34px');
    $("#div_status").find('.select2-container').css('width', '210px');
    $("#status").data('select2').$selection.find('span').first().css('padding-top', '2px'); 
    
    $("#type").select2({minimumResultsForSearch:-1});
    $("#type").data('select2').$selection.css('height', '34px');
    $("#div_type").find('.select2-container').css('width', '130px');
    $("#type").data('select2').$selection.find('span').first().css('padding-top', '2px');
    
    $("#channel").select2({minimumResultsForSearch:-1});
    $("#channel").data('select2').$selection.css('height', '34px');
    $("#div_channel").find('.select2-container').css('width', '115px');
    $("#channel").data('select2').$selection.find('span').first().css('padding-top', '2px');
    
    $("body").on("click", '#dt_filt_red, #dt_filt_yellow, #dt_filt_blue, #no_scenario', function(){
        if ($(this).hasClass('checked')) {
            $(this).removeClass('checked');
            $(this).attr('value', '0');
        }
        else {
            $(this).addClass('checked');  
            $(this).attr('value', '1');          
        }
        //$('#mango_table').trigger('update:filter'); 
    });
    
     $('#body_mangos').on("click", '#calc_analytics', function(e){       
        $('#mango_table').trigger('update:filter');
    });
    
    $("body").on("mouseenter", '#mango_table tr td', function(){       	
		const td_html = $(this).html();
        var td_text = $(this).text();
        
        if ($(this).attr('name') == 'name') {
            error_value = $(this).closest('tr').attr('error_value');
            if (error_value) {
                td_text = error_value;
            }
        }
        
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
    
    $("body").on("mouseleave", '#mango_table tr td', function(){
        $('#mango_table').find('.tooltip').remove();        
    });
    
    $('#body_mangos').on("click", '#export_mangos', function(e){
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
        var args = AppMangos.static_dt.ajax.params().args;
        args.save = 1;

        $.ajax({
            type: 'POST',
            url: '/admin/',
            dataType: "json",
            timeout: 40000, 
            data: {
                op: 'Controller',
                object: 'MangoController',
                args: args,
                responce: true
            }
        }).done(function(data){
            var $a = $("<a>");
            $a.attr("href",data.data);
            $("body").append($a);
            $a.attr("download","Mangos.csv");
            $a[0].click();
            $a.remove();
            $("#overlay").remove();
        });        
    });
    
    $("body").on("click", ".dropdown-menu", function(e){
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    $('#FilterController-showFilters[page=mango]').find('.dropdown-menu').find('.flat').on('ifClicked', function(event){
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
    
    $('#FilterController-showFilters[page=mango]').find('.switch_check').on('click', function() {
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
    
    $('#FilterController-showFilters[page=mango]').find('.all_checked').on('click', function() {
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
    
    $('#FilterController-showFilters[page=mango]').find('.clean_checked').on('click', function() {
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
        $('#FilterController-showFilters[page=mango]').find('.wrapper').find('.dropdown').each(function() {
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
                    //$('#mango_table').trigger('update:filter');
                }
            }
        });        
    });
    
    $('body').on('click', '#FilterController-showFilter', function() {
        if ($(this).closest('.page-filter').attr('page') == 'mango') {
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
        $('#FilterController-showFilters[page=mango]').find('.wrapper').each(function() {
            $(this).find('.dropdown-menu').find('.overflow').find('.flat').each(function() {
                $(this).iCheck('uncheck');
            });
            $(this).find('.tag').find('.count').text(0);
            $(this).find('.tag').find('.rigthtbutton').removeAttr('style');
        });
        $('#filter_tags').html('<div id="empty_tag" style="margin-top: 30px;"></div><div id="tags_menu" hidden="true"></div>');
        deleteCookie('filterTags');
        //$('#mango_table').trigger('update:filter');
    }); 
    
    $('#type').on("change", function (e) {
		//$('#mango_table').trigger('update:filter');
	});
    
    $('#status').on("change", function (e) {
		//$('#mango_table').trigger('update:filter');
	});
    
    $("body").on("click", '#refresh_uis', function(){
        doPostAjax({op: 'Controller', object: 'SourceController', args: {mode: 'refreshUIS'}}, function(code, answer){
            if (code === "success") {
                $.notify(
                    'Успешно: юис обновлен!',
                    {style: 'success_boot', autoHideDelay: 10000}
                );
            }
            else {
                $.notify(
                    'Ошибка: юис не был обновлен!',
                    {style: 'error_boot', autoHideDelay: 10000}
                );
            }
        });
    });
    
    
    $("body").on("click", '.js-chart', function(){
        
         var mango_id = $(this).closest('tr').attr('data-id');
         var $t = $(this);
         $t.attr("class", "fa fa-spinner fa-pulse");
         
         doPostAjax({op: 'Controller', object: 'MangoController', args: {mode: 'ShowChart', mango_id: mango_id, 
                        datepicker: {start: $('.datepicker').data('start'), end: $('.datepicker').data('end')}}}, function(code, answer){
           if (code === "success") {    
                $("#openAnalytics").remove();
                var pop_html =  $('#popups').html();            
                $('#popups').html(pop_html + answer);  
                $("#openAnalytics").modal("show");
                $t.attr("class", "fa js-chart fa-area-chart");
            } 
         });
         
         return false;        
    });
    
    $("body").on("click", '.js-tag-filter', function(){
        
        var mango_id = $("[data-mango_id]").data("mango_id");
        var $t = $(this);
        $tr = $t.closest("tr");
        $tr.toggleClass("success");
        
        var tags_value = [];
        
        $("#openAnalytics #body_analytics table tr").each(function() {
            if ($(this).hasClass("success"))
                tags_value.push($(this).data('tags'));    
        });
        
        tags_value = tags_value.join();
        $("#openAnalytics #body_analytics").find('tbody').css('filter', 'blur(3px)');
        
        doPostAjax({op: 'Controller', object: 'MangoController', args: {mode: 'ShowChart', mango_id: mango_id, tags: tags_value,
                        datepicker: {start: $('.datepicker').data('start'), end: $('.datepicker').data('end')}}}, function(code, answer){
            if (code === "success") {
                $answer = $(answer);
                $('#openAnalytics #body_analytics').html($answer.find("#body_analytics").html());
               	$("#openAnalytics #body_analytics").find('tbody').css('filter', 'none'); 
            }
        });
        
        return false;
    });
    
});