<?php

/**
| created     | datetime     | YES  |     | NULL    |                |
| created_by  | int(11)      | YES  |     | NULL    |                |
| enable      | tinyint(4)   |      |     | 1       |                |
| id          | int(11)      |      | PRI | NULL    | auto_increment |
| is_section  | tinyint(4)   |      |     | 0       |                |
| is_secure   | tinyint(4)   |      |     | 0       |                |
| meta_desc   | text         | YES  |     | NULL    |                |
| meta_key    | text         | YES  |     | NULL    |                |
| page_key    | varchar(100) | YES  |     | NULL    |                |
| page_left   | int(11)      | YES  |     | NULL    |                |
| page_right  | int(11)      | YES  |     | NULL    |                |
| page_url    | varchar(255) |      | MUL |         |                |
| parent_id   | int(11)      |      |     | 0       |                |
| short_title | varchar(255) | YES  |     | NULL    |                |
| sortorder   | int(11)      |      |     | 1       |                |
| status	VARCH
| template_id | int(11)      | YES  |     | NULL    |                |
| title       | varchar(100) | YES  |     | NULL    |                |
| type        | char(1)      |      |     | S       |                |
| updated     | datetime     | YES  |     | NULL    |                |
| updated_by  | int(11)      | YES  |     | NULL    |                |

auto-draft
inherit
publish
trash

registerPage($title, $page_key, $meta_keywords, $meta_description);

function createRequestHandlerPage($title, $key, $status, $template);

BlogEntry - PageLogic::createRequestHandlerPage('The Blog Entry', 'the-blog-entry',);
BlogCategory - PageLogic::createRequestHandlerPage('Attractions', 'attractions');
BlogTag - PageLogic::createRequestHandlerPage('Programming', 'programming');
BlogAuthor - PageLogic::createRequestHandlerPage('Robert Jones', 'robert-jones');

Blog Entry
Blog Category
Blog Tag
Blog Author

object_types
	blog
	page

object_taxonomies (type, name)
	category
	tags

object_taxonomy_terms (name, key)

object_taxonomy_relationships (object_type[page, blog], term_id)

object have type
	taxonomies are classifications
		types have taxonomies (taxonomy_types)
			taxonomies have terms (taxonomy_terms)
				terms have hierarchy (taxonomy_terms)
object_taxonomies (taxonomy_key, name)
	tags
	categories		
object_terms (taxonomy_key, term_key, name)
	blogging
	business
object_term_relationships
	object_type:page
	term_id
 SELECT tt.term_taxonomy_id, tt.term_id, tt.taxonomy, t.* FROM wp_term_taxonomy tt LEFT JOIN wp_terms t ON t.term_id = tt.term_id ORDER BY tt.taxonomy;
object_taxonomy_terms
object_terms
object_term_relationships

page/blog [object_type] -> tags/categories [taxonomy] -> programming/development [terms]

//object_types
object_type_taxonomies
object_taxonomies
object_taxonomy_terms
object_terms

ObjectTaxonomy
createObjectTaxonomyTerm
createObjectTermLink($object_type, $object_id, $taxonomy, $name);
createObjectTermLink('page', 1, 'tag', 'Development')

page [object type]
	tags [taxonomy]
		programming [term]
		news [term]
		building business [term]
	categories [taxonomy]
		attractions [term]
		activities [term]
blogs [object type]
	tags
		programming
		news
		building business
	categories
		attractions
		activities

*/

class BlogRequestHandler extends RequestHandler {
	/**
	 * Right now only supports single blogs
	 * In the future it may make sense to allow multiple blogs and to split this logic up further
	 */
	function canHandleRequest() {
		$requested_path = $this->getPageRequest()->getRequestedPath();
		$can_handle = (substr($requested_path, 0, 6) == '/blog/');
	
		if ($can_handle) {
			$path = substr($requested_path, 6);
			
			if ($path == 'index.html') {
				echo 'lists blog posts<br />';
			} else if ($path == 'rss.html' && $this->getPageRequest()->getPageResponse()->getOutputType() == PageResponse::OUTPUT_TYPE_XML) {
				echo 'output posts as xml';
			} else {
				if (preg_match('#[0-9]{4}/[0-9]{2}/[0-9]{2}/(.+)\.html#', $path, $path_parts)) {
					echo 'show blog post for slug: ' . $path_parts[1] . '<br />';
				} else {
					$parts = explode('/', $path);
					if (count($parts) > 0) {
						if ($parts[0] == 'category') {
							echo 'category<br />';
						} else if ($parts[0] == 'tag') {
							echo 'tag<br />';
						} else $can_handle = false;
					} else $can_handle = false;
					echo '<pre>';
					print_r($parts);
					
				}
			}
			exit;
		}
		
		return $can_handle;
	}
	
	function render() {
		return 'blog rendered';
	}
	
}

?>