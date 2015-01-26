<?php
/**
 * Data structure for Pages 
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 *
 * types:
 *	S = single
 *	G = group (group of pages)
 *	L = forward link
 *	C = custom (customer handler)
 **/
class PageStruct {
	var $created, $created_by, $id, $is_section, $is_secure, $meta_key, $meta_desc, $page_left, $page_right, $page_url, $parent_id, $service_handler_class, $short_title, $status, $template_id, $title, $type, $updated, $updated_by;
	var $template_contents, $template_src, $template_name; // Template
}

?>