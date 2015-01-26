<?php
/**
 * CHANGELOG
 * 10/11/2010	(Robert Jones) Added support for permissions
 */
function _adminNavCheckValue($value) {

	if (preg_match_all('#%(.+?)%#', $value, $matches)) {
		
		for ($i=0; $i < count($matches[0]); $i++) {
			if ($configuration_value = ConfigurationManager::get($matches[1][$i])) {
				$value = str_replace($matches[0][$i], $configuration_value, $value);
			}
		}
	}
	
	return $value;
}
function get_admin_nav_struct() {
	return array(
		'_title'	=> '',
		'_url'		=> '',
		'_description'	=> '',
		'_image'	=> '',
		'_roles'	=> '',
		'_permissions'	=> '',
		'_new_window'	=> false
	);
}

function adminNav(&$nav, $site_map) {
	if ($sections = $site_map->getPath('/siteMap/siteMapNode')) {
		foreach($sections as $section) {
		
			$section_title		= $section->getParam('title');
			$section_url		= $section->getParam('url');
			$section_roles		= $section->getParam('roles');
			$section_permissions	= $section->getParam('permissions');
			
			if (!isset($nav[$section_title])) {
				$nav[$section_title] = get_admin_nav_struct();
			}
			
			if ($section_url) $nav[$section_title]['_url']			= _adminNavCheckValue($section_url);
			if ($section_title) $nav[$section_title]['_title']		= _adminNavCheckValue($section_title);
			if ($section_roles) $nav[$section_title]['_roles']		= _adminNavCheckValue($section_roles);
			if ($section_permissions) $nav[$section_title]['_permissions']	= _adminNavCheckValue($section_permissions);
			
			if ($groups = $section->getPath('siteMapNode')) {
			
				foreach($groups as $group) {
				
					$group_title = $group->getParam('title');
					$group_url = $group->getParam('url');
					$group_roles = $group->getParam('roles');
					$group_permissions = $group->getParam('permissions');
					
					if (!isset($nav[$section_title][$group_title])) {
						$nav[$section_title][$group_title] = get_admin_nav_struct();
					}
					
					if ($group_title)	$nav[$section_title][$group_title]['_title']		= _adminNavCheckValue($group_title);
					if ($group_url)		$nav[$section_title][$group_title]['_url']		= _adminNavCheckValue($group_url);
					if ($group_roles)	$nav[$section_title][$group_title]['_roles']		= _adminNavCheckValue($group_roles);
					if ($group_permissions)	$nav[$section_title][$group_title]['_permissions']	= _adminNavCheckValue($group_permissions);
					
					
					if ($buttons = $group->getPath('siteMapNode')) {
					
						foreach($buttons as $button) {
						
							$button_title		= $button->getParam('title');
							$button_url		= $button->getParam('url');
							$button_description	= $button->getParam('description');
							$button_image		= $button->getParam('icon');
							$button_roles		= $button->getParam('roles');
							$button_permissions	= $button->getParam('permissions');
							$new_window		= $button->getparam('newWindow');
							
							if (!isset($nav[$section_title][$group_title][$button_title])) {
								$nav[$section_title][$group_title][$button_title] = get_admin_nav_struct();
							}

							if ($button_title)		$nav[$section_title][$group_title][$button_title]['_title']		= _adminNavCheckValue($button_title);
							if ($button_url)		$nav[$section_title][$group_title][$button_title]['_url']		= _adminNavCheckValue($button_url);
							if ($button_description)	$nav[$section_title][$group_title][$button_title]['_description']	= _adminNavCheckValue($button_description);
							if ($button_image)		$nav[$section_title][$group_title][$button_title]['_image']		= _adminNavCheckValue($button_image);
							if ($button_roles)		$nav[$section_title][$group_title][$button_title]['_roles']		= _adminNavCheckValue($button_roles);
							if ($button_permissions)	$nav[$section_title][$group_title][$button_title]['_permissions']	= _adminNavCheckValue($button_permissions);
							
							if ($new_window) {
								if ($new_window != 'false') {
									$nav[$section_title][$group_title][$button_title]['_new_window'] = $new_window;
								}
							}
						}
					}
				}
			}
		}
	}
}

?>