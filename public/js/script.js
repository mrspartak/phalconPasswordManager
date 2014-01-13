var App = {
	vr : {
		user : {}	
	},
	init : function(){		
		$.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
			options.data = options.data + '&token='+ App.vr.user.token;
		});
	},
	page : {
		hideData : function(id) {
			$('.row' + id).find('.textarea').addClass('hidden').find('textarea').val('');
			$('.row' + id).find('.secret_inputs').show();
		},
		error : function(text) {
			$('.error-block').stop().hide().removeClass('hidden').html(text).slideDown().delay(4000).slideUp();
		}
	},
	data : {
		send : {
			deleteRow : function(id){
				secret_key = $('.row' + id).find('.secret_key').val().trim();
				if(secret_key.length > 0)
					$.post('/ajax', {action: 'delete_row', data: {id: id, secret_key: secret_key}}, function(){
						$('.row' + id).remove();
					}).error(function(jqXHR, textStatus, errorThrown){
							App.page.error('Error getting data. Server responsed with message: "' + errorThrown +'"');
					})
				else
					alert('Must be more than 3 characters.')
			}
		},
		get : {
			getSecretData : function(id){
				secret_key = $('.row' + id).find('.secret_key').val().trim();
				if(secret_key.length > 0)
					$.post('/ajax', {action: 'get_secret', data: {id: id, secret_key: secret_key}}, function(data){
						if(data.text_secret) {
							$('.row' + id).find('.textarea').removeClass('hidden').find('textarea').val(data.text_secret);
							$('.row' + id).find('.secret_inputs').hide().find('.secret_key').val('');
						} else
							alert('Error getting data')
					}, 'json').error(function(jqXHR, textStatus, errorThrown){
						App.page.error('Error getting data. Server responsed with message: "' + errorThrown +'"');
					})
				else
					alert('Must be more than 3 characters.')
			}
		}
	}
}