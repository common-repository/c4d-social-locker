///////// TWITTER ///////////
function c4d_social_locker_t_post(status, parent) {
	jQuery.getJSON(
		c4d_social_locker.ajax_url, 
		{
			action: 'c4d_social_locker_t_post',
			data: {
				status: status
			}
		},
		function(response){
			parent.addClass("active");
		}
	);
}
function c4d_social_locker_t_click_event() {
	jQuery('.c4d-social-locker-twitter-button').on('click', function(event){
		event.preventDefault();
		var status = jQuery(this).attr('data-text') + ' ' + jQuery(this).attr('data-url');
		if (status.length > 140) {
			alert("Status is over 140 characters. It will be trimmed.");
		}
		c4d_social_locker_t_post(status, jQuery(this).parents('.c4d-social-locker'));
	});
}
//////// GOOGLE ///////
		function endInteraction() {
			alert(1);
		}
		function startInteraction() {
			alert(2);
		}
(function($){
	$(document).ready(function(){
		var check_twitter_auth = setInterval(function(){
			if (typeof c4d_social_locker.twitter_auth != undefined && c4d_social_locker.twitter_auth == 1) {
				clearInterval(check_twitter_auth);
				$('.c4d-social-locker-twitter-button').removeAttr('onClick');
				c4d_social_locker_t_click_event();
			}
		}, 1000);
		$.getJSON(
			c4d_social_locker.ajax_url, 
			{
				action: 'c4d_social_locker_t_auth'
			},
			function(response){
				if (response.r == 0) {
					$('.c4d-social-locker-twitter-button').attr('onClick', "window.open('"+ response.url +"', 'Twitter', 'height=600,width=600'); return false;");
				} else {
					c4d_social_locker_t_click_event();
				}
		});

		///// FACEBOOK /////
		
		window.fbAsyncInit = function() {
		    FB.init({
		      appId            : c4d_social_locker.appid,
		      autoLogAppEvents : true,
		      xfbml            : true,
		      version          : 'v2.10'
		    });
		    FB.AppEvents.logPageView();
		};

		(function(d, s, id){
		     var js, fjs = d.getElementsByTagName(s)[0];
		     if (d.getElementById(id)) {return;}
		     js = d.createElement(s); js.id = id;
		     js.src = "//connect.facebook.net/en_US/sdk.js";
		     fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));

		$('.c4d-social-locker-fb-button').on('click', function(event){
			event.preventDefault();
			var self = this;
			FB.ui(
			{
			    method: 'share',
			    quote: $(this).attr('data-text'),
			    href: $(this).attr('data-url'),
			},
			  	function(response) {
			    	if (response && !response.error_message) {
			      		$(self).parents('.c4d-social-locker').addClass('active');
			    	} 
			  	}
			);
		});
	});
})(jQuery);