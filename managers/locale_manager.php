<?php

class LocaleManager {
	private $supportedLocales = array();
	private $currentLocale;
	
	public static function getInstance() {
		return Singleton::getInstance('LocaleManager');
	}
	/**
	 * Get the current locale
	 */
	public static function getCurrentLocale() {
		$_this = LocaleManager::getInstance();
		$current_locale = $_this->currentLocale;
		if (empty($current_locale)) {
			// Check first to see if the user has overridden the default locale
			if ($session_locale = SessionManager::get('locale')) {
				return $session_locale;
			// Otherwise, return the default locale
			} else {
				return LocaleManager::getDefaultLocale();
			}
		} else return $current_locale;
	}
	/**
	 * Resets the current locale 
	 */
	public static function resetLocale() {
		SessionManager::del('locale');
	}
	/**
	 * Get the default locale
	 */
	public static function getDefaultLocale() {
		$supported_locales = LocaleManager::getUserSupportedLocales();
		if (count($supported_locales) > 0) {
			return $supported_locales[0];
		} else return false;
	}
	
	public static function getUserSupportedLocales() {
		$_this = LocaleManager::getInstance();
		if (empty($_this->supportedLocales)) {
			// Container for results
			$supported_locales = array();
			
			// Retrieve list from browser
			$browser_locales = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			
			// Iterate through each
			foreach($browser_locales as $browser_locale) {
				list($locale_code) = explode(';', $browser_locale, 2); // Break off possible quotient value
				array_push($supported_locales, $locale_code);
			}
			$_this->supportedLocales = $supported_locales;
			return $supported_locales;
			
		} else return $_this->supportedLocales;
	}
	
	/**
	 * Sets the current locale
	 * @param string 
	 * @return void
	 */
	public static function setCurrentLocale($locale_code) { 
		$_this = LocaleManager::getInstance();
		$_this->currentLocale = $locale_code;
		SessionManager::set('locale', $locale_code);
	}
	

}

?>