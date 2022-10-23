$(function () {

    DashboardSetkas.initialize($('#DashboardController-showSetkas'));
    DashboardDaily.initialize($('#DashboardController-showDaily'));
    
    $('body').find('.selectpicker2').selectpicker('selectAll', true);
    
    $("#type").select2({minimumResultsForSearch:-1});
    $("#type").data('select2').$selection.css('height', '34px');
    $("#div_type").find('.select2-container').css('width', '115px');
    $("#type").data('select2').$selection.find('span').first().css('padding-top', '2px'); 

    $('#body_dashboard_setkas').on("click", '#calc_analytics', function(e){
        DashboardSetkas.reload();
    });
    
    $('#body_dashboard_daily').on("click", '#calc_analytics_daily', function(e){
        DashboardDaily.reload();
    });

    $('#body_dashboard_daily').on("click", '#clear_cache', function(e){
        DashboardDaily.reload(1);
    });
    
    $("body").on("click", '.js-interval', function(){
        
        if ($(this).data("interval_value") == 0) return false;
        $("[name=interval_value]").val($(this).data("interval_value"));
        DashboardSetkas.reload();
        
        return false;
    });
    
    $("body").on('click', 'th.sorting_desc, th.sorting_asc', function(e){
       
        if ($(this).hasClass("sorting_desc"))
            dir = 0;
        else
            dir = 1;
            
        $("[name=order_dir]").val(dir);
       
        DashboardSetkas.reload();
        return false; 
    }); 
     
    $("body").on('click', '#table_dashboard_setkas th:not(.sorting_desc, .sorting_asc)', function(){

        index = $(this).closest("table").find("th").index($(this));
        $("[name=order_column]").val(index);
        
        DashboardSetkas.reload();
        return false;
    });
    
    $("body").on("click", '#procent', function(){
        if ($(this).hasClass('checked')) {
            $(this).removeClass('checked');
            $(this).attr('value', '0');
        }
        else {
            $(this).addClass('checked');  
            $(this).attr('value', '1');          
        }
        return false;
    });
    
});