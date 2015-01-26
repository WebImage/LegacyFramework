<?php
/**
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 */
FrameworkManager::loadLibrary('admin');

class AdminNavControl extends WebControl {
	function _userInRole($roles_string) {
		if (empty($roles_string)) return true;
		else {
			$roles = explode(',', $roles_string);
			foreach($roles as $role) {
				$role = trim($role);
				
				if (Roles::isUserInRole($role)) {
					return true;
				}
			}
		}
		// If we have gotten this far, the user does not have permission
		return false;
	}
	function _userHasPermission($permission_string) {
		if (empty($permission_string)) return true;
		else {
			$permissions = explode(',', $permission_string);
			
			foreach($permissions as $permission) {
				$permission = trim($permission);
				
				if (Roles::canRead($permission)) {
					return true;
				}
			}
		}
		// If we have gotten this far, the user does not have permission
		return false;
	}
	
	private function generateSectionHtml($nav, $level=1) {
		$output = '';
		
		foreach($nav as $key=>$val) {

			if (substr($key, 0, 1) != '_' && $this->_userInRole($val['_roles']) && $this->_userHasPermission($val['_permissions'])) {
				
				$children_html = $this->generateSectionHtml($val, $level+1);
				
				$class = '';
				if ($level == 1) $class = 'dropdown';
				else if ($level == 2) $class = 'nav-header';
				
				if (!empty($class)) $class = ' class="' . $class . '"';
				
				$output .= sprintf('<li%s>', $class);
				
				$title = $key;
				
				$link_attr = array('href'=>'#');
				
				if (!empty($val['_url'])) {
					// Link
					if (isset($val['_new_window']) && $val['_new_window']) {
						
						$link_attr['onclick'] = "window.open('" . $val['_url'] . "', '', '" . $val['_new_window']. "');return false;";
					
					} else {
						
						$link_attr['href'] = $val['_url'];

					}
					
				}
				
				$caret = '';
				if ($level == 1 && !empty($children_html)) {
					
					#if ($link_attr['href'] != '#') {
						$link_attr['class'] = 'dropdown-toggle';
						$link_attr['data-toggle'] = 'dropdown';
					#} 
					
					#if (!empty($children_html)) 
					$caret = ' <b class="caret"></b>';
				}
				
				$link_format = '<a';
				foreach($link_attr as $attr_key=>$attr_val) {
					$link_format .= ' ' . $attr_key . '="' . $attr_val . '"';
				}
				
				$link_format .= '>';
				$link_format .= '%s';
				$link_format .= $caret;
				$link_format .= '</a>';
				
				if ($level == 2) {
					$output .= $title;
				} else {
					$output .= sprintf($link_format, $title);
				}
				
				$end_tag = '</li>' . PHP_EOL;
				
				if ($level == 3) {
					$output_format = '%2$s%1$s';
				} else {
					$output_format = '%s%s';
				}
				
				$output .= sprintf($output_format, $children_html, $end_tag);
				
				
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
	
	function prepareContent() {
		
		$paths = array_reverse(PathManager::getPaths());
				
		FrameworkManager::loadLibrary('xml.compile');
		$nav = array();
		foreach($paths as $path) {
			$admin_nav = $path . 'config/adminnav.xml';
			if (file_exists($admin_nav)) {
				$nav_contents = file_get_contents($admin_nav);
				try {
					$nav_xml = CWI_XML_Compile::compile($nav_contents);
					adminNav($nav, $nav_xml);
				} catch (CWI_XML_CompileException $e) {
					echo 'Compile Exception: ' . $e->getMessage();exit;
				} catch (Exception $e) {
					echo 'Some other error was found: ' . $e->getMessage();exit;
				}
				
			}
		}
		
		if (is_array($nav)) {
			
			$this->setRenderedContent( $this->generateSectionHtml($nav) );
			
		}
	
	}
}

?>