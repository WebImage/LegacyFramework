<?php

FrameworkManager::loadDAO('pagestat');
class PageStatLogic {
	public static function getPageStatById($id) {
		$page_log_dao = new PageStatDAO();
		return $page_log_dao->load($id);
	}
	/*public static function getPageStatsWithUnprocessedBrowser() {
		$page_log_dao = new PageStatDAO();
		return $page_log_dao->getPageStatsWithUnprocessedBrowser();
	}*/
	private static function getGroupedUserAgentsForUnprocessedBrowsers() {
		$page_log_dao = new PageStatDAO();
		return $page_log_dao->getGroupedUserAgentsForUnprocessedBrowsers();
	}
	public static function getPageStatsByDate($start_date, $end_date) {
		$page_log_dao = new PageStatDAO();
		return $page_log_dao->getPageStatsByDate($start_date, $end_date);
	}
	
	public static function getTopPageViewsForPeriod($start_date, $end_date, $top=10) {
		$page_log_dao = new PageStatDAO();
		$page_log_dao->paginate(1, $top);
		return $page_log_dao->getTopPageViewsForPeriod($start_date, $end_date);
	}
	
	public static function getTopBrowsersForPeriod($start_date, $end_date, $top=10) {
		$page_log_dao = new PageStatDAO();
		$page_log_dao->paginate(1, $top);
		return $page_log_dao->getTopBrowsersForPeriod($start_date, $end_date);
	}
	
	public static function getPageViewsByDay($start_date, $end_date) {
		$page_log_dao = new PageStatDAO();
		return $page_log_dao->getPageViewsByDay($start_date, $end_date);
	}
	// Unprocessed records only
	private static function setUnprocessedBrowsersByUserAgent($page_stat_struct) {
		if (is_a($page_stat_struct, 'PageStatStruct')) {
			$page_log_dao = new PageStatDAO();
			return $page_log_dao->setUnprocessedBrowsersByUserAgent($page_stat_struct);
		}
	}
	
	public static function updateUnprocessedBrowsers() {
		/** 
		 * Not working reliably
		 
		$stats = PageStatLogic::getGroupedUserAgentsForUnprocessedBrowsers();
		if (function_exists('get_browser')) {
			while ($stat = $stats->getNext()) {
				$browser_info = get_browser($stat->user_agent);
		
				$stat->browser = $browser_info->browser;
				$stat->browser_majver = $browser_info->majorver;
				$stat->browser_minver = $browser_info->minorver;
				$stat->is_crawler = ($browser_info->crawler == 1) ? 1 : 0;
				$stat->os = $browser_info->platform;
	
				PageStatLogic::setUnprocessedBrowsersByUserAgent($stat);
			}
		}
		**/
	}

	
	public static function createStatForCurrentUser($path, $query) {
		FrameworkManager::loadStruct('pagestat');
		
		$page_id		= (Page::getPageId()) ? Page::getPageId() : 0;
		$request_handler	= Page::getCurrentPageRequest()->getRequestHandler();
		
		$user_agent		= (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$membership_id		= ($user = Membership::getUser()) ? $user->getId() : 0;
		$referrer		= (isset($_SERVER['HTTP_REFERER'])) ?  $_SERVER['HTTP_REFERER'] : '';
		$protocol		= (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : '';
		
		$stat			= new PageStatStruct();
		$stat->domain		= ConfigurationManager::get('DOMAIN');
		$stat->ip_address	= $_SERVER['REMOTE_ADDR'];
		$stat->membership_id	= $membership_id;
		$stat->page_id		= $page_id;
		$stat->path		= $path;
		$stat->protocol		= $protocol;
		$stat->referrer		= $referrer;
		$stat->query		= $query;
		$stat->request_handler	= (!is_null($request_handler)) ? get_class($request_handler) : '';
		$stat->session_id	= SessionManager::getId();
		$stat->user_agent	= $user_agent;
		
		/**
		 * ps = page stat
		 * SESSION: 
		 * 	last_access - stored in session?
		 *	page_views
		 *	user_id

		 * COOKIE: 
		 *	first_access
		 *	session_start
		 *	last_access
		 * 	session_sequence_num (index) - starts at 1 (updates after session expiration)
		 *
		 * 1. Look at session information first as it is more likely to be reliable
		 * 2. Fall back to cookie information if session is new.  
		 * 	The reason is that a PHP session typically ends when the user closes their browser, as opposed to a specific period of time (say 30 minutes)
		 *	If a user closes their browser, but opens up the site again within the STAT_SESSION_TIMEOUT timeframe then the session should 
		 *	be assumbed to be continuous, instead of assuming a brand new session 
		 */
		
		define('STATS_LAST_ACCESS', 'ps_last_access');
		define('STATS_SESSION_PAGE_VIEWS', 'ps_session_page_views');
#		define('STATS_TOTAL_PAGE_VIEWS
		define('STATS_TOTAL_SESSIONS', 'ps_total_sessions');
		define('STATS_USER_ID_KEY', 'ps_user_id');
		
		define('COOKIE_STAT_STORAGE', 'ps');
		#define('COOKIE_STAT_VERIFICATION', 'psv');
		
		ConfigurationManager::set('STATS_SESSION_TIMEOUT', 1800);
		
		// Defaults
		$tm_now				= time();

		$last_access			= $tm_now;
		$page_views			= 0;
		$total_sessions			= 0;
		$unique_user_id			= null;
		$start				= $tm_now;
		
		$initial_site_access		= $tm_now;
		
		$session_unique_user_id		= SessionManager::get(STATS_USER_ID_KEY);
		$session_page_views		= SessionManager::get(STATS_SESSION_PAGE_VIEWS);
		$session_total_sessions		= SessionManager::get(STATS_TOTAL_SESSIONS);
		$session_last_access		= SessionManager::get(STATS_LAST_ACCESS);
		
		$session_timeout		= ConfigurationManager::get('STATS_SESSION_TIMEOUT');

		$use_session = true;
		if ($use_session && $session_last_access) { // Use session values

			$total_sessions	= $session_total_sessions;
			$page_views	= $session_page_views;
			
			$time_lapse = (time()-$session_last_access);
			
			if ($time_lapse > $session_timeout) { // Keep
				#$create_new_session = false;
				$total_sessions += 1;
			}
			
		} else { // Otherwise try cookie values
			
			$total_sessions = 1;
			
			if ($previous_cookie = SessionManager::getCookie(COOKIE_STAT_STORAGE)) {
				
				$cookie_parts = explode('.', $previous_cookie);
				if (count($cookie_parts) >= 2) {
					
					$cookie_stat_version = $cookie_parts[1];
					switch ($cookie_stat_version) {
						case 1: // Version #1
							/**
							 * $cookie_parts[]
							 * 0: Version:
							 * 1: Unique User Id
							 * 2: Total Sessions
							 * 3: Last Access
							 * 4: Session Page Views
							 * 5: Check value
							 */
							if (count($cookie_parts) == 6) {
								
								list($check_value, $cookie_stat_version, $cookie_unique_user_id, $cookie_total_sessions, $cookie_last_access, $cookie_page_views) = $cookie_parts;
								
								// Validate
								$compare_check_value = md5($cookie_stat_version . $cookie_unique_user_id . $cookie_total_sessions . $cookie_last_access . $cookie_page_views);
								
								if ($check_value == $compare_check_value) {
									/*
									echo 'Version: ' . $cookie_stat_version . '<br />';
									echo 'Unique User Id: ' . $cookie_unique_user_id . '<br />';
									echo 'Total Sessions: ' . $cookie_total_sessions . '<br />';
									echo 'Cookie Last Access: ' . $cookie_last_access . '<br />';
									echo 'Time Lapsed: ' . (time()-$cookie_last_access) . '<br />';
									echo 'Page Views: ' . $cookie_page_views . '<br />';
									echo 'Check Value: ' . $check_value . '<br />';
									echo 'Check Value: ' . $compare_check_value . '<br />';
									*/
									$time_lapse = $tm_now - $cookie_last_access;
									
									$unique_user_id		= $cookie_unique_user_id;
									$total_sessions		= $cookie_total_sessions;
									$page_views		= $cookie_page_views;
									
									if ($time_lapse > $session_timeout) { // Create new session counters
										$total_sessions ++;
										$page_views = 0; // Reset (increased below)
									}
									
								}
								
							}
							break;
					}
				}
			}
			//$total_sessions += 1;
		}
		
		$page_views += 1; // Increment number of viewed pages by 1
		$unique_user_id	= 123456789;
		
		
		SessionManager::set(STATS_USER_ID_KEY, $unique_user_id);
		$version = 1;
		$cookie_string = $version . '.' . $unique_user_id . '.' . $total_sessions . '.' . $last_access . '.' . $page_views;
		$cookie_string = md5($version . $unique_user_id . $total_sessions . $last_access . $page_views) . '.' . $cookie_string;
		#$cookie_verification = md5(ConfigurationManager::get('SITE_KEY') . $unique_user_id) . '.' . md5(ConfigurationManager::get('SITE_KEY') . $cookie_string);
		
		SessionManager::setCookie(COOKIE_STAT_STORAGE, $cookie_string);
		#SessionManager::setCookie(COOKIE_STAT_VERIFICATION, $cookie_verification);
		
		// Reset session variables
		SessionManager::set(STATS_USER_ID_KEY, $unique_user_id);
		SessionManager::set(STATS_SESSION_PAGE_VIEWS, $page_views);
		SessionManager::set(STATS_TOTAL_SESSIONS, $total_sessions);
		SessionManager::set(STATS_LAST_ACCESS, $tm_now);
		
#		if (!$last_access || ($last_access && 
		
		/**
		 * Check whether to create a new session
		 **/
		$create_new_session = true;
		if ($last_access) { // Existing session
			
			$time_lapse = (time()-$last_access);
			
			if ($time_lapse < $session_timeout) { // Keep
				$create_new_session = false;
			}
			
		}
		
		
		SessionManager::set('ps_last_access', time());
		
		#echo 'createStatForCurrentUser: ' . time();
		#SessionManager::setCookie('firstVisit
		
		return PageStatLogic::save($stat);
	
	}
	public static function save($page_log_struct) {
		FrameworkManager::loadLogic('uuid');
		$page_log_dao = new PageStatDAO();
		if (empty($page_log_struct->id)) {
			$page_log_struct->id = str_replace('-', '', UuidLogic::v4());
			$page_log_dao->setForceInsert(true);
		}
		return $page_log_dao->save($page_log_struct);
	}
}

?>