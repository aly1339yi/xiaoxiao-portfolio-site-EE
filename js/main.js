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
################################# COMMERCIAL WORKS ###################################
*/


	
		
	$(document).on('click', '.commercial-ajax-trigger', function() {
		

		var $this = $(this);
		
		
		
		
		var ajaxURL = $this.data('link');
	
/*
		$('.work-item-container').css({
			
		});
*/
		
		
		
		
		$( '#ajax-wrapper' ).load( ajaxURL, function() {
			
			$('.commercial-work-item').mousewheelStopPropagation();
			
			$('.fitvids-wrapper').fitVids();			

			$('.work-item-container').css('display','block');
			
			
			
				
			VerticalAlignTitleCol();
			
			setTimeout(function(){	
				
				$('.work-item-container').animate({opacity: 1}, 400, function() {
					
					$('.commercial-work-item .detail-wrapper').removeClass('slide-down');
					
					setTimeout(function(){	
						$('.commercial-work-item .relative-entry-link-wrapper').addClass('slide-out');		
					}, 1000);			
					
					
				});					

			}, 400);


		});		
	});
	
	

	$(document).on('click', '.work-item-container .close-icon', function() {
		
		
		$( '.work-item-container' ).css({
			'display':'none',
			'opacity':'0'
		});		
		$( '#ajax-wrapper' ).empty();
	});	
	
		
/*
################################# INDIE WORKS ###################################
*/
	

	$('.isotope-wrapper').waitForImages( function() {	
		$('.isotope-wrapper').isotope({itemSelector: '.isotope-item'}).isotope();
	});
	
	$('.indie-works-filter-wrapper').on( 'click', 'li', function() {
	  var filterValue = $(this).attr('data-filter');
	  $('.isotope-wrapper').isotope({ filter: filterValue }).isotope();
	});





	$(document).on('click', '.indie-ajax-trigger', function() {
		
		var ajaxURL = $(this).data('link');
		
		$('.work-item-container .close-icon').css('color','#f4f4f4');
			
		$( '#ajax-wrapper' ).load( ajaxURL, function() {
			
			$('.relative-entry-link').css('color','#f4f4f4');
			
			$('.indie-work-item').mousewheelStopPropagation();
			
			$( '.work-item-container' ).css('display','block');

			
			if($('.video-wrapper').length){
				
				$('.fitvids-wrapper').fitVids();
				
			}
			else if($('.carousel-wrapper').length){
						
				$('.owl-carousel').owlCarousel({
					
					itemsCustom: [[0, 2], [1200, 3], [1800, 4]],
		
					afterInit: function(){
						$('.owl-carousel').waitForImages( function() {
							var paddingTop = $(window).height()/2-$('.owl-wrapper').height()/2;
							$('.carousel-wrapper').css({
								'padding-top': paddingTop+'px',
								'opacity': '1'
							});
							
						});
						
				    },
				    afterUpdate : function(){
						var paddingTop = $(window).height()/2-$('.owl-wrapper').height()/2;
						$('.carousel-wrapper').css('padding-top', paddingTop+'px');						
				    }					
					
				})
					
			}

			
			setTimeout(function(){	
				
				$( '.work-item-container' ).animate({opacity: 1}, 300, function() {
					
					//$('.indie-work-item .detail-wrapper').removeClass('slide-down');
					
					setTimeout(function(){	
						$('.indie-work-item .relative-entry-link-wrapper').addClass('slide-out');		
					}, 1000);			
					
					
				});					

			}, 400);
		});		
	});


/*
################################# REEL ###################################
*/

	$(document).on('click', '.breakdown-list-wrapper-trigger', function() {
		$('.breakdown-list-wrapper').removeClass('slide-right');
		
	});

	$(document).on('click', '.breakdown-list-close-icon-wrapper', function() {
		
		$('.breakdown-list-wrapper').addClass('slide-right');
		
	});









		
	
});