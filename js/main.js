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



		
	$('.commercial-works-wrapper').on('click', '.work-item-thumbnail.ajax-trigger', function(event) {
		event.preventDefault();

		var ajaxURL = $(this).data('link');
		
		

		$( '#ajax-wrapper' ).load( ajaxURL, function() {

			$('.fitvids-wrapper').fitVids();

			$( '#ajax-wrapper' ).delay(400).fadeIn(400,'easeInOutExpo',function(){

				VerticalAlignTitleCol();
				//history.pushState({}, '', ajaxURL);
				$('.commercial-work-item .detail-wrapper').addClass('slide-up');
				
			});

		});		
	});
	
	

	$(document).on('click', '.commercial-work-item .close-icon', function(event) {
		event.preventDefault();
		$( '#ajax-wrapper' ).fadeOut(0, function() {
			$( '#ajax-wrapper' ).empty();
		});
		
	});	
	
	
	
	
	
	
	
	
	
	
	
	
	



		
	
});