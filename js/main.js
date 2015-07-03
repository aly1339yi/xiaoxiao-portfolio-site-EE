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
	});
					
	$('#scene').parallax();
	
	$('.fitvids-wrapper').fitVids();
	
	centerAlignFeather();	



		
	$(document).on('click', '.commercial-ajax-trigger', function() {

		if($('#ajax-wrapper').is(':empty')){
			
			$( '#ajax-wrapper' ).empty();
		}
		
		var ajaxURL = $(this).data('link');
		
		
		$( '#ajax-wrapper' ).load( ajaxURL, function() {

			$('.fitvids-wrapper').fitVids();

			$( '.commercial-work-item-container' ).delay(400).fadeIn(400,'easeInOutExpo',function(){

				
				VerticalAlignTitleCol();
				//history.pushState({}, '', ajaxURL);
				$('.commercial-work-item .detail-wrapper').addClass('slide-up');
				
			});

		});		
	});
	
	

	$(document).on('click', '.commercial-work-item-container .close-icon', function() {
		$( '.commercial-work-item-container' ).fadeOut(0, function() {
			$( '#ajax-wrapper' ).empty();
		});
		
	});	
	
		
	
	
	
	
	
	
	
	
	
	



		
	
});