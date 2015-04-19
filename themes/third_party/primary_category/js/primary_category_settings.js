jQuery(function($j) {
	
	$j('.primary_cat_settings').click(function(event) {
		event.preventDefault();	
		var action = $j(this).attr('rel');
		
		if(action=='uncheck'){		
			$j(this).parents('table').find('input').attr('checked','');
			$j(this).attr('rel','check');		
		}else {
			$j(this).parents('table').find('input').attr('checked','checked');
			$j(this).attr('rel','uncheck');
		}	
			
	});
});