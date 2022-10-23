let DashboardSetkas = {
    
    reload: function() {
        $("#table_dashboard_setkas").css('filter', 'blur(3px)');
            
        args = {};
        args.mode = 'refreshSetkas';

        var channels = [];
        check = 0;
        $('body').find('.bootstrap-select').find('li').each(function() {
            if ($(this).hasClass('selected')) {
                channels.push($(this).find('.text').text());
            }
            else {
                check = -1;
            }
        });
        if (channels.length === 0 && check === -1) {
            channels = -1;
        }
        args.channels_filter = channels;
        
        args.procent = $('#procent').attr('value');        
        args.datepicker = {start: $('.datepicker').data('start'), end: $('.datepicker').data('end')};
        
        args.interval_value = $("[name=interval_value]").val();
        
        args.order_dir = $("[name=order_dir]").val(); 
        args.order_column = $("[name=order_column]").val();
        
        doPostAjax({op: 'Controller', object: 'DashboardController', args: args}, function(code, answer){
            if (code === "success") {
                $('#table_dashboard_setkas')[0].outerHTML = answer;
               	$("#table_dashboard_setkas").css('filter', 'none');   
                   
                $('#dashboard_load').remove();                       
            }
        });
    },
    
    initialize: function ($wrapper) {
        
        this.reload();
    }
}

let DashboardDaily = {
    
    reload: function(clear) {
        $("#table_dashboard_daily").css('filter', 'blur(3px)');
            
        args = {};
        args.mode = 'refreshDaily';
        args.clear = clear;
        args.type = $('#type').val();
        
        doPostAjax({op: 'Controller', object: 'DashboardController', args: args}, function(code, answer){
            if (code === "success") {
                $('#table_dashboard_daily')[0].outerHTML = answer;
               	$("#table_dashboard_daily").css('filter', 'none');   
                   
                $('#dashboard_daily_load').remove();                       
            }
        });
    },
    
    initialize: function ($wrapper) {
        
        this.reload();
    }
}       