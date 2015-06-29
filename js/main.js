$( document ).ready(function() {
		
	$('body').imagesLoaded( function() {
		$('body').css('opacity', '1');
	});
					
	$('#scene').parallax();	
	$('.fitvids-wrapper').fitVids();

	function VerticalAlignTitleCol(){
		
		var parentHeight = $('.commercial-work-item .detail-wrapper').height();
		$('.commercial-work-item .title.col').css({
			'height': parentHeight,
			'line-height': parentHeight+'px'
		});
	}

	
			
	$('.commercial-works-wrapper').on('click', '.work-item-thumbnail.ajax-trigger', function(event) {
		event.preventDefault();

		var ajaxURL = $(this).data('link');

		

		$( '#ajax-wrapper' ).load( ajaxURL, function() {

			$('.fitvids-wrapper').fitVids();

			$( '#ajax-wrapper' ).delay(400).fadeIn(400,'easeInOutExpo',function(){

				VerticalAlignTitleCol();

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