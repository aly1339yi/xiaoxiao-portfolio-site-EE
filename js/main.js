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
################################# WINDOW RESIZE #########################################
*/

	$(window).on('resize',function(event) {
	
		centerAlignFeather();
	});

/*
################################# GENERAL ###################################
*/



	$('.fitvids-wrapper').fitVids();
	
	
	$(document).on('click', '.anchor-span', function() {
		if($(this).attr('data-link')){
			window.location= $(this).data('link');
		} 
	});
	
		
	$('body').waitForImages( function() {
		
		
		$( 'body' ).animate({opacity: 1}, 800, function() {
			// Animation complete.
			if($('body').hasClass('homepage')){
				setTimeout(function(){	
					$('.main-navbar').addClass('slide-in');
					$('.logo span').textillate();			
				}, 800);
			}
			
			if($('body').hasClass('indie-works')){
				setTimeout(function(){	
					$('.indie-works-filter-wrapper ul').addClass('slide-up');			
				}, 2000);
			}	
			
			
			
			
		});		
			
	});
	

/*
################################# HOMEPAGE ###################################
*/

	

					
	$('#scene').parallax();
	

	centerAlignFeather();	



/*
################################# REEL ###################################
*/








/*
################################# COMMERCIAL WORKS ###################################
*/


	
		
	$(document).on('click', '.commercial-ajax-trigger', function() {
		
		
		
		var ajaxURL = $(this).data('link');
		
		
		$( '#ajax-wrapper' ).load( ajaxURL, function() {

			$('.fitvids-wrapper').fitVids();

			$( '.work-item-container' ).delay(400).fadeIn(400,'easeInOutExpo',function(){

				$('.commercial-work-item').mousewheelStopPropagation();

				VerticalAlignTitleCol();
				//history.pushState({}, '', ajaxURL);
				$('.commercial-work-item .detail-wrapper').removeClass('slide-down');
				
				setTimeout(function(){	
					$('.commercial-work-item .relative-entry-link-wrapper').addClass('slide-out');		
				}, 1500);
				
				
			});

		});		
	});
	
	

	$(document).on('click', '.work-item-container .close-icon', function() {
		
		
		$( '.work-item-container' ).fadeOut(0, function() {
			$( '#ajax-wrapper' ).empty();
		});
		
	});	
	
		
/*
################################# INDIE WORKS ###################################
*/
	

	$('.isotope-wrapper').waitForImages( function() {	
		$('.isotope-wrapper').isotope({itemSelector: '.isotope-item'}).isotope();
	});
	
	$('.indie-works-filter-wrapper').on( 'click', 'li', function() {
	  var filterValue = $(this).attr('data-filter');
	  $('.isotope-wrapper').isotope({ filter: filterValue });
	});





	$(document).on('click', '.indie-ajax-trigger', function() {
		
		
		
		var ajaxURL = $(this).data('link');
		
		
		$( '#ajax-wrapper' ).load( ajaxURL, function() {

			$('.fitvids-wrapper').fitVids();

			$( '.work-item-container' ).delay(400).fadeIn(400,'easeInOutExpo',function(){

				$('.commercial-work-item').mousewheelStopPropagation();

/*
				VerticalAlignTitleCol();

				$('.commercial-work-item .detail-wrapper').removeClass('slide-down');
				
				setTimeout(function(){	
					$('.commercial-work-item .relative-entry-link-wrapper').addClass('slide-out');		
				}, 1500);
*/
				
				
			});

		});		
	});







		
	
});