<?php
ini_set('display_errors', 1);
Page::addScript(ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'jquery/jquery.nestedsortable.js');
Page::addScript(ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'jquery/jquery.editablemenu.js');
Page::addScript(ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'jquery/jquery.clickedit.js');

FrameworkManager::loadLogic('menu');
FrameworkManager::loadLogic('menuitem');
FrameworkManager::loadStruct('menuitem');

$menu_id = Page::get('menuid');
if (empty($menu_id) || (!$menu = MenuLogic::getMenuById($menu_id))) Page::redirect('index.html?error=MISSING+MENU');

if ($lbl_menu_name = Page::getControlById('menu_name')) $lbl_menu_name->setText($menu->name);

/*if ($delete = Page::get('delete')) MenuItemLogic::delete($delete);*/

if (Page::isPostBack()) {
	$action = Page::get('action');
	
	$response = array(
		'success' => false,
	);
	
	if ($action == 'save') {
		
		$num_items = Page::get('items');
		$menu_id = Page::get('menu');
		
		if (is_numeric($num_items) && is_numeric($menu_id)) {
			
			$base_format = 'item%d_';
			$menu_refs = array();
			$existing = MenuItemLogic::getMenuItemsByMenuId($menu_id);
			while ($item = $existing->getNext()) $item->_keep = 0;
			
			$response['success'] = true;
			$response['message'] = '# menus: ' . $num_items . '. ';
			
			for ($i=0; $i < $num_items; $i++) {
					
				// Base for retrieving menu item from POST
				$base = sprintf($base_format, $i);

				$ref = Page::get($base . 'ref');
				
				// menu item values
				$menu_refs[$ref] = null;
				
				$id = Page::get($base . 'id');
				$name = Page::get($base . 'name');
				$parent_ref = Page::get($base . 'parent'); // reference $ref value
				$url = Page::get($base . 'url');
				$item = null;
				 
				if (substr($id, 0, 3) == 'new') {
					$item = new MenuItemStruct();
					$item->menu_id = $menu_id;
					$item->_keep = 2;
					$existing->add($item);
				} else {
					$existing->resetIndex();
					while ($existing_item = $existing->getNext()) {
						#$response['message'] .= PHP_EOL . 'Item: ' . $existing_item->id . ' == ' . $ref . ';';
						if ($existing_item->id == $id) {
							$item = $existing_item;
							$item->_keep = 1;
							break;
						}
					}
				}
				$item->url = $url;
				$item->name = $name;
				$item->parent_id = $parent_ref; // references $ref value, will need to be rewritten to actually ID before saving
				$item->sortorder = $i+1;
				
				$menu_refs[$ref] = $item;
				
				/*menu4_id: "new"
				menu4_name: "Level 2"
				menu4_parent: "new_1"
				menu4_ref: "new_2"
				menu4_url: ""*/
			}
			
			ob_start();

			foreach($menu_refs as $ref=>$item) {
				if (empty($item->parent_id)) {
					$item->parent_id = 0;
				} else {
					$parent = $menu_refs[$item->parent_id];
					$item->parent_id = $parent->id;
				}
				MenuItemLogic::save($item);
				
				$menu_refs[$ref] = $item;
				echo PHP_EOL;
				echo $item->id . ' - ' . $item->parent_id . PHP_EOL;
				echo 'Error: ' . mysql_error();
			}
			$response['message'] .= ob_get_contents();
			ob_end_clean();
			
			#foreach($menu_refs as $ref => $item) {
				#$response['message'] .= PHP_EOL . 'Ref: ' . $ref . ' - Item: ' . (null === $item->id?'NULL':'NOT NULL') . ' - Keep: ' . $item->_keep . '; ';
			#}
			
			while ($item = $existing->getNext()) {
				if ($item->_keep == 0) { // Remove
					MenuItemLogic::delete($item->id);
				}
			}
		} else {
			$response['message'] = sprintf('items(%s) and menu(%s) must be numeric', $num_items, $menu_id);
		}
	} else {
		$response['message'] = 'Invalid action: ' . $action;
	}
	
	echo json_encode($response);
	exit;
 
}

$menu = MenuLogic::getMenuStructure($menu_id);

function renderMenu(ResultSet $items, $id=null, $class=null, $depth=0) {
	
	$output = '';
	if ($depth == 0) {
		$output .= '<ol';
		if (null !== $id) $output .= ' id="' . $id . '"';
		if (null !== $class) $output .= ' class="' . $class . '"';
		$output .= '>';
	}
	
	if ($items->getCount() > 0) {
		if ($depth > 0) $output .= '<ol>';
		while ($item = $items->getNext()) {
			$output .= sprintf('<li id="menuitem_%d" data-id="%d">', $item->id, $item->id);
			$output .= '<div class="menu-row">';
				$output .= sprintf('<div class="item-name">%s</div>', $item->name);
				$output .= sprintf('<div class="item-url">%s</div>', $item->url);
			$output .= '</div>';
			$output .= renderMenu($item->_children, null, null, $depth+1);
			$output .= '</li>';
		}
		if ($depth > 0) $output .= '</ol>';
	}
	if ($depth == 0) {
		$output .= '</ol>';
	}
	return $output;
}

?>