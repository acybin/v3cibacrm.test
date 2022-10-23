
$(function () {
    setTagsFromCookies();

    $('#default_sort').text(1);
    History.initialize($('#HistoryController-showHistory'));            

    moment.locale('ru');
    $('body').find('.datepicker').daterangepicker(AppPageFilters.filterDateRangePickerConfigDefault, AppPageFilters.filterDatePickerCallback);


    $("#status").select2({minimumResultsForSearch:-1});
    $("#status").data('select2').$selection.css('height', '34px');
    $("#div_status").find('.select2-container').css('width', '210px');
    $("#status").data('select2').$selection.find('span').first().css('padding-top', '2px'); 


    $("#active").select2({minimumResultsForSearch:-1});
    $("#active").data('select2').$selection.css('height', '34px');
    $("#div_active").find('.select2-container').css('width', '115px');
    $("#active").data('select2').$selection.find('span').first().css('padding-top', '2px');


    $("body").on("click", ".dropdown-menu", function(e){
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
        

    $('#FilterController-showFilters[page=history]').find('.dropdown-menu').find('.flat').on('ifClicked', function(event){
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



    $('#FilterController-showFilters[page=history]').find('.switch_check').on('click', function() {
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


    $('#FilterController-showFilters[page=history]').find('.all_checked').on('click', function() {
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


    $('#FilterController-showFilters[page=history]').find('.clean_checked').on('click', function() {
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
        $('#table_history').trigger('update:filter');
    });

    
    $(document).on('click', 'body', function() {
        $('#FilterController-showFilters[page=history]').find('.wrapper').find('.dropdown').each(function() {
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
                    $('#table_history').trigger('update:filter');
                }
            }
        });        
    });
    
    
    $('body').on('click', '#FilterController-showFilter', function() {
        if ($(this).closest('.page-filter').attr('page') == 'history') {
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


    $("body").on("click", '#database', function(e){
        doPostAjax({op: 'Controller', object: 'HistoryController', args: {mode: 'setHistoryDatabase'}}, function(code, answer){
            if (code === "success") {                 
                window.location.reload();
            }
        });
    });


    $('#body_history').on("click", '#export_history', function(e){
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
        var args = History.static_dt.ajax.params().args;
        args.save = 1;

        $.ajax({
            type: 'POST',
            url: '/admin/',
            dataType: "json",
            timeout: 40000, 
            data: {
                op: 'Controller',
                object: 'HistoryController',
                args: args,
                responce: true
            }
        }).done(function(data){
            var $a = $("<a>");
            $a.attr("href",data.data);
            $("body").append($a);
            $a.attr("download","History.xlsx");
            $a[0].click();
            $a.remove();
            $("#overlay").remove();
        });        
    });


    $('body').on("click", '#cancel_filter', function(e){
        $('#FilterController-showFilters[page=history]').find('.wrapper').each(function() {
            $(this).find('.dropdown-menu').find('.overflow').find('.flat').each(function() {
                $(this).iCheck('uncheck');
            });
            $(this).find('.tag').find('.count').text(0);
            $(this).find('.tag').find('.rigthtbutton').removeAttr('style');
        });
        $('#filter_tags').html('<div id="empty_tag" style="margin-top: 30px;"></div><div id="tags_menu" hidden="true"></div>');
        deleteCookie('filterTags');
        $('#table_history').trigger('update:filter');
    });    


    $('#body_history').on('mouseenter', '.highlight', function () {
        if (!$(this).closest('tr').hasClass('details')) {
            $(this).closest('tr').attr('style', 'background-color: rgba(38,185,354,0.1)');
        }
    });
    

    $('#body_history').on('mouseleave', '.highlight', function () {
        if (!$(this).closest('tr').hasClass('details')) {
            $(this).closest('tr').removeAttr('style');
        }
    });


    $("body").on("mouseenter", '#table_history tr td:nth-child(2), #table_history tr td:nth-child(3), #table_history tr td:nth-child(4), #table_history tr td:nth-child(5)', function(){
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

    $("body").on("mouseleave", '#table_history tr td:nth-child(2), #table_history tr td:nth-child(3), #table_history tr td:nth-child(4), #table_history tr td:nth-child(5)', function(){
        $('#table_history').find('.tooltip').remove();        
    }); 


    $("body").on("mouseenter", '.fa-exclamation-triangle', function(){
        const td_html = $(this).closest('td').html();
        const history_status = $(this).closest('tr').attr('history_status');
        if ($(this).closest('td').find('.tooltip').length == 0) {
            $(this).closest('td').html(td_html + '<div class="tooltip">' + history_status + '</div>');
            $(this).closest('td').find('.tooltip').tooltip('show');
        }
    });


    $("body").on("mouseleave", '.fa-exclamation-triangle', function(){
        if ($(this).closest('td').find('.tooltip').length != 0) {
            $('#table_history').find('.tooltip').remove();
        }
    });


    //fix overflow x for filters
    $('#FilterController-showFilters').find('.wrapper').each(function() {
        $(this).find('.overflow').attr('style', 'overflow-x:hidden');
    });


    //move datatable's search to other filters
    $('#body_history').find('.col-xs-5').first().prepend($('#table_history_filter'));
    $('#table_history_filter').attr('style', 'width: 165px !important;');
    $('#table_history_filter').addClass('col-xs-3 maxcontent zero-padd mr10');
    $('#table_history_filter > label').attr('style', 'font-weight: normal !important; margin-bottom: 0px;');
    $('#table_history_filter > label > input').attr('style', 'height: 34px; font-size: 14px');
    $('#table_history_wrapper').find('.row').first().remove();
       
    //move download button to datatable's footer
    $('#table_history_wrapper').find('.datatable-footer').find('.summ').append($('#div_export_history'));
    $('#table_history_wrapper').find('.datatable-footer').find('.summ').attr('style', 'margin-top: 7px;');

    //move cancel filter button
    $('#FilterController-showFilters').find('.wrapper').last().after($('#cancel_filter'));

    //move tag info tooltip button after cancel filter
    //$('#cancel_filter').after($('#tag_info'));
});