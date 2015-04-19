<?php if ( ! defined('EXT') ) exit('No direct script access allowed');

/**
 * Primary Category - Plugin
 *
 * @package		Solspace:Primary Category
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/primary_category
 * @license		http://www.solspace.com/license_agreement
 * @version		2.3.0
 * @filesource	primary_category/pi.primary_category.php
 */

require_once 'addon_builder/extension_builder.php';
require_once 'constants.primary_category.php';

// ----------------------------------------
//	Information array
// ----------------------------------------

$plugin_info = array(
	'pi_name'			=> 'Primary Category',
	'pi_version'		=> PRIMARY_CATEGORY_VERSION,
	'pi_author'			=> 'Solspace, Inc.',
	'pi_author_url'		=> 'http://www.solspace.com',
	'pi_description'	=> 'Select one of your categories as the primary for that entry, then display the primary category for that entry in your template.',
	'pi_usage'			=> ''
);

class Primary_category extends Extension_builder_primary_category
{
	public	$return_data;

	public	$entry_id			= 0;

	public	$category_fields	= array();

	private	$prim_ob;

	// ----------------------------------------
	//	Constructor
	// ----------------------------------------

	public function __construct ( $str='')
	{
		parent::__construct();

		ee()->load->helper('string');

		if ( ! is_object($this->prim_ob))
		{
			require_once 'ext.primary_category.php';

			$this->prim_ob = new Primary_category_ext();
		}


		//	----------------------------------------
		//	Grab entry id
		//	----------------------------------------

		$this->entry_id = ee()->TMPL->fetch_param('entry_id');

		if ( $this->_entry_id() === FALSE )
		{
			return ee()->TMPL->no_results();
		}

		// ----------------------------------------------
		//	Query
		// ---------------------------------------------

		$group_id			= '';

		$primary_category	= array();

		$select	= "c.cat_id AS main_cat_id, c.group_id AS main_group_id, cg.group_name AS main_group_name, c.*, cp.cat_name AS parent_name, cp.cat_url_title AS parent_url_title, t.entry_id ";

		//$sql	= "SELECT %select FROM exp_categories c LEFT JOIN {$this->sc->db->channel_titles} t ON t.primary_category = c.cat_id";
		$sql	= "/* Primary Category Plugin */ SELECT %select FROM exp_categories c LEFT JOIN exp_primary_category pc ON pc.primary_category_id = c.cat_id LEFT JOIN {$this->sc->db->channel_titles} t ON pc.entry_id = t.entry_id
			LEFT JOIN exp_category_groups cg ON cg.group_id = c.group_id";

		if ( ee()->db->table_exists('exp_category_field_data') === TRUE )
		{
			$full_version	= TRUE;
			$select	.= ", cfd.*";
			$sql	.= " LEFT JOIN exp_category_field_data cfd ON cfd.cat_id = c.cat_id";
		}

		$sql .= " LEFT OUTER JOIN exp_categories cp ON cp.cat_id = c.parent_id ";

		$sql	.= " WHERE t.entry_id = '".ee()->db->escape_str( $this->entry_id )."'";


		//	----------------------------------------
		//	Category group filter
		//	----------------------------------------
		$in_not_in = '';

		if( ee()->TMPL->fetch_param('category_group') != '' && strncmp(ee()->TMPL->fetch_param('category_group'), 'not ', 4) == 0 )
		{
			$in_not_in = "NOT IN (". ee()->db->escape_str( str_replace('|', ', ',  substr(ee()->TMPL->fetch_param('category_group'), 4) ) ) . ")";
		}
		elseif( ee()->TMPL->fetch_param('category_group') != '' )
		{
			$in_not_in = "IN (". ee()->db->escape_str( str_replace('|', ', ',  ee()->TMPL->fetch_param('category_group') ) ) . ")";
		}

		$sql 	.= ee()->TMPL->fetch_param('category_group') != '' ? " AND c.group_id " . $in_not_in : '';

		$sql	= str_replace( "%select", $select, $sql );

		$query	= ee()->db->query( $sql );

		/** ----------------------------------------------
		/**	Load array
		/** ---------------------------------------------*/
		$c = 0;
		if ( $query->num_rows() > 0 )
		{
			foreach($query->result_array() as $row)
			{
				$c++;
				$primary_category[$c][ 'primary_category_id' ]					= $row['main_cat_id'];
				$primary_category[$c][ 'primary_category_group_id' ]			= $row['main_group_id'];
				$primary_category[$c][ 'primary_category_group_name' ]			= $row['main_group_name'];
				$primary_category[$c][ 'primary_category_parent_id' ]			= $row['parent_id'];
				$primary_category[$c][ 'primary_category_name' ]				= $row['cat_name'];
				$primary_category[$c][ 'primary_category_description' ]			= $row['cat_description'];
				$primary_category[$c][ 'primary_category_image' ]				= $row['cat_image'];
				$primary_category[$c][ 'primary_category_order' ]				= $row['cat_order'];
				$primary_category[$c][ 'primary_category_parent_name' ]			= $row['parent_name'];
				$primary_category[$c][ 'primary_category_parent_url_title' ]	= $row['parent_url_title'];

				if ( isset( $full_version ) === TRUE )
				{
					$primary_category[$c][ 'primary_category_url_title' ]	= $row['cat_url_title'];
				}

				$category_groups[] = $row['main_group_id'];

				foreach($row as $label => $value)
				{
					if(strncmp( $label, 'field_id_', 9 ) == 0)
					{
						$primary_category[$c][ 'primary_category_' . $label ] = $value;
					}
				}

			}
		}
		else
		{
			require_once 'addon_builder/module_builder.php';
			$pcmod = new Module_builder_primary_category();
			$this->return_data = $pcmod->no_results();
			return $this->return_data;
		}

		$result_array = $query->result_array();


		/** ----------------------------------------------
		/**	Tagdata
		/** ---------------------------------------------*/

		$tagdata	= ( $str != '' ) ? $str: ee()->TMPL->tagdata;

		$tag_pair_data = '';

		foreach($primary_category as $row_number => $pc_value_arr)
		{
			$tag_pair_data = $tag_pair_data . $tagdata;

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

				ee()->load->library('Typography');

				/**	----------------------------------------
				/**	Loop and parse
				/**	----------------------------------------*/

				foreach ( $this->category_fields as $key => $val )
				{
					/**	----------------------------------------
					/**	Conditionals
					/**	----------------------------------------*/

					$cond[ 'primary_category_'.$key ]	=  $primary_category[$row_number]['primary_category_field_id_'.$val['field_id']];

					$tag_pair_data		= ee()->functions->prep_conditionals( $tag_pair_data, $cond );

					/**	----------------------------------------
					/**	Parse select
					/**	----------------------------------------*/

					foreach ( ee()->TMPL->var_pair as $k => $v )
					{
						if ( $k == "select_".$key )
						{

							$row = $primary_category[$row_number];

							$data			= ee()->TMPL->fetch_data_between_var_pairs( $tag_pair_data, $k );

							$tag_pair_data	= preg_replace( "/".LD.$k.RD."(.*?)".LD.preg_quote(T_SLASH,'/').$k.RD."/s",
														$this->_parse_select( $key, $row, $data ),
														$tag_pair_data );

						}
					}

					/**	----------------------------------------
					/**	Parse singles
					/**	----------------------------------------*/

					$tag_pair_data = ee()->TMPL->swap_var_single(
						'primary_category_' . $key,
						ee()->typography->parse_type(
							 $primary_category[$row_number]['primary_category_field_id_'.$val['field_id']],
							array(
									'text_format'	=> $this->category_fields[$key]['field_default_fmt'],
									'html_format'	=> 'safe',
									'auto_links'	=> 'n',
									'allow_img_url'	=> 'n'
							)
						),
						$tag_pair_data
					);

				}
			}

			$tag_pair_data	= ee()->functions->prep_conditionals( $tag_pair_data, $primary_category[$row_number] );

		}

		$this->return_data	= $tag_pair_data;
	}

	/**	End parse primary category */


	/** ----------------------------------------------
	/**	Category fields
	/** ---------------------------------------------*/

	public function _category_fields ( $group_id = '' )
	{


		if ( $group_id == '' ){ return FALSE;}

		if ( count( $this->category_fields ) > 0 ){ return $this->category_fields;}

		$query = ee()->db->query(
			"SELECT	*
			 FROM	exp_category_fields
			 WHERE	group_id IN (" . ee()->db->escape_str( implode(',', $group_id) ) . ")"
		);

		foreach ($query->result_array() as $row)
		{
			foreach ( $row as $key => $val )
			{
				$this->category_fields[ $row['field_name'] ][$key]	= $val;
			}
		}

		return $this->category_fields;
	}
	// End category fields


	//	----------------------------------------
	//	Parse select
	//	----------------------------------------

	public function _parse_select( $key = '', $row = array(), $data = '' )
	{
		//	----------------------------------------
		//	Fail?
		//	----------------------------------------

		if ( $key == '' OR $data == '' )
		{
			return '';
		}

		//	----------------------------------------
		//	Are there list items present?
		//	----------------------------------------

		if ( ! isset( $this->category_fields[$key]['field_list_items'] ) OR
			$this->category_fields[$key]['field_list_items'] == '' )
		{
			return '';
		}


		//	----------------------------------------
		//	Do we have a value?
		//	----------------------------------------

		if ( isset( $row['primary_category_field_id_'.$this->category_fields[$key]['field_id']] ) )
		{
			$value	= $row['primary_category_field_id_'.$this->category_fields[$key]['field_id']];
		}
		else
		{
			$value	= '';
		}

		//	----------------------------------------
		//	Create an array from value
		//	----------------------------------------

		$arr	= preg_split( "/\r|\n/", $value );

		//	----------------------------------------
		//	Loop
		//	----------------------------------------

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

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		return $return;
	}
	//	End parse select


	//	----------------------------------------
	//	Numeric
	//	----------------------------------------

	public function _numeric ( $item )
	{
		if ( is_array( $item ) === TRUE )
		{
			foreach ( $item as $val)
			{
				if ( $val == '' OR preg_match( '/[^0-9]/', $val ) != 0 ){ return FALSE;}
			}
		}
		elseif ( $item == '' OR preg_match( '/[^0-9]/', $item ) != 0 )
		{
			return FALSE;
		}

		return TRUE;
	}

	/* End numeric */


	//	----------------------------------------
	//	Entry id
	//	----------------------------------------

	public function _entry_id ( $t = 'channel' )
	{

		//	----------------------------------------
		//	Prep type
		//	----------------------------------------

		$type	= array();

		foreach ( array(
			'channel'	=> $this->sc->db->channel_titles,
			'gallery'	=> 'exp_gallery_entries' ) as $key => $val )
		{
			if ( stristr( $t, $key ) )
			{
				$type[]	= $val;
			}
		}

		//	----------------------------------------
		//	Cat segment
		//	----------------------------------------

		$cat_segment	= ee()->config->item("reserved_category_word");

		//	----------------------------------------
		//	Begin matching
		//	----------------------------------------

		$psql	= "SELECT entry_id";

		$psql	.= " FROM ".implode( ",", $type );

		$psql	.= " WHERE entry_id = '%eid'";

		if ( $this->_numeric( $this->entry_id ) === TRUE )
		{
			$sql	= str_replace( "%eid", ee()->db->escape_str( $this->entry_id ), $psql );

			$query	= ee()->db->query( $sql );

			if ( $query->num_rows() > 0 )
			{
				$this->entry_id	= $query->row('entry_id');

				return TRUE;
			}
		}
		elseif ( ee()->uri->query_string != '' OR
				( isset( ee()->uri->page_query_string ) === TRUE AND
					ee()->uri->page_query_string != '' ) )
		{

			$qstring	= ( ee()->uri->page_query_string != '' ) ?
							ee()->uri->page_query_string :
							ee()->uri->query_string;

			//	----------------------------------------
			//	Do we have a pure ID number?
			//	----------------------------------------

			if ( $this->_numeric( $qstring ) === TRUE )
			{
				$sql	= str_replace( "%eid", ee()->db->escape_str( $qstring ), $psql );

				$query	= ee()->db->query( $sql );

				if ( $query->num_rows() > 0 )
				{
					$this->entry_id	= $query->row('entry_id');

					return TRUE;
				}
			}
			else
			{
				//	----------------------------------------
				//	Parse day
				//	----------------------------------------

				if (preg_match("#\d{4}/\d{2}/(\d{2})#", $qstring, $match))
				{
					$partial	= substr($match['0'], 0, -3);

					$qstring	= trim_slashes(str_replace($match['0'], $partial, $qstring));
				}

				//	----------------------------------------
				//	Parse /year/month/
				//	----------------------------------------

				if (preg_match("#(\d{4}/\d{2})#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['1'], '', $qstring));
				}

				//	----------------------------------------
				//	Parse page number
				//	----------------------------------------

				if (preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['0'], '', $qstring));
				}

				//	----------------------------------------
				//	Parse category indicator
				//	----------------------------------------

				// Text version of the category

				//EE 1.x only for now
				if (APP_VER < 2.0 AND
					preg_match("#^".$cat_segment."/#", $qstring, $match) AND
					ee()->TMPL->fetch_param('weblog'))
				{
					$qstring	= str_replace($cat_segment.'/', '', $qstring);

					$sql		= " SELECT DISTINCT cat_group
									FROM	{$this->sc->db->channels}
									WHERE	site_id
									IN		('".implode("','", ee()->TMPL->site_ids)."') ";

					if (defined('UB_BLOG_ID') AND
						defined('USER_BLOG') AND
						USER_BLOG !== FALSE)
					{
						$sql	.= " AND weblog_id='" . UB_BLOG_ID . "'";
					}
					else
					{
						$sql	.= ee()->functions->sql_andor_string(
							ee()->TMPL->fetch_param('weblog'),
							'blog_name'
						);
					}

					$query	= ee()->db->query($sql);

					if ($query->num_rows() == 1)
					{
						$result	= ee()->db->query(
							"SELECT	cat_id
							 FROM	exp_categories
							 WHERE	site_id
							 IN		('".implode("','", ee()->TMPL->site_ids)."')
							 AND	cat_name='" . ee()->db->escape_str($qstring) . "'
							 AND	group_id='{$query->row('cat_group')}'"
						);

						if ($result->num_rows() == 1)
						{
							$qstring	= 'C' . $result->row('cat_id');
						}
					}
				}

				/**	----------------------------------------
				/**	Numeric version of the category
				/**	----------------------------------------*/

				if (preg_match("#^C(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['0'], '', $qstring));
				}

				/**	----------------------------------------
				/**	Remove "N"
				/**	----------------------------------------*/

				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['0'], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse URL title
				/**	----------------------------------------*/

				if (strstr($qstring, '/'))
				{
					$xe			= explode('/', $qstring);
					$qstring	= current($xe);
				}

				$sql = "SELECT	{$this->sc->db->channel_titles}.entry_id
						FROM	{$this->sc->db->channel_titles},
								{$this->sc->db->channels}
						WHERE	{$this->sc->db->channel_titles}.{$this->sc->db->channel_id} = {$this->sc->db->channels}.{$this->sc->db->channel_id}
						AND		{$this->sc->db->channel_titles}.site_id
						IN		('".implode("','", ee()->TMPL->site_ids)."')
						AND		{$this->sc->db->channel_titles}.url_title = '".ee()->db->escape_str($qstring)."'";


				//HANDLE USER_BLOG case in EE1.x
				if (APP_VER < 2.0 AND
					defined('USER_BLOG') AND
					defined('UB_BLOG_ID') AND
					USER_BLOG !== FALSE)
				{
					$sql .= " AND {$this->sc->db->titles}.{$this->sc->db->id} = '" . UB_BLOG_ID . "'";
				}
				//.is_user_blog
				elseif (APP_VER < 2.0 AND $this->column_exists('is_user_blog', $this->sc->db->titles))
				{
					$sql .= " AND {$this->sc->db->titles}.is_user_blog = 'n'";
				}


				$query	= ee()->db->query($sql);

				if ( $query->num_rows() > 0 )
				{
					$this->entry_id = $query->row('entry_id');

					return TRUE;
				}
			}
		}

		return FALSE;
	}
	// End entry id

	// ----------------------------------------
	//	Usage
	// ----------------------------------------

	public function usage ()
	{
		return '';
	}
	// END usage
}
// END Primary_category