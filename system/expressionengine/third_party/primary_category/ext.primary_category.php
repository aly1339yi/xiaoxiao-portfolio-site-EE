<?php if ( ! defined('EXT')) exit('No direct script access allowed');

/**
 * Primary Category - Extension
 *
 * @package		Solspace:Primary Category
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/primary_category
 * @license		http://www.solspace.com/license_agreement
 * @version		2.3.0
 * @filesource	primary_category/ext.primary_category.php
 */

require_once 'addon_builder/extension_builder.php';

class Primary_category_ext extends Extension_builder_primary_category
{
	public $settings			= array();
	public $category_fields 	= array();
	public $name				= '';
	public $version				= '';
	public $description			= '';
	public $settings_exist		= 'y';
	public $docs_url			= '';


	public $categories			= array();
	public $selected_cats		= array();
	public $primary_cat			= array();
	public $used_cats			= array();
	public $used_cat_groups		= array();
	public $channel_id 			= '';
	public $sections 			= array();

	public $id					= 'primary_category_acc';


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct($settings = array())
	{
		parent::__construct();

		// --------------------------------------------
		//  Settings
		// --------------------------------------------

		$this->settings = $settings;

		// --------------------------------------------
		// Detault Settings
		// --------------------------------------------


		$init 	= array();

		$query 	= ee()->db->query("SELECT site_id FROM {$this->sc->db->channels}");

		if ( $query->num_rows() > 0 )
		{
			foreach( $query->result_array() as $row )
			{
				$init[$row['site_id']][$this->sc->channels]		= array();
				$init[$row['site_id']]['cat_groups'] 	= array();
			}
		}

		$this->default_settings = serialize($init);

		// --------------------------------------------
		// Extension Hooks
		// --------------------------------------------


		$default = array(
			'class'        => $this->extension_name,
			'settings'     => $this->default_settings,
			'priority'     => 10,
			'version'      => $this->version,
			'enabled'      => 'y'
		);

		$this->hooks = array(
			'check_for_primary_category' => array_merge(
				$default,
				array(
					'method' 	=> 'check_for_primary_category',
					'hook'		=> 'entry_submission_start'
				)
			),
			'set_primary_category' => array_merge(
				$default,
				array(
					'method' 	=> 'set_primary_category',
					'hook'		=> 'entry_submission_end'
				)
			),
			'parse_primary_category' => array_merge(
				$default,
				array(
					'method' 	=> 'parse_primary_category',
					'hook'		=> 'channel_entries_tagdata_end',
					'priority'=> 5,
				)
			),
			// Quick save support
			// added by Leevi Graham (http://newism.com.au) in version 1.6.1
			'publish_form_start' => array_merge(
				$default,
				array(
					'method' 	=> 'publish_form_start',
					'hook'		=> 'publish_form_start',
					'priority'=> 5,
				)
			)
		);


		// --------------------------------------------
		//  Update to New Version - Required
		// --------------------------------------------
		 if ( ! $this->extension_name == $this->class_name . '_acc' AND
			 ( ! $this->extensions_enabled() OR
			 (isset(ee()->extensions->version_numbers[$this->extension_name])
			  AND $this->version_compare(
					$this->version,
					'>',
					ee()->extensions->version_numbers[$this->extension_name]
				))))
		 {
			$this->update_extension_hooks();
		 }
	}
	// END Primary_category_extension_base()


	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * @access	public
	 * @return	null
	 */

	public function activate_extension()
	{
  		//	----------------------------------------
		//	Create the exp_primary_category table
		//	----------------------------------------
		ee()->load->dbforge();

		$fields = array(
			'entry_id'				=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => FALSE),
			'primary_category_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => FALSE),
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->create_table('primary_category', TRUE);

		return parent::activate_extension();
	}
	// END activate_extension()


	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * @access	public
	 * @return	null
	 */

	public function disable_extension()
	{
		//do a saftey check here, just so we don't bomb out.
		if ($this->column_exists('primary_category', $this->sc->db->channel_titles))
		{
			ee()->db->query("ALTER TABLE {$this->sc->db->channel_titles} DROP primary_category");
		}

		ee()->db->query("DROP TABLE IF EXISTS exp_primary_category");

		return parent::disable_extension();
	}
	// END disable_extension()

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * @access	public
	 * @return	null
	 */

	public function update_extension($current = '')
  	{

  		if ($current == '' OR $current == $this->version)
	    {
	        //return FALSE;
	    }

  		if(version_compare($current, '2.2.0.b5', '<'))
  		{
  			//	----------------------------------------
  			//	Create the exp_primary_category table
  			//	----------------------------------------
  			ee()->db->where('class', __CLASS__);
		    ee()->db->update(
				'extensions',
				array('version' => $this->version)
		    );

  			ee()->load->dbforge();

  			$fields = array(
			'entry_id'				=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => FALSE),
			'primary_category_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => FALSE),
			);

			ee()->dbforge->add_field($fields);
			ee()->dbforge->create_table('primary_category', TRUE);

			//	----------------------------------------
			//	Move all current primary categories to
			//	the new table
			//	----------------------------------------
			$sql = ee()->db->query("SELECT entry_id, primary_category FROM {$this->sc->db->channel_titles} WHERE primary_category != '' AND primary_category != 0 AND primary_category IS NOT NULL");

			if($sql->num_rows() > 0)
			{
				foreach($sql->result_array() as $row)
				{
					$data['entry_id']				= $row['entry_id'];
					$data['primary_category_id']	= $row['primary_category'];

					ee()->db->query( ee()->db->insert_string('exp_primary_category', $data, TRUE) );
				}
			}

			//	----------------------------------------
			//	Drop the legact primary_category column
			//	from exp_channel_titles
			//	----------------------------------------
			if ($this->column_exists('primary_category', $this->sc->db->channel_titles))
			{
				ee()->db->query("ALTER TABLE {$this->sc->db->channel_titles} DROP primary_category");
			}


		}

		return TRUE;

	}
	// END update_extension()

	// --------------------------------------------------------------------


	/**
	 * Error Page
	 *
	 * @access	public
	 * @param	string	$error	Error message to display
	 * @return	null
	 */

	public function error_page($error = '')
	{
		$this->cached_vars['error_message'] = $error;

		$this->cached_vars['page_title'] = ee()->lang->line('error');

		// -------------------------------------
		//  Output
		// -------------------------------------

		$this->ee_cp_view('error_page.html');
	}
	// END error_page()


	// -------------------------------------
	// Configuration for the extension settings page
	//
	// @access  public
	// @return	array The settings array
  // -------------------------------------



	public function settings_form($current)
	{

		$this->cached_vars['site_label'] 			= ee()->config->item('site_label');
		$this->cached_vars['channels']	 			= array();
		$this->cached_vars['category_groups'] 		= array();
		$this->cached_vars['settings'] 				= $this->_get_settings(TRUE,TRUE);
		$this->cached_vars['class'] 				= 'even';

		/** ----------------------------------
		/**  List of Channels
		/** ----------------------------------*/

		$channels_query = ee()->db->query(
			"SELECT 	{$this->sc->db->channel_id}, field_group,
						site_id, {$this->sc->db->channel_title}, status_group
			 FROM 		{$this->sc->db->channels}
			 WHERE 		site_id = ".ee()->config->item('site_id')."
			 ORDER BY 	{$this->sc->db->channel_title}"
		);


		foreach($channels_query->result_array() as $row){

			$this->cached_vars['channels'][$row[$this->sc->db->channel_id]] = $row[$this->sc->db->channel_title];

		}


		/** ----------------------------------
		/**  List of Category Groups
		/** ----------------------------------*/

		$category_group_query = ee()->db->query(
			"SELECT 	group_id, group_name
			 FROM 		exp_category_groups
			 WHERE 		site_id = " . ee()->config->item('site_id') . "
			 ORDER BY 	group_name"
		);

		foreach($category_group_query->result_array() as $row){

			$this->cached_vars['category_groups'][$row['group_id']] = $row['group_name'];

		}

		//clean up after ourselves like good house guests
		unset($channels_query);
		unset($category_group_query);

		$this->_include_theme_js('primary_category_settings.js');
		return $this->ee_cp_view('settings_form.html');

	}


	/**
	* Saves the settings from the config form
	*
	* @since	Version 1.5.
	**/
	function save_settings()
	{

		$settings = array();


		// load the settings from cache or DB
		$this->settings = $this->_get_settings(TRUE, TRUE);

		// unset the name
		unset($_POST['name']);


		// add the posted values to the settings
		$this->settings[ee()->config->item('site_id')] = $_POST;

		if(isset($_POST[$this->sc->channels]) === FALSE)
		{
			$this->settings[ee()->config->item('site_id')][$this->sc->channels] = array();
		}

		if(isset($_POST['cat_groups']) === FALSE)
		{
			$this->settings[ee()->config->item('site_id')]['cat_groups'] = array();
		}

		// update the settings
		ee()->db->query($sql = "UPDATE exp_extensions SET settings = '" . addslashes(serialize($this->settings)) . "' WHERE class = '" . get_class($this) . "'");

	}



	/**
	* Returns the extension settings from the DB
	*
	* @access	private
	* @param	bool	$force_refresh	Force a refresh
	* @param	bool	$return_all		Set the full array of settings rather than just the current site
	* @return	array					The settings array
	* @since 	Version 1.5.0
	* @thanks	Leevi Graham (leevigraham.com) for this wonderfull idea
	*/
	function _get_settings($force_refresh = FALSE, $return_all = FALSE, $class = '')
	{
		// assume there are no settings
		$settings = FALSE;

		// What is the current ext class (we love ucfirst OK!)
		$class = ($class=='' ? get_class($this) : $class );

		// Get the settings for the extension
		if(isset($this->cache['settings']) === FALSE || $force_refresh === TRUE)
		{
			// check the db for extension settings
			$query = ee()->db->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = '" . $class . "' LIMIT 1");

			// if there is a row and the row has settings
			if ($query->num_rows() > 0 && $query->row('settings') != '')
			{
				ee()->load->helper('string');


				$this->cache['settings'] = strip_slashes(unserialize($query->row('settings')));
			}
		}

		// check to see if the session has been set
		// if it has return the session
		// if not return false

		if(empty($this->cache['settings']) !== TRUE)
		{
			$settings = ($return_all === TRUE) ?  $this->cache['settings'] : $this->cache['settings'][ee()->config->item('site_id')];
		}

		return $settings;

	}

	/**
	* Method for the publish_form_start hook
	*
	* - Runs before any data id processed
	* - Checks for quicksave
	* added by Leevi Graham (http://newism.com.au) in version 1.6.1
	*
	* @param	string $which The current action (new, preview, edit, or save)
	* @param	string $submission_error A submission error if any
	* @param	string $entry_id The current entries id
	* @see		http://expressionengine.com/developers/extension_hooks/publish_form_start/
	* @since	Version 1.6.1
	*/
	function publish_form_start( $which, $submission_error, $entry_id, $hidden )
	{

		// Quicksave
		if ( $which == "save" )
		{
			$entry_id = $_GET['entry_id'] = ee()->input->get_post('entry_id');
			$this->set_primary_category($entry_id);
		}
	}


	/** ----------------------------------------------
	/**	Set the primary category
	/** ---------------------------------------------*/

	function set_primary_category ($entry_id = '', $data = '', $ping_message = '')
	{
		ee()->extensions->end_script = FALSE;

		/** ----------------------------------------------
		/**	Execute?
		/** ---------------------------------------------*/

		if ( $entry_id == '' )
		{
			return FALSE;
		}

		/** ----------------------------------------------
		/**	Weblog id?
		/** ---------------------------------------------*/

		$channel_id	= ( ee()->input->get_post($this->sc->db->channel_id) !== FALSE AND $this->_numeric( ee()->input->get_post($this->sc->db->channel_id) ) === TRUE ) ? ee()->input->get_post($this->sc->db->channel_id): 0;

		/** ----------------------------------------------
		/**	Save primary cat memory prefs in cookie
		/** ---------------------------------------------*/

		$save_primary	= FALSE;

		if ( ee()->input->get_post( 'pc_remember_primary_category_'.$channel_id ) !== FALSE AND ee()->input->get_post( 'pc_remember_primary_category_'.$channel_id ) == 'y' )
		{
			$save_primary	= TRUE;
			$this->set_cookie( 'pc_remember_primary_category_'.$channel_id, 'y', 0 );
		}
		else
		{
			$this->set_cookie( 'pc_remember_primary_category_'.$channel_id, 'n', 0 );
		}

		/** ----------------------------------------------
		/**	Save regular cat memory prefs in cookie
		/** ---------------------------------------------*/

		$save_cats	= array();

		$query	= ee()->db->query( "SELECT w.cat_group
			FROM {$this->sc->db->channels} w
			LEFT JOIN {$this->sc->db->channel_titles} t ON t.{$this->sc->db->channel_id} = w.{$this->sc->db->channel_id}
			WHERE w.cat_group != ''
			AND t.entry_id = '".ee()->db->escape_str(  $entry_id )."'" );

		if ( $query->num_rows() > 0 )
		{
			foreach ( explode( "|", $query->row('cat_group') ) as $group_id )
			{
				if ( ee()->input->get_post( 'pc_remember_other_categories_'.$channel_id.'_'.$group_id) !== FALSE AND ee()->input->get_post( 'pc_remember_other_categories_'.$channel_id.'_'.$group_id ) == 'y' )
				{
					$save_cats[]	= $group_id;

					$this->set_cookie( 'pc_remember_other_categories_'.$channel_id.'_'.$group_id, 'y', 0 );
				}
				else
				{
					$this->set_cookie( 'pc_remember_other_categories_'.$channel_id.'_'.$group_id, 'n', 0 );
				}
			}
		}

		/** ----------------------------------------------
		/**	Check primary category
		/** ---------------------------------------------*/

		if ( isset( $_POST['primary_category'] ) === FALSE )
		{
			$primary 	= array();
		}
		else
		{
			$primary 	= ee()->security->xss_clean($_POST['primary_category']);
			$primary 	= explode('|', $primary);
		}

		if( ! empty($primary))
		{
			//	----------------------------------------
			//	Delete old rows in exp_primary_categories
			//	----------------------------------------

				ee()->db->query("DELETE FROM exp_primary_category WHERE entry_id = " . ee()->db->escape_str($entry_id));

			//	----------------------------------------
			//	Insert each primary category
			//	----------------------------------------
			foreach($primary as $key => $pc_id)
			{
				if(is_numeric($pc_id))
				{
					$query = ee()->db->query( ee()->db->insert_string( 'exp_primary_category', array( 'entry_id' => $entry_id, 'primary_category_id' => $pc_id ) ) );
				}
			}
		}

		/** ----------------------------------------------
		/**	Save primary cat to cookie
		/** ---------------------------------------------*/
		if ( $save_primary === TRUE )
		{
			$this->set_cookie( 'pc_cookie_primary_category_'.$channel_id, $primary, 0 );
		}

		/** ----------------------------------------------
		/**	Make sure category is set
		/** ---------------------------------------------*/

		// $query	= ee()->db->query( "SELECT COUNT(*) AS count FROM exp_category_posts WHERE entry_id = '".ee()->db->escape_str( $entry_id )."' AND cat_id = '".ee()->db->escape_str( $primary )."'" );
//
// 		if ( $query->row('count') == 0 )
// 		{
// 			ee()->db->query( ee()->db->insert_string( 'exp_category_posts', array( 'entry_id' => $entry_id, 'cat_id' => $primary ) ) );
// 		}

		/** ----------------------------------------------
		/**	Save other cats to cookies
		/** ---------------------------------------------*/

		$cats	= array();

		$query	= ee()->db->query( "SELECT cat_id FROM exp_category_posts WHERE entry_id = '".ee()->db->escape_str( $entry_id )."'" );

		foreach ( $query->result_array() as $row )
		{
			$cats[]	= $row['cat_id'];
		}

		if ( count( $save_cats ) > 0 AND count( $cats ) > 0 )
		{
			$query	= ee()->db->query( "SELECT cat_id, group_id FROM exp_categories
										WHERE cat_id IN (".implode( ",", $cats ).")
										AND group_id IN (".implode( ",", $save_cats ).")" );

			$save_cats	= array();

			foreach ( $query->result_array() as $row )
			{
				$save_cats[ $row['group_id'] ][]	= $row['cat_id'];
			}

			foreach ( $save_cats as $key => $val )
			{
				$this->set_cookie( 'pc_cookie_categories_'.$channel_id.'_'.$key, implode( "|", $val ), 0 );
			}
		}

		return;
	}

	/** end	set primary category */



	/** ----------------------------------------------
	/**	Display the primary category box
	/** ---------------------------------------------*/

	function show_category_box ( $cat_group = '0', $which = 'new', $deft_category = '', $catlist = '' )
	{
		// channel id
		$this->channel_id	= (
			ee()->input->get_post($this->sc->db->channel_id) !== FALSE AND
			$this->_numeric( ee()->input->get_post($this->sc->db->channel_id) ) === TRUE
		) ? ee()->input->get_post($this->sc->db->channel_id) : 0;


		// ----------------------------------------------
		//  Get assigned categories
		// ---------------------------------------------

		$pc = '';

		if ( isset( $_POST['entry_id'] ) === FALSE )
		{
			$entry_id 	= ee()->input->get_post('entry_id');

			// ----------------------------------------------
			//	There's a variable typing bug in EE on the
			//	extension hook. $catlist can either be a
			//	string or an array, so we have to correct on
			//	our side.
			// ---------------------------------------------

			if ( is_array( $catlist ) === TRUE )
			{
				$this->selected_cats	= $catlist;
			}
			else
			{
				$this->selected_cats[]	= $catlist;
			}
		}
		else
		{
			$entry_id	= ee()->security->xss_clean($_POST['entry_id']);

			foreach( $_POST as $key => $value )
			{
				if ( preg_match('/^category_*/', $key) )
				{
					$this->selected_cats[] = $value;
				}
			}
		}

		// ----------------------------------------------
		//  Get primary category
		// ---------------------------------------------

		$pc = '';

		if ( ee()->input->get_post('entry_id') === FALSE OR ee()->input->get_post('entry_id') == '' )
		{
			$pc_cookie = ee()->input->cookie( 'pc_cookie_primary_category_'.$this->channel_id );
			$re_cookie = ee()->input->cookie( 'pc_remember_primary_category_'.$this->channel_id );

			if ($pc_cookie  !== FALSE AND
				$this->_numeric( $pc_cookie ) === TRUE AND
				$re_cookie == 'y'
			 )
			{
				$pc	= $pc_cookie;

				$query	= ee()->db->query(
					"SELECT cat_name
					 FROM 	exp_categories
					 WHERE 	cat_id = '" . ee()->db->escape_str( $pc ) . "'
					 ORDER BY cat_order"
				);

				$this->cached_vars['cookies']['remember_primary_cat'][$this->channel_id] = 'y';
				$this->primary_cat['id'] 		= ee()->db->escape_str( $pc );
				$this->primary_cat['cat_name'] 	= $query->row('cat_name');
			}

		}
		elseif ( ee()->input->get_post('primary_category') === FALSE )
		{

			$sql = "SELECT 		pc.primary_category_id, c.cat_name, c.group_id, cg.group_name
					FROM 		exp_primary_category pc
					LEFT JOIN 	exp_categories c
					ON 			pc.primary_category_id = c.cat_id
					LEFT JOIN 	exp_category_groups cg
					ON 			c.group_id = cg.group_id
					WHERE 		pc.entry_id='" . ee()->db->escape_str($entry_id) . "'
					ORDER BY 	c.cat_order";

			$query = ee()->db->query($sql);

			if ( $query->num_rows() != 0 && $query->row('cat_name') != '' )
			{
				//this handles the case where a user has removed the primary category
				//then hit a validation error and gets redirected back
				//leave the field empty in this case
				foreach($query->result_array() as $row)
				{
					if(ee()->input->post('pc_del_primary') === FALSE OR
					   ee()->input->post('pc_del_primary') == '') {

						$this->primary_cat[$row['primary_category_id']]['id']				= $row['primary_category_id'];
						$this->primary_cat[$row['primary_category_id']]['cat_name']			= $row['cat_name'];
						$this->primary_cat[$row['primary_category_id']]['cat_group_id']		= $row['group_id'];
						$this->primary_cat[$row['primary_category_id']]['cat_group_name']	= $row['group_name'];

					}
				}

			}
		}
		else
		{
			$pc = ee()->security->xss_clean($_POST['primary_category']);

			if ( $pc != '' )
			{
				$sql = "SELECT 		cat_name
						FROM 		exp_categories
						WHERE 		cat_id='".ee()->db->escape_str($pc)."'
						ORDER BY 	cat_order";

				$query = ee()->db->query($sql);

				$this->primary_cat['id'] = $pc;
				$this->primary_cat['cat_name'] = $query->row('cat_name');

			}
		}

		// --------------------------------------------
		// Build the Cat Tree
		// --------------------------------------------

		// Get the category groups we need

		$category = $this->_category_tree($cat_group);

		// Add some vars for later in the views
		$this->cached_vars['channel_id'] 	= $this->channel_id;
		$this->cached_vars['selected_cats'] = $this->selected_cats;
		$this->cached_vars['primary_cat'] 	= $this->primary_cat;
		$this->cached_vars['theme_path'] 	= $this->_theme_url();
		$this->cached_vars['settings'] 		= $this->settings;


		return $this->view('category_box.html','',TRUE);
	}

	/**	End show category box */


/**	----------------------------------------
  /**	Numeric
  /**	----------------------------------------*/

  function _numeric ( $str = '' )
  {
	if ( $str == '' OR preg_match( '/[^0-9]/', $str ) != 0 )
	{
		return FALSE;
	}

	return TRUE;
  }

  /* End numeric */

	/** ----------------------------------------------
	/**	Build the category tree array
	/** ---------------------------------------------*/

	function _category_tree ( $cat_group, $parent_id='' )
	{
		$this->cached_vars['categories'] = array();

		$cat_groups = array();

		if ( $cat_group != '' )
		{
			$cat_groups = preg_split("/,|\|/s", $cat_group);
		}

		//loop through the categories with with the sort orders

		foreach($cat_groups AS $group_id)
		{

			if( $group_id != '' )
			{

				$sql = "SELECT 		c.cat_name AS category_name,
									c.cat_id AS category_id,
									c.parent_id AS parent_id,
									p.cat_name AS parent_name,
									c.cat_image AS category_image,
									cg.group_id,
									cg.group_name
						FROM		exp_categories c
						LEFT JOIN 	exp_categories p
						ON 			p.cat_id = c.parent_id
						LEFT JOIN 	exp_category_groups cg
						ON 			c.group_id = cg.group_id
						WHERE 		c.cat_id != 0";

				if ( $group_id != '' && is_numeric( $group_id ) )
				{
					$sql .= " AND c.group_id = '".ee()->db->escape_str( $group_id )."'";
				}

				if ( $parent_id != '' && is_numeric( $parent_id ) )
				{
					$sql .= " AND c.parent_id = '".ee()->db->escape_str( $parent_id )."'";
				}

				/*$sort_order = ( isset( $query->row['sort_order'] ) === TRUE AND
								$query->row['sort_order'] == 'a' ) ? 'c.cat_name' : 'c.cat_order';

				//get the sort order for this group
				$query = ee()->db->query(
					"SELECT sort_order
					 FROM 	exp_category_groups
					 WHERE 	group_id='{$group_id}'");*/

				$sort_order = 'c.cat_name'; /*( isset( $query->row['sort_order'] ) === TRUE AND
								$query->row['sort_order'] == 'a' ) ? 'c.cat_name' : 'c.cat_order';*/

				$sql .= " ORDER BY c.cat_order, c.parent_id, ".$sort_order;

				$query = ee()->db->query($sql);

				//This is messsy.
				//It's done this way as this actually runs as part of the accessory in EE2
				//And gets the wrong class to get the extension settings
				//Overload the class in this case.

				$this->settings = $this->_get_settings(FALSE,FALSE,'primary_category_ext');

				//add these to the categories array.
				$this->cached_vars['categories'][$group_id] 					= $query->result_array();
				$this->cached_vars['group_settings'] 							= $this->settings['cat_groups'];

				//we've got no categories in this group
				if($query->num_rows == 0)
				{
					$sql_cat_group = ee()->db->query("SELECT group_name FROM exp_category_groups WHERE group_id = " . ee()->db->escape_str($group_id) );
					$this->cached_vars['category_groups'][$group_id] 	= $sql_cat_group->row('group_name');
				}
				else
				{
					$this->cached_vars['category_groups'][$query->row('group_id')] 	= $query->row('group_name');
				}

				/** ----------------------------------------------
				/**	Add cookie saved cats
				/** ---------------------------------------------*/


				if ( ee()->input->get_post('entry_id') === FALSE OR
					 ee()->input->get_post('entry_id') == '' )
				{
					if ( ee()->input->cookie( 'pc_cookie_categories_'.$this->channel_id.'_'.$group_id ) !== FALSE
						AND ee()->input->cookie( 'pc_cookie_categories_'.$this->channel_id.'_'.$group_id ) != ''
						AND ee()->input->cookie( 'pc_remember_other_categories_'.$this->channel_id.'_'.$group_id ) !== FALSE
						AND ee()->input->cookie( 'pc_remember_other_categories_'.$this->channel_id.'_'.$group_id ) == 'y' )
					{
						$this->cached_vars['cookies']['remember_other_categories'][$this->channel_id][$group_id] = 'y';
						$this->selected_cats	= array_merge( $this->selected_cats, explode( "|", ee()->input->cookie( 'pc_cookie_categories_'.$this->channel_id.'_'.$group_id ) ) );
					}
				}
			}
		}
	}

	/**	End category tree */


	/** ----------------------------------------------
	/**	Check if primary category is required
	/** ---------------------------------------------*/

	function check_for_primary_category ($which)
	{

		if (ee() === NULL)
		{
			return;
		}

		// Check POST to see if primary_category is empty && channel_id is set to force primary_category
		// IF YES shoot them!!
	//	show_error('general',ee()->lang->line('error_empty'));
		if ( ee()->input->post('primary_category')=='' && isset($_POST[$this->sc->db->channel_id],ee()->extensions->s_cache[ucfirst(get_class($this))][ee()->config->item('site_id')][$this->sc->channels]) && in_array($_POST[$this->sc->db->channel_id],ee()->extensions->s_cache[ucfirst(get_class($this))][ee()->config->item('site_id')][$this->sc->channels]) )
		{

			ee()->load->library('api');

			ee()->api->instantiate('channel_entries');

			ee()->api_channel_entries->errors['category'] = ee()->lang->line('error_empty');
		}


		return;
	}

	/**	End check_for_primary_category */



	/** ----------------------------------------------
	/**	Parse primary category
	/** ---------------------------------------------*/

	function parse_primary_category ( $tagdata, $row, $ths )
	{

		/** ----------------------------------------------
		/**	Extension house cleaning
		/** ---------------------------------------------*/

		ee()->extensions->end_script	= FALSE;

		if ( isset( ee()->extensions->last_call ) === TRUE AND ee()->extensions->last_call != '' )
		{
			$tagdata	= ee()->extensions->last_call;
		}

		/** ---------------------------------------------------
		/**	C'mon we are greedy with performance/sql queries
		/** --------------------------------------------------*/

		if ( strpos(ee()->TMPL->fetch_param('disable'),'primary_category') !== FALSE )
		{
			return $tagdata;
		}

		if ( strpos($tagdata, LD.'primary_category') === FALSE )
		{
			return $tagdata;
		}

		/** ----------------------------------------------
		/**	Query
		/** ---------------------------------------------*/



		foreach ( ee()->TMPL->var_pair as $k => $v )
		{

			if ( strncmp($k, "primary_categories", 18) == 0)
			{
				$group_id			= '';
				$category_groups	= array();
				$result_array		= array();
				$primary_category	= array();

				if ( isset( $row['entry_id'] ) === TRUE )
				{
					$select	= "c.cat_id AS main_cat_id, c.group_id AS main_group_id, cg.group_name AS main_group_name, c.*, cp.cat_name AS parent_name, cp.cat_url_title AS parent_url_title, t.entry_id ";

					$sql	= "/* Primary Category Channel Entries */ SELECT %select FROM exp_categories c LEFT JOIN exp_primary_category pc ON pc.primary_category_id = c.cat_id LEFT JOIN {$this->sc->db->channel_titles} t ON pc.entry_id = t.entry_id
						LEFT JOIN exp_category_groups cg ON cg.group_id = c.group_id";

					if ( ee()->db->table_exists('exp_category_field_data') === TRUE )
					{
						$full_version	= TRUE;
						$select	.= ", cfd.*";
						$sql	.= " LEFT JOIN exp_category_field_data cfd ON cfd.cat_id = c.cat_id";
					}

					$sql .= " LEFT OUTER JOIN exp_categories cp ON cp.cat_id = c.parent_id ";

					$sql	.= " WHERE t.entry_id = '".ee()->db->escape_str( $row['entry_id'] )."'";

					$in_not_in = '';

					if ( $v !== FALSE && is_array($v))
					{
						foreach($v as $param => $param_value)
						{
							if($param == 'category_group')
							{
								//	----------------------------------------
								//	Category group filter
								//	----------------------------------------

								if( $param_value != '' && strncmp($param_value, 'not ', 4) == 0 )
								{
									$in_not_in = "NOT IN (". ee()->db->escape_str( str_replace('|', ', ',  substr($param_value, 4) ) ) . ")";
								}
								elseif( $param_value != '' )
								{
									$in_not_in = "IN (". ee()->db->escape_str( str_replace('|', ', ',  $param_value ) ) . ")";
								}
							}
						}
					}


					$sql 	.= ! empty($in_not_in) ? " AND c.group_id " . $in_not_in : '';

					$sql	= str_replace( "%select", $select, $sql );

					$query	= ee()->db->query( $sql );

					/** ----------------------------------------------
					/**	Load array
					/** ---------------------------------------------*/
					$c = 0;
					if ( $query->num_rows() > 0 )
					{
						foreach($query->result_array() as $pc_row)
						{
							$c++;
							$primary_category[$c][ 'primary_category_id' ]					= $pc_row['main_cat_id'];
							$primary_category[$c][ 'primary_category_group_id' ]			= $pc_row['main_group_id'];
							$primary_category[$c][ 'primary_category_group_name' ]			= $pc_row['main_group_name'];
							$primary_category[$c][ 'primary_category_parent_id' ]			= $pc_row['parent_id'];
							$primary_category[$c][ 'primary_category_name' ]				= $pc_row['cat_name'];
							$primary_category[$c][ 'primary_category_description' ]			= $pc_row['cat_description'];
							$primary_category[$c][ 'primary_category_image' ]				= $pc_row['cat_image'];
							$primary_category[$c][ 'primary_category_order' ]				= $pc_row['cat_order'];
							$primary_category[$c][ 'primary_category_parent_name' ]			= $pc_row['parent_name'];
							$primary_category[$c][ 'primary_category_parent_url_title' ]	= $pc_row['parent_url_title'];

							if ( isset( $full_version ) === TRUE )
							{
								$primary_category[$c][ 'primary_category_url_title' ]	= $pc_row['cat_url_title'];
							}

							// Build the category groups we need to get category fields from
							$category_groups[] = $pc_row['main_group_id'];

							// Build category custom field value array
							foreach($pc_row as $label => $value)
							{
								if(strncmp( $label, 'field_id_', 9 ) == 0)
								{
									$primary_category[$c][ 'primary_category_' . $label ] = $value;
								}
							}
						}
					}

					$result_array = $query->result_array();

					$data = ee()->TMPL->fetch_data_between_var_pairs( $tagdata, 'primary_categories' );

					$tag_pair_data = '';

					if( ! empty($primary_category) )
					{

						foreach($primary_category as $row_number => $pc_value_arr)
						{
							$tag_pair_data = $tag_pair_data . $data;

							//	----------------------------------------
							//	Parse standard Category variables
							//	----------------------------------------
							foreach($pc_value_arr as $var => $val)
							{

								$tag_pair_data = ee()->TMPL->swap_var_single(
											$var,
											$val,
											$tag_pair_data
										  );

								//	---------------------------------------------
								//	Replace {filedir_X} when present
								//	---------------------------------------------
								if($var == 'primary_category_image')
								{
									if (version_compare(APP_VER, '2.4', '>='))
									{
										ee()->load->model('file_upload_preferences_model');
										$file_upload_prefs = $this->EE->file_upload_preferences_model->get_file_upload_preferences();

										foreach($file_upload_prefs as $upload_id => $upload_prefs_array)
										{
											$tag_pair_data = str_replace(LD.'filedir_'.$upload_id.RD, $upload_prefs_array['url'], $tag_pair_data);
										}
									}
								}
							}

							//	---------------------------------------------
							//	Parse custom category fields
							//	---------------------------------------------
							if ( isset( $full_version ) === TRUE AND $this->_category_fields( $category_groups ) !== FALSE )
							{
								/**	----------------------------------------
								/**	Set classes
								/**	----------------------------------------*/
								ee()->load->library('typography');

								/**	----------------------------------------
								/**	Loop and parse
								/**	----------------------------------------*/

								foreach ( $this->category_fields as $key => $val )
								{
									/**	----------------------------------------
									/**	Conditionals
									/**	----------------------------------------*/

									$cond[ 'primary_category_'.$key ]	= $primary_category[$row_number]['primary_category_field_id_'.$val['field_id']];

									$tag_pair_data		= ee()->functions->prep_conditionals( $tag_pair_data, $cond );

									/**	----------------------------------------
									/**	Parse select
									/**	----------------------------------------*/

									foreach ( ee()->TMPL->var_pair as $select_k => $select_v )
									{
										if ( $select_k == "select_".$key )
										{

											$pc_select_row = $primary_category[$row_number];

											$data_select	= ee()->TMPL->fetch_data_between_var_pairs( $tag_pair_data, $k );

											$tag_pair_data	= preg_replace( "/".LD.$select_k.RD."(.*?)".LD.preg_quote(T_SLASH,'/').$select_k.RD."/s",
																		$this->_parse_select( $key, $pc_select_row, $data_select ),
																		$tag_pair_data );

										}
									}

									/**	----------------------------------------
									/**	Parse singles
									/**	----------------------------------------*/

									$tag_pair_data = ee()->TMPL->swap_var_single(
										'primary_category_'.$key,
										ee()->typography->parse_type(
															$primary_category[$row_number]['primary_category_field_id_'.$val['field_id']],
															array(
																	'text_format'   => $this->category_fields[$key]['field_default_fmt'],
																	'html_format'   => 'safe',
																	'auto_links'    => 'n',
																	'allow_img_url' => 'n'
																  )
														  ),
										$tag_pair_data
									  );

								}
							}


						}

						//	----------------------------------------
						//	The replaced data now built, place it
						//	within the tag pair
						//	----------------------------------------

						// Final escaping to avoid eg. $100 from being turned to $10 backreference, which is empty.
						// @link http://www.procata.com/blog/archives/2005/11/13/two-preg_replace-escaping-gotchas/
						$tag_pair_data = preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $tag_pair_data);

						$tagdata	= preg_replace( "/".LD.$k.RD."(.*?)".LD.preg_quote(T_SLASH,'/').'primary_categories'.RD."/s", $tag_pair_data, $tagdata );

					}
					else
					{
						require_once 'addon_builder/module_builder.php';
						$pcmod = new Module_builder_primary_category();
						$no_results = $pcmod->no_results();
						$tagdata	= preg_replace( "/".LD.'primary_categories(.*?)'.RD."(.*?)".LD.preg_quote(T_SLASH,'/').'primary_categories'.RD."/s", $no_results, $tagdata );
					}

				} // if ( isset( $row['entry_id'] ) === TRUE )
			} // if ( strncmp($k, "primary_categories", 18) == 0)
		} // foreach ( ee()->TMPL->var_pair as $k => $v )

		return $tagdata;
	}


	/**	End parse primary category */


	/** ----------------------------------------------
	/**	Category fields
	/** ---------------------------------------------*/

	function _category_fields ( $group_id = '' )
	{

		if ( $group_id == '' ) return FALSE;

		if ( count( $this->category_fields ) > 0 ) return $this->category_fields;

		$query = ee()->db->query("SELECT * FROM exp_category_fields");
		//WHERE group_id IN (".ee()->db->escape_str( implode(',', $group_id) ).")

		foreach ($query->result_array() as $row)
		{
			foreach ( $row as $key => $val )
			{
				$this->category_fields[ $row['field_name'] ][$key]	= $val;
			}
		}

		return $this->category_fields;
	}

	/**	End category fields */


	/**	----------------------------------------
	/**	Parse select
	/**	----------------------------------------*/

	function _parse_select( $key = '', $row = array(), $data = '' )
	{
		/**	----------------------------------------
		/**	Fail?
		/**	----------------------------------------*/

		if ( $key == '' OR $data == '' )
		{
			return '';
		}

		/**	----------------------------------------
		/**	Are there list items present?
		/**	----------------------------------------*/

		if ( ! isset( $this->category_fields[$key]['field_list_items'] ) OR $this->category_fields[$key]['field_list_items'] == '' )
		{
			return '';
		}

		/**	----------------------------------------
		/**	Do we have a value?
		/**	----------------------------------------*/

		if ( isset( $row['primary_category_field_id_'.$this->category_fields[$key]['field_id']] ) )
		{
			$value	= $row['primary_category_field_id_'.$this->category_fields[$key]['field_id']];
		}
		else
		{
			$value	= '';
		}

		/**	----------------------------------------
		/**	Create an array from value
		/**	----------------------------------------*/

		$arr	= preg_split( "/\r|\n/", $value );

		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/

		$return	= '';

		foreach ( preg_split( "/\r|\n/", $this->category_fields[$key]['field_list_items'] ) as $val )
		{
			$out		= $data;
			$selected	= ( in_array( $val, $arr ) ) ? 'selected="selected"': '';
			$checked	= ( in_array( $val, $arr ) ) ? 'checked="checked"': '';
			$out		= str_replace( LD."selected".RD, $selected, $out );
			$out		= str_replace( LD."checked".RD, $checked, $out );
			$out		= str_replace( LD."value".RD, $val, $out );
			$return		.= trim( $out )."\n";

		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		return $return;
	}

	/**	End parse select */


	/**
	 * Set Sections
	 *
	 * Set content for the accessory
	 *
	 * @access	public
	 * @return	void
	 */
	function set_sections()
	{

		// are we on the Publish page?
		if (ee()->input->get('C') == 'content_publish' && ee()->input->get('M') == 'entry_form')
		{
			//get the channel id from the url
			$channel_id = ee()->security->xss_clean(ee()->input->get('channel_id'));

			//be defensive
			if($channel_id != '')
			{

				//what category groups do we want?
				$query 	= ee()->db->query("SELECT cat_group
							FROM {$this->sc->db->channels}
							WHERE {$this->sc->db->channel_id} = '{$channel_id}'
							LIMIT 0,1");

				//we might not have any category groups.
				if($query->row('cat_group') != '')
				{
					$catlist = array();

					if(ee()->input->get('entry_id'))
					{
						//if we have cat_groups we might have categories
						$cat_query = ee()->db->query("SELECT cat_id
											FROM exp_category_posts
											WHERE entry_id = '".ee()->security->xss_clean(ee()->input->get('entry_id'))."'");

						foreach($cat_query->result_array() AS $cat)
						{
							$catlist[] = $cat['cat_id'];

						}

					}


					$template = $this->json_encode($this->show_category_box($query->row('cat_group'),'','',$catlist));

					//This little bit of magic is to get around quicksaves and validation errors
					//The category details do get saved, but we can't access them if there's a validation error
					//In this case on the page reload, we check the default ee2 state of the category inputs,
					//save the checked ones, then recheck our new input list.
					//the additional inputs (remember my choices, primary category etc, do get lost, but that's
					//the same behaviour as in EE1, so at the minute I'm willing to accept that).
					$this->_insert_js('var n = jQuery("#sub_hold_field_category .holder input:checked").length;
									var cats = [];
									if(n>0) {
										var cats = [];
										jQuery("#sub_hold_field_category .holder input:checked").each(function(){
											cats.push(jQuery(this).attr("value"));
										});
									}
									var error = jQuery("#sub_hold_field_category .holder .notice");

									jQuery("#sub_hold_field_category .holder").html('.$template.');

									jQuery("#sub_hold_field_category input").each(function(){
										if(jQuery.inArray(jQuery(this).attr("value"),cats) > -1) jQuery(this).attr("checked","checked");
									});
									jQuery("#sub_hold_field_category .holder").append(error);
									');
					$this->_include_theme_css('primary_category.css');

					$this->cache_vars['theme_path'] = $this->_theme_url();
					$this->_insert_css($this->view('primary_category.css','',TRUE));
					$this->_include_theme_js('primary_category.js');

				}
			}

		}

		$this->_insert_js('jQuery("#accessoryTabs a.primary_category_acc").parent("li").remove()');

	}

	/**
	 * Include Theme CSS
	 */
	private function _include_theme_css($file)
	{
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().'css/'.$file.'" />');
	}

	/**
	 * Include Theme JS
	 */
	private function _include_theme_js($file)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().'js/'.$file.'"></script>');
	}

	// --------------------------------------------------------------------

	/**
	 * Insert CSS
	 */
	private function _insert_css($css)
	{
		$this->EE->cp->add_to_head('<style type="text/css">'.$css.'</style>');
	}

	/**
	 * Insert JS
	 */
	private function _insert_js($js)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
	}

	/**
	 * Theme URL
	 */
	private function _theme_url()
	{
		return $this->sc->addon_theme_url;
	}


}
// END Class Primary_category_extension