<?php
/**
 * Data structure for PageControls 
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 *
 * status options:
 * 	published
 *	draft
 *	deleted
 *	archive
 */
class PageControlStruct {
	var $config, $control_id, $created, $created_by, $favorite_title, $id, $is_draft, $is_favorite, $mirror_id, $page_id, $sortorder, $placeholder, $template_id, $title, $updated, $updated_by; // Page Control
	var $class_name, $control_src, $control_label; // Control
}

?>