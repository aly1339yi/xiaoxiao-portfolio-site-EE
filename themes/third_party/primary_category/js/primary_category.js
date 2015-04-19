jQuery(function($j) {
	var capitalize = function(s){ //v1.0
	    return s.replace(/\w+/g, function(a) {
	        return a.charAt(0).toUpperCase() + a.substr(1).toLowerCase();
	    });
	};

	var auto_chk = '';

	$j('a.pc_accordian').click(function(event) {
		event.preventDefault();
		var id 		= $j(this).parent('div').attr('id').substr(4);
		var state 	= ( $j(this).attr('title') == 'Expand' ) ? 'collapse' : 'expand';
		var span 	= '<span class="'+state+'">'+capitalize(state)+'</span>';
		var img	 	= '<img src="<?=PATH_CP_GBL_IMG;?>'+state+'.gif" border="0" width="10" height="10" alt="'+capitalize(state)+'" />';
		$j(this).html(span);
		$j(this).attr('title',capitalize(state));
		$j('#sub_'+id).toggle();
	});

	$j('.pc_divider, .pc_divider a.pc_accordian').click(function(event) {
		event.preventDefault();
		var id 		= $j(this).hasClass('pc_divider') ? $j(this).attr('id').substr(4) : $j(this).closest('.pc_divider').attr('id').substr(4);
		
		if($j(this).hasClass('pc_divider'))
		{
			var state 	= ( $j(this).children('a.pc_accordian').attr('title') == 'Expand' ) ? 'collapse' : 'expand';
		}
		else
		{
			var state 	= ( $j(this).attr('title') == 'Expand' ) ? 'collapse' : 'expand';
		}
		
		var span 	= '<span class="'+state+'">'+capitalize(state)+'</span>';
		var img	 	= '<img src="<?=PATH_CP_GBL_IMG;?>'+state+'.gif" border="0" width="10" height="10" alt="'+capitalize(state)+'" />';
		
		if($j(this).hasClass('pc_divider'))
		{
			$j(this).children('a.pc_accordian').html(span);
			$j(this).children('a.pc_accordian').attr('title',capitalize(state));
		}
		else
		{
			$j(this).html(span);
			$j(this).attr('title',capitalize(state));
		}
		
		$j('#sub_'+id).toggle();
	});

	/**
	 * Button to add a primary category (no swap)
	 */
	$j('a.pc_arrow').click(function(event) {
		event.preventDefault();
		PC_set_primary( $j(this).parent('div') );
	});

	/**
	 * Button to swap a primary category
	 */
	$j('a.pc_swap').click(function(event) {
		event.preventDefault();
		PC_set_primary( $j(this).parent('div') );
	});

	/**
	 * Display of Swap icon on hover
	 */
	$j('#sub_hold_field_category .box').hover(function () {
		if( $(this).find("a.pc_arrow").is(":hidden") )
		{
			$(this).find("a.pc_swap").toggleClass('invisible');
		}
	});

	/**
	 * Button to remove primary category
	 */
	$j('a.pc_delete').click(function(event) {
		event.preventDefault();
		PC_delete_primary( $j(this).parent('div.primary') );
	});

	function PC_set_primary(obj) {
		var d		= $j(obj).clone();
		var id		= $j(obj).attr('id').substr(4);
		var grp_id	= $j(obj).closest('div.cat_group').attr('id');
		var chk		= $j('#cat_'+id).children('input').attr('checked');

		$j('#pc_del_primary').remove();

		if ( auto_chk != '' )
		{
			$j('#cat_'+auto_chk).children('input').attr('checked','');
		}

		if ( chk != true )
		{
			auto_chk = id;
			$j('#cat_'+id).children('input').attr('checked','checked');
		}

		d.children('input').remove();
		d.children('a.pc_arrow').remove();
		d.children('a.pc_accordian').remove();
		d.css({margin:'0', padding:'0 0px', fontWeight:'bold'}).addClass('primary');
		d.attr('id','prime_cat_'+id);
		d.find("label").attr('for', ''); // Don't want right-hand prim cats to uncheck categories on the left

		span	= document.createElement('span');



		a = document.createElement('a');
		a.className = "pc_delete";
		a.title 	= "Delete the primary category";
		a.href 		= '';
		a.appendChild(span);

		d.prepend(a);
		d.attr('data-pc_id', id);
		d.attr('data-pc_grp_id', grp_id);

		// Display the visually hidden category group
		// next to the category name
		d.find("small.cat_group").show();
		
		d.children('a.pc_delete').bind('click', function(event) {
			event.preventDefault();
			PC_delete_primary( $j(this).parent('div.primary') );
		});

		//	----------------------------------------
		// 	Build an array of category group_ids from
		// 	currently selected primary categories
		//	----------------------------------------
		var pc_arr = [];
		 
		$j("#prime_cat_box div").each(function() {
			pc_arr.push( $j(this).data('pc_grp_id') );
		});

		//	----------------------------------------
		//	Append if the category group is not in the array
		//	----------------------------------------
		if($j.inArray( grp_id, pc_arr) < 0)
		{
			$j("div#"+grp_id+" a.pc_arrow").hide();

			// Show the swap button in the left pane
			$j($j(obj).find("a.pc_swap")).removeClass('invisible');
			
			$j('#prime_cat_box').append(d);
		}
		else
		{
			// Swap!
			$j("div.prime_box div").find('[data-pc_grp_id='+grp_id+']').replaceWith(d);	
		}

		// Hide the swap buttons. We don't need them to show there.
		$j('#prime_cat_box').find("a.pc_swap").addClass('invisible');

		var ids = '';
		
		$("#prime_cat_box div").each(function() {
			ids = ids + $(this).data('pc_id') + '|'; 
		});

		$j('#pc_field').val(ids);
	}

	function PC_delete_primary(obj) {
		var id		= $j(obj).attr('id').substr(10);
		var grp_id	= $j(obj).data('pc_grp_id');

		if ( id == auto_chk )
		{
			$j('#cat_'+auto_chk).children('input').attr('checked','');
			auto_chk = '';
		}

		obj.remove();

		$('<input type="hidden" />').attr('id', 'pc_del_primary').attr('name', 'pc_del_primary').attr('value',$j('#pc_field').val()).appendTo($j('#pc_holder'));

		//	----------------------------------------
		// 	Build an array of category group_ids from
		// 	currently selected primary categories
		//	----------------------------------------
		var pc_arr = [];
		 
		$j("#prime_cat_box div").each(function() {
			pc_arr.push( $j(this).data('pc_grp_id') );
		});

		//	----------------------------------------
		//	Display the arrows for the group if 
		//	the category group is not in the array
		//	----------------------------------------
		if($j.inArray( grp_id, pc_arr) < 0)
		{
			$j("div#" + grp_id + " a.pc_arrow").show();
		}

		var ids = '';
		
		$("#prime_cat_box div").each(function() {
			ids = ids + $(this).data('pc_id') + '|'; 
		});

		$j('#pc_field').val(ids);

	}

	$j('a').focus(function() {
		this.blur();
	});
});