<?php
/**
 * 01/27/2010        (Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 */
FrameworkManager::loadLibrary('admin');

class AdminNavControl extends WebControl
{
	
	public function prepareContent()
	{
		$menu = $this->getAdminMenu();
		$this->setRenderedContent($this->generateSectionHtml($menu));
	}
	
	private function getAdminMenu()
	{
		$paths = array_reverse(PathManager::getPaths());
		
		FrameworkManager::loadLibrary('xml.compile');
		
		$menu = new AdminMenu();
		
		foreach ($paths as $path) {
			$admin_nav = $path . 'config/adminnav.xml';
			if (file_exists($admin_nav)) {
				$nav_contents = file_get_contents($admin_nav);
				try {
					$nav_xml = CWI_XML_Compile::compile($nav_contents);
					$menu->importFromXml($nav_xml);
				} catch (CWI_XML_CompileException $e) {
					echo 'Compile Exception: ' . $e->getMessage();
					exit;
				} catch (Exception $e) {
					echo 'Some other error was found: ' . $e->getMessage();
					exit;
				}
				
			}
		}
		
		return $menu;
	}
	
	private function generateSectionHtml(AdminMenu $menu, $parent = null, $level = 1)
	{
		$output = '';
		
		$items = $menu->getItems($parent);
		
		/** @var AdminMenuItem $item */
		foreach ($items as $item) {
			
			if ($this->userCanView($item)) {
				
				$child_html = $this->generateSectionHtml($menu, $item->getId(), $level + 1);
				
				$menu_class_html = $this->getMenuClassHtml($level);
				
				$output .= sprintf('<li%s>', $menu_class_html);
				
				$link_attr = array('href' => '#');
				
				if (strlen($item->getUrl()) > 0) {
					// Link
					if ($item->shouldOpenNewWindow()) {
						$window_attrs = $item->getNewWindowAttributes();
						$window_attrs = str_replace("'", "\'", $window_attrs);
						$link_attr['onclick'] = "window.open('" . $item->getUrl() . "', '', '" . $window_attrs . "');return false;";
					} else {
						$link_attr['href'] = $item->getUrl();
					}
				}
				
				$caret = '';
				
				if ($level == 1) {
					
					if (empty($child_html)) {
						
						if (count($link_attr) == 1 && isset($link_attr['href']) && ($link_attr['href'] == '#' || empty(trim($link_attr['href'])))) {
							continue;
						}
						
					} else if (!empty($child_html)) {
						
						$link_attr['class'] = 'dropdown-toggle';
						$link_attr['data-toggle'] = 'dropdown';
						
						$caret = ' <b class="caret"></b>';
					}
				}
				
				if ($level == 2) { // Level 2 is just a section header
					$output .= $item->getTitle();
				} else {
					$link = '<a';
					foreach ($link_attr as $attr_key => $attr_val) {
						$link .= ' ' . $attr_key . '="' . $attr_val . '"';
					}
					
					$link .= '>';
					$link .= htmlentities($item->getTitle());
					$link .= $caret;
					$link .= '</a>';
					
					$output .= $link;
				}
				
				$end_tag = '</li>' . PHP_EOL;
				
				if ($level == 3) {
					$output_format = '%2$s%1$s';
				} else {
					$output_format = '%s%s';
				}
				
				$output .= sprintf($output_format, $child_html, $end_tag);
			}
		}
		
		$class = '';
		if ($level == 1) $class = 'nav navbar-nav';
		else if ($level == 2) $class = 'dropdown-menu';
		if (!empty($class)) $class = ' class="' . $class . '"';
		
		// Don't wrap items if (1) there is no output and (2) the items are not part of a group
		if (!empty($output) && $level != 3) $output = sprintf('<ul%s>%s</ul>', $class, $output) . PHP_EOL;
		
		return $output;
	}
	
	private function userCanView(AdminMenuItem $item)
	{
		return ($item->isEnabled() && $this->userHasRequiredRole($item));
		return ($item->isEnabled() && $this->userHasRequiredRole($item) && $this->userHasRequiredPermission($item));
	}
	
	private function userHasRequiredRole(AdminMenuItem $item)
	{
		
		$roles = $item->getRoles();
		if (!is_array($roles)) {
			echo '<pre>';print_r($item);echo '<hr />' . __FILE__ .':'.__LINE__;exit;
		}
		if (count($roles) == 0) return true;
		foreach ($roles as $role) {
			if (Roles::isUserInRole($role)) return true;
		}
		
		// If we have gotten this far, the user does not have role
		return false;
	}
	
	private function userHasRequiredPermission(AdminMenuItem $item)
	{
		
		$permissions = $item->getPermissions();
		if (count($permissions) == 0) return true;
		
		foreach ($permissions as $permission) {
			if (Roles::canRead($permission)) return true;
		}
		
		// If we have gotten this far, the user does not have permission
		return false;
	}
	
	private function getMenuClassHtml($level)
	{
		$classes = array();
		if ($level == 1) $classes[] = 'dropdown';
		else if ($level == 2) $classes[] = 'nav-header';
		
		$class = (count($classes) > 0) ? ' class="' . implode(' ', $classes) . '"' : '';
		
		return $class;
	}
	
}