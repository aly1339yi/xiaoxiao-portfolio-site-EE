$( document ).ready(function() {


/*
################################# FUNCTION BLOCK #########################################
*/

	function VerticalAlignTitleCol(){
		
		var parentHeight = $('.commercial-work-item .detail-wrapper').height();
		$('.commercial-work-item .title.col').css({
			'height': parentHeight,
			'line-height': parentHeight+'px'
		});
	}

	function centerAlignFeather(){
		
		var featherMarginLeftValue = $('.feather-wrapper').width()/(-2);

		$('.feather-wrapper').css('margin-left',featherMarginLeftValue);			
	}


	
/*
################################# END OF FUNCTION BLOCK ###################################
*/


/*
################################# WINDOW RESIZE #########################################
*/

	$(window).on('resize',function(event) {

		centerAlignFeather();
	});

/*
################################# END OF WINDOW RESIZE ###################################
*/


		
	$('body').imagesLoaded( function() {
		$('body').css('opacity', '1');
		$('body.homepage .main-navbar').delay(1000).addClass('slide-in');
	});
	
	
					
	$('#scene').parallax();
	
	$('.fitvids-wrapper').fitVids();
	
	centerAlignFeather();	



		
	$(document).on('click', '.commercial-ajax-trigger', function() {
		
		
		
		var ajaxURL = $(this).data('link');
		
		
		$( '#ajax-wrapper' ).load( ajaxURL, function() {

			$('.fitvids-wrapper').fitVids();

			$( '.commercial-work-item-container' ).delay(400).fadeIn(400,'easeInOutExpo',function(){

				
				VerticalAlignTitleCol();
				//history.pushState({}, '', ajaxURL);
				$('.commercial-work-item .detail-wrapper').addClass('slide-up');
				
				$('.commercial-work-item .relative-entry-link-wrapper').addClass('slide-out');
			});

		});		
	});
	
	

	$(document).on('click', '.commercial-work-item-container .close-icon', function() {
		
		
		$( '.commercial-work-item-container' ).fadeOut(0, function() {
			$( '#ajax-wrapper' ).empty();
		});
		
	});	
	
		
	
/*
	$(document).on('mouseover', '.relative-entry-link-wrapper', function() {
		
		$(this).removeClass('slide-out');
		
	});	
*/	
	
	
	
	
	
	
	
	



		
	
});