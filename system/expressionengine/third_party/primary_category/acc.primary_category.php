<?php if ( ! defined('EXT') ) exit('No direct script access allowed');

/**
 * Primary Category - Accessory
 *
 * @package		Solspace:Primary Category
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/primary_category
 * @license		http://www.solspace.com/license_agreement
 * @version		2.3.0
 * @filesource	primary_category/acc.primary_category.php
 */

/**
 * An explanation about this accessory:
 *
 * At the time of development of this add-on, several hooks were
 * not available in ExpressionEngine 2, specifically the
 * 'publish_form_category_display' hook.
 *
 * This accessory was created to get the required functionality
 * for the Primary Category extension to work in the Publish page.
 */


require_once 'ext.primary_category.php';

class Primary_category_acc extends Primary_category_ext {}