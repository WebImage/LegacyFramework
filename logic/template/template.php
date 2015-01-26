<?php

FrameworkManager::loadDAO('template');

class TemplateLogic {
	
	public static function getTemplateById($template_id) {
		$template_dao = new TemplateDAO();
		return $template_dao->load($template_id);
	}
	
	public static function getTemplates($type) {
		$template_dao = new TemplateDAO();
		return $template_dao->getTemplates($type);
	}
	
#	public static function getTemplatesAvailableTemplatesByType($type) {
#	}
	
	public static function getAllTemplates(){
		$template_dao = new TemplateDAO();
		return $template_dao->getAllTemplates();
	}
	
	public static function save($template_struct) {
		$template_dao = new TemplateDAO();
		return $template_dao->save($template_struct);
	}
	/**
	 * @param string $type Any string value to distinguish the type of template - e.g. Page, ControlXyz
	 * @param int $object_id The primary id associated with the object
	 * @param string $object_tag A tag that can be used to distinguish between templates - for example, to distinguish between different views (grid, list, etc.)
	 */
	public static function getObjectTemplatesByTypeAndObjectId($type, $object_id, $object_tag=null) {
		$template_dao = new TemplateDAO();
		return $template_dao->getObjectTemplatesByTypeAndObjectId($type, $object_id, $object_tag);
	}
	
	public static function getBestTemplateByObjectTypeAndObjectId($type, $object_id, $object_tag=null, $locale=null, $profile_id=null) {
		#if (is_null($locale)) $locale = LocaleManager::getCurrentLocale();
		#if (is_null($profile_id)) $profile_id = ProfileManager::getCurrentProfileId();
		$templates = TemplateLogic::getObjectTemplatesByTypeAndObjectId($type, $object_id, $object_tag);
		
		$high_score = 0;
		$selected_template = null;
		/*
		score	| locale | profile	| template_file
		---------------------------------------
		8	| en-US	| MobileMDP	| /templates/thetemplate-MobileMDP-en-US.tpl	Exact Locale + Exact Profile
		7	| -US	| MobileMDP	| /templates/thetemplate-MobileMDP--US.tpl	Partial Locale + Exact Profile
		6	| en	| MobileMDP	| /templates/thetemplate-MobileMDP-en.tpl	Partial Locale + Exact Profile
		5	| 	| MobileMDP	| /templates/thetemplate-MobileMDP.tpl		Global Locale + Exact Profile
		4	| en-US	|		| /templates/thetemplate-en-US.tpl		Exact Locale + Global Profile
		3	| -US	| 		| /templates/thetemplate-en.tpl			Partial Locale + Exact Profile
		2	| en	| 		| /templates/thetemplate-en.tpl			Partial Locale + Exact Profile
		1	| 	| 		| /templates/thetemplate.tpl			Global Locale + Global Profile

		*/
		while ($template = $templates->getNext()) {
			$locale_parts = explode('-', $template->locale);
			$num_locale_parts = count($locale_parts);

			$language_code = '';
			$country_code = '';
			if ($num_local_parts >= 1) $language_code = $locale_parts[0];
			if ($num_locale_parts > 1) $country_code = $locale_parts[1];

			$score = 0;

			if (empty($template->locale) && empty($template->profile)) $score += 1;
			else {
				if (!empty($language_code) && !empty($country_code)) $score += 4;
				else if (empty($language_code) && !empty($country_code)) $score += 3;
				else if (!empty($language_code) && empty($country_code)) $score += 2;
				else $score += 1; // Only happens if template profile is not empty

				if (!empty($template->profile)) $score += 4;
			}
			if ($score > $high_score) {
				$high_score = $score;
				$selected_template = $template;
			}
		}
		if (is_null($selected_template)) return false;
		return $selected_template;
	}
	/**
	 * Take a $template value such as "default" and build a list of possible template combinations in order of preference.
	 * Template list is built from locales (if enabled), profiles (if enabled), and the admin theme (if set)
	 * For example, if locales, profiles, and admin theme are all enabled and the $template value "default" is passed, this function will return (assuming locale=en-US, profile=Default, and admin theme=blueadmin):
	 * 
	 * /templates/themes/blueadmin/admin/default-Default-en-US.tpl
	 * /templates/themes/blueadmin/admin/default-Default--US.tpl
	 * /templates/themes/blueadmin/admin/default-Default-en.tpl
	 * /templates/themes/blueadmin/admin/default-Default.tpl
	 * /templates/themes/blueadmin/admin/default-en-US.tpl
	 * /templates/themes/blueadmin/admin/default-en.tpl
	 * /templates/themes/blueadmin/admin/default--US.tpl
	 * /templates/themes/blueadmin/admin/default.tpl
	 *
	 * /templates/admin/default-Default-en-US.tpl
	 * /templates/admin/default-Default--US.tpl
	 * /templates/admin/default-Default-en.tpl
	 * /templates/admin/default-Default.tpl
	 * /templates/admin/default-en-US.tpl
	 * /templates/admin/default-en.tpl
	 * /templates/admin/default--US.tpl
	 * /templates/admin/default.tpl
	 *
	 */
	public static function getAdminTemplates($template) {//, $locale=null, $profile_id=null) {
		$template_keys = array();
		$locale_combos = array();
		$template_base = $template;
		$check_templates = array();
		
		// Setup locale combinations
		if (ConfigurationManager::get('ENABLE_ADMIN_LOCALES') == 'true') {
			FrameworkManager::loadManager('locale');
			$locale = LocaleManager::getCurrentLocale();
			preg_match('/([a-z]*)-([A-Z]*)/', $locale, $locale_parts);
			if (isset($locale_parts[1])) {
				if (isset($locale_parts[2])) {
					array_push($locale_combos, $locale_parts[1] . '-' . $locale_parts[2]);
				}
				array_push($locale_combos, $locale_parts[1]);
			}
			if (isset($locale_parts[2])) {
				array_push($locale_combos, '-' . $locale_parts[2]);
			}
		}
		// Add profile combinations
		if (ConfigurationManager::get('ENABLE_ADMIN_PROFILES') == 'true') {
			foreach($locale_combos as $locale_combo) {
				array_push($template_keys, $template_base . '-' . Profiles::getCurrentProfileName() . '-' . $locale_combo);
			}
			array_push($template_keys, $template_base . '-' . Profiles::getCurrentProfileName());
		}
		
		// Add locale combinations
		foreach($locale_combos as $locale_combo) {
			array_push($template_keys, $template_base . '-' . $locale_combo);
		}
		array_push($template_keys, $template_base);
		
		// Add theme templates
		if (ConfigurationManager::get('ADMIN_THEME')) {
			foreach($template_keys as $template_key) {
				array_push($check_templates, '~/templates/themes/' . ConfigurationManager::get('ADMIN_THEME') . '/admin/' . $template_key . '.tpl');
			}
		}
		// Add standard templates
		foreach($template_keys as $template_key) {
			array_push($check_templates, '~/templates/admin/' . $template_key . '.tpl');
		}
		return $check_templates;
		
	}
}

?>