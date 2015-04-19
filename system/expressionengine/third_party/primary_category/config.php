<?php if ( ! defined('EXT')) exit('No direct script access allowed');

/**
 * Primary Category - Config
 *
 * NSM Addon Updater config file.
 *
 * @package		Solspace:Primary Category
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/primary_category
 * @license		http://www.solspace.com/license_agreement
 * @version		2.3.0
 * @filesource	primary_category/config.php
 */

//since we are 1.x/2.x compatible, we only want this to run in 1.x just in case
if (APP_VER >= 2.0)
{
	require_once PATH_THIRD . '/primary_category/constants.primary_category.php';

	$config['name']    								= 'Primary Category';
	$config['version'] 								= PRIMARY_CATEGORY_VERSION;
	$config['nsm_addon_updater']['versions_xml'] 	= 'http://www.solspace.com/software/nsm_addon_updater/primary_category';
}