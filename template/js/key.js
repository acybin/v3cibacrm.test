$(function () {

    AppKeys.initialize($('#KeyController-showKeys'));
    
    $('#key_table').on('update:filter', AppKeys.static_dt, function() {
        AppKeys.static_dt.ajax.reload(function (json) {
			             
        });           
    }); 
    
    //move datatable's search to other filters
    $('#body_keys').find('.col-xs-5').first().prepend($('#key_table_filter'));	
    $('#key_table_filter').attr('style', 'width: 165px !important;');
    $('#key_table_filter').addClass('col-xs-3 maxcontent zero-padd mr10');
    $('#key_table_filter > label').attr('style', 'font-weight: normal !important; margin-bottom: 0px;');
    $('#key_table_filter > label > input').attr('style', 'height: 34px; font-size: 14px');
	$('#key_table_filter').append($('#search_label'));
    
    //move download button to datatable's footer
    $('#key_table_wrapper').find('.datatable-footer').find('.summ').append($('#div_export_keys'));
    $('#key_table_wrapper').find('.datatable-footer').find('.summ').attr('style', 'margin-top: 7px;');
    
    $("body").on("mouseenter", '#key_table tr td', function(){       	
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
    
    $("body").on("click", '#no_tags', function(){
        if ($(this).hasClass('checked')) {
            $(this).removeClass('checked');
            $(this).attr('value', '0');
        }
        else {
            $(this).addClass('checked');  
            $(this).attr('value', '1');          
        }
        $('#key_table').trigger('update:filter'); 
    });
    
    $("body").on("mouseleave", '#key_table tr td', function(){
        $('#key_table').find('.tooltip').remove();        
    });
    
    $('#body_keys').on("click", '#export_keys', function(e){
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
        var args = AppKeys.static_dt.ajax.params().args;
        args.save = 1;
        args['mode'] = 'downloadKeys';

        $.ajax({
            type: 'POST',
            url: '/admin/',
            dataType: "json",
            timeout: 40000, 
            data: {
                op: 'Controller',
                object: 'KeyController',
                args: args,
                responce: true
            }
        }).done(function(data){
            /*var $a = $("<a>");
            $a.attr("href",data.data);
            $("body").append($a);
            $a.attr("download","Keys.csv");
            $a[0].click();
            $a.remove();*/
            
            $.notify(
                'Успешно: файл отправлен на формирование!',
                {style: 'success_boot'}
            );
            
            $("#overlay").remove();
        });        
    });
    
    $("body").on("click", '.edit-td[name="tags"]', function(){	
		const td = $(this);
        const html = $(this).html();
		const limit_rows = 10;		
		const cell = $(this).find('.cell');
	
        key_id = $(this).closest('tr').data('id');

		doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: 'showKeysTags'}}, function(code, answer){
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
                                object: 'KeyController',
                                args: {
                                    mode : 'selectKeysTags', 
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
                        object: 'KeyController',
                        args: {
                            mode : 'getKeysTags',
                            key_id : key_id,
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
        const key_id = $(this).closest('tr').data('id');
        
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
		
		doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: 'saveKeysTags', key_id: key_id, tags: tags}}, function(code, answer){
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
		$('#key_table').find('.cell').show();
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
    
    $("body").on("click", '#add_key', function(){
        doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: 'addNewKey'}}, function(code, answer){
            if (code === "success") {
                AppKeys.static_dt.ajax.reload();
                AppKeys.static_dt.page('last').draw('page');
                window.scrollTo(0, document.body.scrollHeight);
            }
            else {

            }
        });
    });
    
    $("body").on("click", '.edit-td[name="name"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		
		doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: 'showName', key_name: selectText}}, function(code, answer){
            if (code === "success") {
                td.html(html + answer);
			}
		});
	});
    
    $("body").on("click", '.save_name', function(){
		const key_id = $(this).closest('tr').find('td[name="id"]').text();
		const key_name = $(this).closest('div.dropdown-block').find('input').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');

		$('.notifyjs-corner').remove();
		
		if (key_name == '') {
			$.notify(
                'Ошибка: Введите ключ!',
                {style: 'error_boot'}
            );
			return false;
		}
		else {
			doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: 'saveName', key_id: key_id, key_name: key_name}}, function(code, answer){
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
		}
	});
    
    $("body").on("click", '.edit-td[name="marker"]', function(){	
		const td = $(this);
        const html = $(this).html();
        const selectText = $(this).find('.cell').text();
		
		doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: 'showMarker', key_marker: selectText}}, function(code, answer){
            if (code === "success") {
                td.html(html + answer);
			}
		});
	});
    
    $("body").on("click", '.save_marker', function(){
		const key_id = $(this).closest('tr').find('td[name="id"]').text();
		const key_marker = $(this).closest('div.dropdown-block').find('input').val();
		const cell = $(this).closest('td').find('.cell');
		const td = $(this).closest('td');

		$('.notifyjs-corner').remove();		
		doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: 'saveMarker', key_id: key_id, key_marker: key_marker}}, function(code, answer){
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
    
    $("body").on("click", '.delete_key', function() {
		$('#key_table').find('tr').removeClass('deleted_row');
        $('.notifyjs-corner').remove();
        $.notify(
            {
                title: "Вы действительно хотите удалить запись?"
            },
            { 
                style: 'submit_form',
                autoHide: false,
                clickToHide: false
            }
        );
        $(this).closest('tr').addClass('deleted_row');
        $('.notifyjs-submit_form-base .no').addClass('actionKey');
		$('.notifyjs-submit_form-base .yes').addClass('actionKey');
        $('.notifyjs-corner').attr('action', 'deleteKey');
        $('.notifyjs-corner').attr('key_id', $(this).closest('tr').attr('data-id'));
    });
    
    $("body").on("click", '.enable_key', function() {
        $('.notifyjs-corner').remove();
        $.notify(
            {
                title: 'Вы действительно хотите активировать запись?'
            },
            { 
                style: 'submit_form',
                autoHide: false,
                clickToHide: false
            }
        );
        $(this).closest('tr').removeClass('disabled');
        $('.notifyjs-submit_form-base .no').addClass('actionKey');
		$('.notifyjs-submit_form-base .yes').addClass('actionKey');
        $('.notifyjs-corner').attr('action', 'enableKey');
        $('.notifyjs-corner').attr('key_id', $(this).closest('tr').attr('data-id'));
    });
    
    
    $(document).on('click', '.notifyjs-submit_form-base .no.actionKey', function() {
        const key_id = $('.notifyjs-corner').attr('key_id');
        $('#key_table').find('tr[data-id=' + key_id + ']').removeClass('deleted_row');
        if ($('.notifyjs-corner').attr('action') == 'enableKey') {
            $('#key_table').find('tr[data-id=' + key_id + ']').addClass('disabled');
        }
        $('.notifyjs-corner').remove();
    });
    

    $(document).on('click', '.notifyjs-submit_form-base .yes.actionKey', function() {
       const mode = $(this).closest('.notifyjs-corner').attr('action');
       var key_id = $(this).closest('.notifyjs-corner').attr('key_id');
        if (key_id === undefined) {
            key_id = 0;
        }

        doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: mode, key_id : key_id}}, function(code, answer){
            if (code === "success") {
                const row = $('#key_table').find('tr[data-id=' + key_id + ']');
                $.notify(
                    answer,
                    {style: 'success_boot', autoHideDelay: 5000}
                );
                if (mode == 'deleteKey') {
                    if (answer == 'Успешно: ключ деактивирован из-за связки с кампанией!') {
                        row.removeClass('deleted_row');
                        row.addClass('disabled');
                        row.find('td').last().html('<i class="fa fa-undo enable_key" aria-hidden="true"></i>');
                    }
                    else if (answer == 'Успешно: ключ удален!') {
                        row.remove();
                    }                    
                }
                else if (mode == 'enableKey') {
                    row.find('td').last().html('<i class="fa fa-trash delete_key" aria-hidden="true"></i>');
                }
            }
            else {
                $.notify(
                    answer,
                    {style: 'error_boot', autoHideDelay: 5000}
                );
            }
        });
        
        $('.notifyjs-corner').remove();
    });
    
    $("body").on("click", '.edit-td[name="campaigns"]', function(){	
		const key_id = $(this).closest('tr').find('td[name="id"]').text();
		const html = $(this).html();
		const td = $(this);
		
		doPostAjax({op: 'Controller', object: 'KeyController', args: {mode: 'showCampaigns', key_id: key_id}}, function(code, answer){
            if (code === "success") {
				td.html(html + answer);
            	AppKeyCampaigns.initialize($('#KeyController-showCampaigns'));	
                
                //fix some datatable's styles
                $('#datatable_campaigns_filter').find('input').attr('placeholder', 'Поиск...');
			}
        });			
	});
    
    
    var progressBar = $('#progressbar');
    
    $('#file_form').on('submit', function(e){
        
        if (!$("#uploaded").val()) return false;
        
        e.preventDefault();
        var $that = $(this),
        formData = new FormData($that.get(0));
        
        $.ajax({
          url: $that.attr('action'),
          type: $that.attr('method'),
          contentType: false,
          processData: false,
          data: formData,
          dataType: 'json',
          xhr: function(){
            var xhr = $.ajaxSettings.xhr(); // получаем объект XMLHttpRequest
            xhr.upload.addEventListener('progress', function(evt){ // добавляем обработчик события progress (onprogress)
              if(evt.lengthComputable) { // если известно количество байт
                // высчитываем процент загруженного
                var percentComplete = Math.ceil(evt.loaded / evt.total * 100);
                // устанавливаем значение в атрибут value тега <progress>
                // и это же значение альтернативным текстом для браузеров, не поддерживающих <progress>
                progressBar.width(percentComplete + '%');
                
              }
            }, false);
            return xhr;
          },
          success: function(json){
            if(json){
              $that.after(json);
            }
            if (json['code'] == 'success') {
                $.notify(
                    'Файл успешно загружен!',
                    {style: 'success_boot', autoHideDelay: 5000}
                );
            }
            else {
                 $.notify(
                    json['answer'],
                    {style: 'error_boot', autoHideDelay: 5000}
                );
            }
            
          }
        });    
    });
    
 });