<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Zenbu_display extends Zenbu_mcp {

	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		$this->site_id = $this->EE->session->userdata['site_id'];
		$this->member_group_id = $this->EE->session->userdata['group_id'];
		$this->member_id = ($this->EE->session->cache('zenbu', 'member_id')) ? $this->EE->session->cache('zenbu', 'member_id') : $this->EE->session->userdata['member_id'];
		$this->cp_call = (REQ == 'CP') ? TRUE : FALSE;
		$this->local_time = version_compare(APP_VER, '2.6', '>') ? $this->EE->localize->now : $this->EE->localize->set_localized_time();
	}

	/**
	 * function set_head_stylesheets
	 */
	public function set_head_stylesheets()
	{
		if($this->cp_call === FALSE)
		{
			return;
		}

		if(defined('URL_THIRD_THEMES'))
		{
			$themes_path = URL_THIRD_THEMES.$this->addon_short_name;

		} else {

			$themes_path = $this->EE->config->item('theme_folder_url').'third_party/'.$this->addon_short_name;
		}

		//	----------------------------------------
		//	CSS
		//	----------------------------------------
		$this->EE->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.$themes_path.'/fancybox/jquery.fancybox-1.3.4.css" />');
		$this->EE->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.$themes_path.'/css/stylesheet.css" />');
		$this->EE->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.$themes_path.'/fontawesome/fontawesome.css" />');

		//	----------------------------------------
		//	JS
		//	----------------------------------------
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$themes_path.'/fancybox/jquery.easing-1.3.pack.js"></script>');
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$themes_path.'/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>');
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$themes_path.'/fancybox/jquery.fancybox-1.3.4.pack.js"></script>');
	}


	/**
	* function _pagination_config
	* Creates pagination for entry listing
	* @return	string	pagination HTML
	*/
	function _pagination_config($method, $total_rows, $limit)
	{
		// Leave if you're not in the CP. It can happen.
		if($this->cp_call === FALSE)
		{
			return array();
		}

		// Pass the relevant data to the paginate class
		$config['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->addon_short_name;
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $limit;//$this->perpage;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'perpage';
		$config['full_tag_open'] = '<span id="paginationLinks">';
		$config['full_tag_close'] = '</span>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="<" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt=">" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="< <" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="> >" />';

		return $config;
	} // END function _pagination_config


	/**
	* function highlight
	* Highlights string based on keyword
	* @return	string	<span class="highlight"></span>-highlighted string
	*/
	function highlight($text, $rules, $search_field)
	{
		$highlight_conds = array("is", "contains", "beginswith", "endswith", "containsexactly");

		if( ! empty($rules))
		{
			foreach($rules as $rule)
			{
				if(isset($rule['field']) && $rule['field'] == $search_field && in_array($rule['cond'], $highlight_conds))
				{
					$keyword = $rule['val'];
					return highlight_phrase($text, $keyword, '<span class="highlight">', '</span>');
				}
			}
		} else {
			return $text;
		}

		return $text;
	}


	/**
	* function _display_saved_searches
	* Creates a list of saved searches
	* @param	array Array of saved searches
	* @return	string	Saved searches HTML
	*/
	function _display_saved_searches($saved_searches_array)
	{
		$this->EE->load->helper('form');
		$this->EE->load->helper('url');

		$output = "";
		if(empty($saved_searches_array))
		{
			return $output;
		}

		if(isset($saved_searches_array['member']))
		{
			$output = '<ul>';

			foreach($saved_searches_array['member'] as $rule_id => $rule_data)
			{
				//$output .= '<tr><td>';
				$output .= '<li>'.anchor(BASE.AMP."C=addons_modules".AMP."M=show_module_cp".AMP."module=zenbu".AMP."rule_id=".$rule_data['rule_id'], $rule_data['rule_label']).'</li>';
				//$output .= "</td><td>";
				//$output .= form_button("", "-", "class='deletesearch removerule' rel='".$rule_data['rule_id']."' title='".$this->EE->lang->line('delete_this_search')."'");
				//$output .= '</td></tr>';
			}
			$output .= "</ul>";
		}

		if(isset($saved_searches_array['group'][$this->member_group_id]))
		{
			$output .= /*<br /><label>Group searches</label>*/'<ul>';
			foreach($saved_searches_array['group'][$this->member_group_id] as $rule_id => $rule_data)
			{
				if($rule_id != 'group_id' && $rule_id != 'group_name')
				{
					$output .= '<li>'.anchor(BASE.AMP."C=addons_modules".AMP."M=show_module_cp".AMP."module=zenbu".AMP."rule_id=".$rule_data['rule_id'], $rule_data['rule_label']).'</li>';
				}
			}
			$output .= '</ul>';
		}

		return $output;
	}


	/**
	 * function _display_template
	 * Displays a link to the template set in channel preferences
	 * @param 	int		$entry			Entry ID, added to the end of the URL
	 * @param 	int		$url_title		URL Title, added to the end of the URL
	 * @param	int		$channel_id		The channel id of the current listing, if one is set
	 * @param	int		$this_entry_id	The channel id of the current entry being processed
	 * @param 	array	$template_array	Template group and name array filtered by channel_id when available
	 * @param	array	$settings		Saved setttings
	 * @param	array	$output_pages	URIs from the Pages module. Empty if no settings or Pages module is not installed
	 * @return	string	fancybox link to template
	 */
	function _display_template($entry_id, $url_title, $channel_id, $this_entry_channel_id, $template_array, $settings, $pages_array)
	{
		$options = (isset($settings['setting'][$channel_id]['extra_options']["show_view"])) ? $settings['setting'][$channel_id]['extra_options']["show_view"] : array();
		$site_url = (substr($this->EE->config->item('site_url'), -1, 1) != '/') ? $this->EE->config->item('site_url').'/' : $this->EE->config->item('site_url');
		$index_page = ( $this->EE->config->item('index_page') != "" ) ? $this->EE->config->item('index_page').'/' : '';
		$target = '';
		$last_segment = '';

		/**
		 * ----------------------------------------
		 * For multiple channels, dig into general $settings array for this entry's channel's options
		 * This is where the $this_entry_channel_id comes in handy to fetch the channel_id of each entry
		 * ----------------------------------------
		 */
		if($channel_id == "0")
		{
			for($i = 1; $i <= 4; $i++)
			{
				${'ll_option_'.$i} = isset($settings['setting'][$this_entry_channel_id]['extra_options']['show_view']['livelook_option_'.$i]) ? $settings['setting'][$this_entry_channel_id]['extra_options']['show_view']['livelook_option_'.$i] : '';
			}

			if(
				( isset($template_array[$this_entry_channel_id]) && $ll_option_1 == 'use_livelook_settings' ) ||
				( isset($template_array[$this_entry_channel_id]) && $ll_option_1 == '' )
			  )
			{
				$target = ($template_array[$this_entry_channel_id]['template_name'] != "index") ? $template_array[$this_entry_channel_id]['group_name'].'/'.$template_array[$this_entry_channel_id]['template_name'].'/' : $template_array[$this_entry_channel_id]['group_name'].'/';
			}

			if( $ll_option_1 == "use_custom_segments" && $ll_option_2 != '' )
			{
				$target = $settings['setting'][$this_entry_channel_id]['extra_options']['show_view']['livelook_option_2'];
			}

			//	----------------------------------------
			//	If $target is still empty after checking
			//	both situations above, just return "-"
			//	----------------------------------------
			if( empty($target) )
			{
				return "-";
			}

			// Get last segment from general $settings array
			$last_segment = ($ll_option_3 == "entry_id_suffix") ? $entry_id : $url_title;

			/**
			*	---------------------
			*	Pages override
			*	---------------------
			*/
			if( $ll_option_4 == 'y' )
			{
				if(isset($pages_array[$entry_id]))
				{
					$target = (substr($pages_array[$entry_id], 0, 1) == '/') ? substr($pages_array[$entry_id], 1) : $pages_array[$entry_id];
					$last_segment = '';
				}
			}


		} else {

			// For channels set to display Live Look, or when nothing is set yet

			if(
				(isset($options['livelook_option_1']) && $options['livelook_option_1'] == 'use_livelook_settings' && isset($template_array[$channel_id])) ||
				(! isset($options['livelook_option_1']) && isset($template_array[$channel_id]))
			)
			{
				$target = ($template_array[$channel_id]['template_name'] != "index") ? $template_array[$channel_id]['group_name'].'/'.$template_array[$channel_id]['template_name'].'/' : $template_array[$channel_id]['group_name'].'/';
				$last_segment = (isset($options['livelook_option_3']) && $options['livelook_option_3'] == "entry_id_suffix") ? $entry_id : $url_title;
			}

			// For other cases: for channels set to custom segments. Check if segment settings is blank or not first.

			$target = (isset($options['livelook_option_2']) && ! empty($options['livelook_option_2']) && isset($options['livelook_option_1']) && $options['livelook_option_1'] == 'use_custom_segments') ? $options['livelook_option_2'] : $target;
			$last_segment = (isset($options['livelook_option_3']) && $options['livelook_option_3'] == "entry_id_suffix") ? $entry_id : $url_title;
		}

		/**
		*	---------------------
		*	Pages override
		*	---------------------
		*/
		if(isset($options['livelook_option_4']) && $options['livelook_option_4'] == 'y')
		{
			if(isset($pages_array[$entry_id]))
			{
				$target = (substr($pages_array[$entry_id], 0, 1) == '/') ? substr($pages_array[$entry_id], 1) : $pages_array[$entry_id];
				$last_segment = '';
			}
		}

		$target = (substr($target, -1, 1) != '/' && ! empty($target)) ? $target . '/' : $target;
		$link = $site_url.$index_page.$target.$last_segment;
		$final_output = anchor($link, $this->EE->lang->line('view'), 'class="fancyboxtemplate" title="'.$link.'"');
		return $final_output;
	}


	/**
	 * function _display_category_list
	 * Displays a list of category names
	 * @param 	int		$entry	Entry ID
	 * @param 	array	$category_array	category name array
	 * @param 	array	$installed	array of installed modules, used for checking presence of Category Rights addon
	 * @param 	array	$categories full array of category information. "allowed_categories" is used when Category Rights is installed
	 * @param	array	$settings		Saved setttings
	 * @return	string	Comma-separated list of categories
	 */
	function _display_category_list($entry_id, $category_array, $installed = array(), $categories = array(), $settings = array())
	{
		$output = "&nbsp;";
		if( ! isset($category_array[$entry_id]))
		{
			return $output;
		}

		$output = "";
		$output_hidden = "";
		$allowed_categories = ( isset($categories['allowed_categories']) && ! empty($categories)) ? explode(",", $categories['allowed_categories']) : array();

		if( isset($settings['show_categories']['category_option_1']) && (! empty($settings['show_categories']['category_option_1']) || $settings['show_categories']['category_option_1'] == '0'))
		{
			$c = 1;

			foreach($category_array[$entry_id] as $key => $category_name)
			{
				if($c <= $settings['show_categories']['category_option_1'])
				{
					$output .= $category_name.', ';
				} else {
					$output_hidden .= $category_name.', ';
				}

				$c++;

			}

			$comma = $output != "" ? ', ' : '';
			$spacer = $output != "" ? '<span class="more-categories-spacer">&nbsp;</span>' : '';
			$output_hidden = ! empty($output_hidden) ? $spacer . '<a class="more-categories" title="...'.rtrim($output_hidden, ', ').'">...</a><span class="more-categories invisible">' . $comma . rtrim($output_hidden, ', ') . '</span>' : '';
			$output = rtrim($output, ', ') . $output_hidden;

		} else {

			foreach($category_array[$entry_id] as $key => $category_name)
			{
				$output .= $category_name.', ';
			}

		}

		$output = rtrim($output, ', ');

		return $output;
	}


	/**
	 * function _display_text
	 * Displays standard text, with text trimming if set
	 * @param string $data The text
	 * @param int $field_id the Field ID
	 * @param array $settings Some text settings set by user (word limit, etc)
	 * @param string $keyword Keyword text, if any
	 * @return	string	the text
	 */
	function _display_text($data, $field_id, $settings, $rules = array())
	{
		$output = "&nbsp;";

		$extra_options = (isset($settings["field_".$field_id])) ? $settings["field_".$field_id] : array();

		$word_limit = (isset($extra_options['text_option_1'])) ? $extra_options['text_option_1'] : '';
		$word_limit_entered_length = strlen($word_limit);
		if($word_limit == "0" && $word_limit_entered_length == 1) {
			return $output; // From settings page. Shot showing the field would be another way to do this
		}

		if((isset($extra_options['text_option_2']) && $extra_options['text_option_2'] == "html") || ! isset($extra_options['text_option_2']))
		{
			$data = (empty($data)) ? '&nbsp;' : str_replace('&amp;', '&', htmlspecialchars($data)); //str_replace('&amp;', '&', htmlspecialchars($data));
		} else if ($extra_options['text_option_2'] == "nohtml") {
			$data = (empty($data)) ? '&nbsp;' : strip_tags(html_entity_decode(htmlspecialchars($data)));
		}

		$data_len = strlen($data);
		$ellipsis = (empty($data) || $data_len <= $word_limit) ? '' : '...';

		$data = $this->highlight($data, $rules, 'field_'.$field_id);
		if( ! empty($word_limit) && is_numeric($word_limit))
		{
			if(function_exists('mb_substr'))
			{
				$output = mb_substr($data, 0, $word_limit);
			} else {
				$output = substr($data, 0, $word_limit);
			}

			// Remove last entity character
			// May remove the last well-formed character in some cases,
			// but can also remove the last badly-formed character, cleaning up output
			// * If you do not like garbled entities at the end of your text string, uncomment the following:
			/*$string = strrev($output);
			$string = substr($string, strpos($string, "&") + 1);
			$output = strrev($string);*/
			$output = $output.$ellipsis;

		} else {
			$output = $data;
		}

		$output = str_replace("\n", "<br />", $output);
		return $output;
	}


	/**
	 * function _display_date
	 * Formats and displays date based on memebr settings
	 * @param	int		$date	UNIX timestamp
	 * @param	string	$date_type	Type: unix or mysql
	 * @param	string	$custom_date_format	Custom date format set by user (eg. %Y-%m-%d)
	 * @return	string	formatted date data
	 */
	function _display_date($date, $keyword = "", $date_type = "unix", $custom_date_format = '')
	{
		if(empty($date) || $date == "0")
		{
			return '&nbsp;';
		}

		if($date_type == "mysql")
		{
			$date = mysql_to_unix($date);
		}

		if( ! empty($custom_date_format))
		{
			if(version_compare(APP_VER, '2.6', '>'))
			{
				$date_pre = $this->EE->localize->format_date($custom_date_format, $date);
			} else {
				$date_pre = $this->EE->localize->decode_date($custom_date_format, $date);
			}

			// If the custom date format contains no date formatting variables (eg. has only text),
			// just return the custom format only. If not, return the formatted date.
			if($date_pre == $date && $date_type == "unix")
			{
				$date = $custom_date_format;
			} else {
				$date = $date_pre;
			}
		} else {
			if(version_compare(APP_VER, '2.6', '>'))
			{
				$date = $this->EE->localize->human_time($date);
			} else {
				$date = $this->EE->localize->set_human_time($date);
			}
		}
		$output = highlight_phrase($date, $keyword, '<span class="highlight">', '</span>');

		return $output;
	}

	/**
	 * function _display_view_counts
	 * Formats and displays entry view counts
	 * @param	array	$settings		Zenbu settings
	 * @param	array	$view_counts	View count array
	 * @return	string	formatted view count data
	 */
	function _display_view_counts($settings, $view_counts)
	{
		$output = "&nbsp;";
		$empty = TRUE;
		// Checking whether any of the view counts are set to be displayed
		for($i = 1; $i <= 4; $i++)
		{
			if(isset($settings['show_view_count']['view_count_'.$i]) && $settings['show_view_count']['view_count_'.$i] == 'y')
			{
				$empty = FALSE;
			}
		}

		if($empty === TRUE)
		{
			return $output;
		}

		//	----------------------------------------
		//	Single value display
		//	----------------------------------------
		if(count($settings['show_view_count']) == 1)
		{

			for($i = 1; $i <= 4; $i++)
			{
				if(isset($settings['show_view_count']['view_count_'.$i]) && $settings['show_view_count']['view_count_'.$i] == 'y')
				{
					$output = $view_counts[$i];
				}
			}

			// Adding span to avoid 0 echoing nothing in the view. int/string don't seem to work.
			return '<span>'.$output.'</span>';
		}

		// ---------------------------------------------------
		// If there are any view counts set to be displayed,
		// generate the display table
		// ---------------------------------------------------
		$output = '<span class="invisible">'.max($view_counts).'</span>'; // For js sorting
		$output .= '<table class="matrixTable" cellspacing="0" cellpadding="0"><tr>';
		for($i = 1; $i <= 4; $i++)
		{
			if(isset($settings['show_view_count']['view_count_'.$i]) && $settings['show_view_count']['view_count_'.$i] == 'y')
			{
				$output .= '<th class="center">'.$i.'</th>';
			}
		}

		$output .= '</tr><tr>';

		for($i = 1; $i <= 4; $i++)
		{
			if(isset($settings['show_view_count']['view_count_'.$i]) && $settings['show_view_count']['view_count_'.$i] == 'y')
			{
				$output .= '<td>'.$view_counts[$i].'</td>';
			}
		}

		$output .= '</tr></table>';
		return $output;
	}


	/**
	 * Displays file as link, or thumbnail openable by fancybox
	 * @param 	string 	$field_data: the filename with upload directory pointer. eg. {filedir_1}myfile.jpg
	 * @param 	array 	$upload_prefs: array of upload urls from database
	 * @return	string	file link
	 */
	function _display_file($field_id, $field_data, $upload_prefs, $rules = array())
	{
		$this->EE->load->helper('html');
		$this->EE->load->helper('url');

		$output = "&nbsp;";
		$keyword = "";
		if( ! empty($rules))
		{
			foreach($rules as $rule)
			{
				if($rule['field'] == 'field_'.$field_id)
				{
					$keyword = $rule['val'];
				}
			}
		}

		preg_match('/\{filedir_([0-9]*?)\}(.*)/', $field_data, $file_data);

		if(isset($file_data[1]) && $file_data[2])
		{
			$filedir_id = $file_data[1];
			$filename = $file_data[2];
			if($filedir_id != 0)
			{
				$output = "";
				if(preg_match('/(jpg|jpeg|gif|png|JPG|JPEG|GIF|PNG)/', $filename))
				{
					if(file_exists($upload_prefs[$filedir_id]['server_path']."_thumbs/thumb_".$filename))
					{
						$filedir = $upload_prefs[$filedir_id]['url']."_thumbs/";
						$filename_thumb = "thumb_".$filename;
					} else {
						$filedir = $upload_prefs[$filedir_id]['url']."_thumbs/"; // Thanks for making "thumbs" plural from 2.1.5 to 2.2 without telling me
						$filename_thumb = $filename;
					}

					$output .= anchor($upload_prefs[$filedir_id]['url'].$filename, img($filedir.$filename_thumb), 'class="fancybox" rel="#fancybox" alt="'.$filename.'" title="'.$filename.'"');
				} else {
					$filedir = $upload_prefs[$filedir_id]['url'];
					$output .= anchor($upload_prefs[$filedir_id]['url'].$filename, $this->highlight($filename, $rules, 'field_'.$field_id));
				}
			}
		}
		return $output;
	}


	/**
	 * function _display_comments
	 * Displays comments and count, as a link
	 * @return	string	comment link
	 */
	function _display_comments($comment_total, $entry_id, $channel_id, $comment_module_installed = FALSE)
	{
		$output = "";
		if($comment_module_installed === FALSE)
		{
			$output = '-';
		} else {
			// EE 2.1.5 and below use the following commented link, and comment out the next one:
			//$output = anchor(BASE.'&C=content_edit&M=view_comments&channel_id='.$channel_id.'&entry_id='.$entry_id, $this->EE->lang->line('view').' ('.$comment_total.')', 'class="z_comment_link"');
			$output = anchor(BASE.'&C=addons_modules&M=show_module_cp&module=comment&method=index&entry_id='.$entry_id, $this->EE->lang->line('view').' ('.$comment_total.')', 'class="z_comment_link"');
		}
		return $output;
	}

	/**
	  * function _display_rel
	  * Displays a list of related entries
	  * @compatibility: Playa and relationship fields
	  */
	function _display_rel($entry_id, $field_id, $field_data = "", $rel_data = array(), $rules = array())
	{
		$output = '&nbsp;';

		$this_entry_id = $entry_id;
		if( empty($rel_data))
		{
			return $output;
		}

		if(isset($rel_data['playa_ver']))
		{
			$playa_ver = $rel_data['playa_ver'];
		} else {
			$playa_ver = "";
		}

		// --------------------
		// Playa entry display
		// --------------------
		// Playa 4.0
		if(isset($rel_data['parent_id_'.$entry_id]['field_id_'.$field_id]) && $playa_ver >= "4.0")
		{
			// With a $field_id, we should be all set up to get the info we need from $rel_data
			$output = '<ul>';
			$related_entries = $rel_data['parent_id_'.$entry_id]['field_id_'.$field_id];
			foreach($related_entries as $child_entry_id => $entry_data_array)
			{
				$entry_title = $this->highlight($entry_data_array['title'], $rules, 'field_'.$field_id);
				$entry_id = $entry_data_array['entry_id'];
				$channel_id = $entry_data_array['channel_id'];
				$output .= '<li>'.anchor(BASE.AMP."C=content_publish".AMP."M=entry_form".AMP."channel_id=".$channel_id.AMP."entry_id=".$entry_id, $entry_id.' - '.$entry_title);
				$output .= (count($related_entries) > 1) ? '</li>' : '';
			}
			$output .= '</ul>';
			return $output;

		// Playa less than 4.0
		} elseif(isset($rel_data['parent_id_'.$entry_id]) && ! empty($playa_ver) && $playa_ver < "4.0") {

			// Read the data in the field and compare it to $rel_array to fetch entry data
			$field_data = explode('[', $field_data);
			$f_data = array();
			foreach ($field_data as $key => $val)
			{
				if($key != 0)
				{
					$matches = preg_match('/(.*?)\]/', $val, $match);
					$f_data[$match[1]] = $match[1];
				}
			}

			// Go through previously read rel_ids and look for its reference in $rel_array
			$output = '<ul>';
			foreach($f_data as $key => $rel_id)
			{
				$related_entries = $rel_data['parent_id_'.$this_entry_id]['rel_id_'.$rel_id];
				foreach($related_entries as $child_id => $entry_data_array)
				{
					$entry_title = $this->highlight($entry_data_array['title'], $rules, 'field_'.$field_id);
					$entry_id = $entry_data_array['entry_id'];
					$channel_id = $entry_data_array['channel_id'];
					$output .= '<li>'.anchor(BASE.AMP."C=content_publish".AMP."M=entry_form".AMP."channel_id=".$channel_id.AMP."entry_id=".$entry_id, $entry_id.' - '.$entry_title);
					$output .= (count($related_entries) > 1) ? '</li>' : '';
				}
			}
			$output .= '</ul>';
			return $output;

		// When Playa data is within Matrix, just get the entry data (id, title, channel_id) cell-by-cell
		} elseif(isset($rel_data['from_matrix']) && $rel_data['from_matrix'] == 'y') {

			$field_data = explode('[', $field_data);
			$f_data = array();
			foreach ($field_data as $key => $val)
			{
				if($key != 0)
				{
					$matches = preg_match('/(.*?)\]/', $val, $match);
					$f_data[$match[1]] = $match[1];
				}
			}

			$entry_data = $this->EE->zenbu_get->_get_core_entry_data($f_data);
			$output = '<ul>';
			foreach($entry_data as $entry_id => $row)
			{
				$entry_title = $this->highlight($row['title'], $rules, 'field_'.$field_id);
				$entry_id = $row['id'];
				$channel_id = $row['channel_id'];
				$output .= '<li>'.anchor(BASE.AMP."C=content_publish".AMP."M=entry_form".AMP."channel_id=".$channel_id.AMP."entry_id=".$entry_id, $entry_id.' - '.$entry_title);
			}
			$output .= '</ul>';
			return $output;
		}

		// --------------
		// Relationships
		// --------------
		// @param $field_data is the data in the field, which contains the rel_id
		if(isset($rel_data['parent_id_'.$this_entry_id]['rel_id_'.$field_data]) && ! isset($rel_data['parent_id_'.$this_entry_id]['field_id_'.$field_data]) && empty($playa_ver))
		{
			$output = "<ul>";
			$related_entries = $rel_data['parent_id_'.$this_entry_id]['rel_id_'.$field_data];
			foreach($related_entries as $child_entry_id => $entry_data_array)
			{
				$entry_title = $this->highlight($entry_data_array['title'], $rules, 'field_'.$field_id);
				$entry_id = $entry_data_array['entry_id'];
				$channel_id = $entry_data_array['channel_id'];
				$output .= '<li>'.anchor(BASE.AMP."C=content_publish".AMP."M=entry_form".AMP."channel_id=".$channel_id.AMP."entry_id=".$entry_id, $entry_id.' - '.$entry_title);
				$output .= (count($related_entries) > 1) ? '</li>' : '';
			}
			$output .= '</ul>';
			return $output;
		}

		return $output;

	}

}

?>