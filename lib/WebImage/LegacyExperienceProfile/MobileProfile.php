<?php

namespace WebImage\LegacyExperienceProfile;

/**
 * General idea borrowed from http://detectmobilebrowsers.mobi/
 *
 * Published by Andy Moore - .mobi certified mobile web developer - http://andymoore.info/
 */
class MobileProfile extends AbstractProfile {

	/**
	 * Check if this profile is explicitly supported.  A profile might be supported implicitly via its inclusion in another IProfile via "addSupportedProfiles," but this method will check if this profile is actually supported in the current environment
	 * @return bool
	 */
	protected function checkIfSupported() {

		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$user_agent_lower = strtolower($user_agent);
		$accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';

		if (strpos($user_agent_lower, 'ipad') > 0) return false;

		$is_mobile_device = false;
		if (strpos($user_agent_lower, 'ipod') !== false || strpos($user_agent_lower, 'iphone')) {
			$is_mobile_device = true;
		} else if (strpos($user_agent_lower, 'android') !== false) {
			$is_mobile_device = true;
		} else if (strpos($user_agent_lower, 'opera mini') !== false) {
			$is_mobile_device = true;
		} else if (strpos($user_agent_lower, 'blackberry') !== false) {
			$is_mobile_device = true;
		} else if (preg_match('/(pre\/|palm os|palm|hiptop|avantgo|fennec|plucker|xiino|blazer|elaine)/', $user_agent_lower)) {
			$is_mobile_device = true;
		} else if (preg_match('/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)/', $user_agent_lower)) {
			$is_mobile_device = true;
		} else if (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/', $user_agent_lower)) {
			$is_mobile_device = true;
		} else if ((strpos($accept, 'text/vnd.wap.wml') > 0) || (strpos($accept, 'application/vnd.wap.xhtml+xml') > 0)) {// is the device showing signs of support for text/vnd.wap.wml or application/vnd.wap.xhtml+xml
			$is_mobile_device = true;
		} else if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) { // is the device giving us a HTTP_X_WAP_PROFILE or HTTP_PROFILE header - only mobile devices would do this
			$is_mobile_device = true;
		} else {
			$user_agent_4 = substr($user_agent_lower, 0, 4);
			if (in_array($user_agent_4,
				array(
					'1207', '3gso', '4thp', '501i', '502i', '503i', '504i', '505i', '506i', '6310',
					'6590', '770s', '802s', 'a wa', 'acer', 'acs-', 'airn', 'alav', 'asus', 'attw',
					'au-m', 'aur ', 'aus ', 'abac', 'acoo', 'aiko', 'alco', 'alca', 'amoi', 'anex',
					'anny', 'anyw', 'aptu', 'arch', 'argo', 'bell', 'bird', 'bw-n', 'bw-u', 'beck',
					'benq', 'bilb', 'blac', 'c55/', 'cdm-', 'chtm', 'capi', 'cond', 'craw', 'dall',
					'dbte', 'dc-s', 'dica', 'ds-d', 'ds12', 'dait', 'devi', 'dmob', 'doco', 'dopo',
					'el49', 'erk0', 'esl8', 'ez40', 'ez60', 'ez70', 'ezos', 'ezze', 'elai', 'emul',
					'eric', 'ezwa', 'fake', 'fly-', 'fly_', 'g-mo', 'g1 u', 'g560', 'gf-5', 'grun',
					'gene', 'go.w', 'good', 'grad', 'hcit', 'hd-m', 'hd-p', 'hd-t', 'hei-', 'hp i',
					'hpip', 'hs-c', 'htc ', 'htc-', 'htca', 'htcg', 'htcp', 'htcs', 'htct', 'htc_',
					'haie', 'hita', 'huaw', 'hutc', 'i-20', 'i-go', 'i-ma', 'i230', 'iac', 'iac-',
					'iac/', 'ig01', 'im1k', 'inno', 'iris', 'jata', 'java', 'kddi', 'kgt', 'kgt/',
					'kpt ', 'kwc-', 'klon', 'lexi', 'lg g', 'lg-a', 'lg-b', 'lg-c', 'lg-d', 'lg-f',
					'lg-g', 'lg-k', 'lg-l', 'lg-m', 'lg-o', 'lg-p', 'lg-s', 'lg-t', 'lg-u', 'lg-w',
					'lg/k', 'lg/l', 'lg/u', 'lg50', 'lg54', 'lge-', 'lge/', 'lynx', 'leno', 'm1-w',
					'm3ga', 'm50/', 'maui', 'mc01', 'mc21', 'mcca', 'medi', 'meri', 'mio8', 'mioa',
					'mo01', 'mo02', 'mode', 'modo', 'mot ', 'mot-', 'mt50', 'mtp1', 'mtv ', 'mate',
					'maxo', 'merc', 'mits', 'mobi', 'motv', 'mozz', 'n100', 'n101', 'n102', 'n202',
					'n203', 'n300', 'n302', 'n500', 'n502', 'n505', 'n700', 'n701', 'n710', 'nec-',
					'nem-', 'newg', 'neon', 'netf', 'noki', 'nzph', 'o2 x', 'o2-x', 'opwv', 'owg1',
					'opti', 'oran', 'p800', 'pand', 'pg-1', 'pg-2', 'pg-3', 'pg-6', 'pg-8', 'pg-c',
					'pg13', 'phil', 'pn-2', 'pt-g', 'palm', 'pana', 'pire', 'pock', 'pose', 'psio',
					'qa-a', 'qc-2', 'qc-3', 'qc-5', 'qc-7', 'qc07', 'qc12', 'qc21', 'qc32', 'qc60',
					'qci-', 'qwap', 'qtek', 'r380', 'r600', 'raks', 'rim9', 'rove', 's55/', 'sage',
					'sams', 'sc01', 'sch-', 'scp-', 'sdk/', 'se47', 'sec-', 'sec0', 'sec1', 'semc',
					'sgh-', 'shar', 'sie-', 'sk-0', 'sl45', 'slid', 'smb3', 'smt5', 'sp01', 'sph-',
					'spv ', 'spv-', 'sy01', 'samm', 'sany', 'sava', 'scoo', 'send', 'siem', 'smar',
					'smit', 'soft', 'sony', 't-mo', 't218', 't250', 't600', 't610', 't618', 'tcl-',
					'tdg-', 'telm', 'tim-', 'ts70', 'tsm-', 'tsm3', 'tsm5', 'tx-9', 'tagt', 'talk',
					'teli', 'topl', 'hiba', 'up.b', 'upg1', 'utst', 'v400', 'v750', 'veri', 'vk-v',
					'vk40', 'vk50', 'vk52', 'vk53', 'vm40', 'vx98', 'virg', 'vite', 'voda', 'vulc',
					'w3c ', 'w3c-', 'wapj', 'wapp', 'wapu', 'wapm', 'wig ', 'wapi', 'wapr', 'wapv',
					'wapy', 'wapa', 'waps', 'wapt', 'winc', 'winw', 'wonu', 'x700', 'xda2', 'xdag',
					'yas-', 'your', 'zte-', 'zeto', 'acs-', 'alav', 'alca', 'amoi', 'aste', 'audi',
					'avan', 'benq', 'bird', 'blac', 'blaz', 'brew', 'brvw', 'bumb', 'ccwa', 'cell',
					'cldc', 'cmd-', 'dang', 'doco', 'eml2', 'eric', 'fetc', 'hipt', 'http', 'ibro',
					'idea', 'ikom', 'inno', 'ipaq', 'jbro', 'jemu', 'java', 'jigs', 'kddi', 'keji',
					'kyoc', 'kyok', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-', 'libw', 'm-cr', 'maui',
					'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'mywa', 'nec-',
					'newt', 'nok6', 'noki', 'o2im', 'opwv', 'palm', 'pana', 'pant', 'pdxg', 'phil',
					'play', 'pluc', 'port', 'prox', 'qtek', 'qwap', 'rozo', 'sage', 'sama', 'sams',
					'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-', 'siem', 'smal',
					'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh', 'treo', 'tsm-',
					'upg1', 'upsi', 'vk-v', 'voda', 'vx52', 'vx53', 'vx60', 'vx61', 'vx70', 'vx80',
					'vx81', 'vx83', 'vx85', 'wap-', 'wapa', 'wapi', 'wapp', 'wapr', 'webc', 'whit',
					'winw', 'wmlb', 'xda-'
				)
			)) { // check against a list of trimmed user agents to see if we find a match
				$is_mobile_device = true;
			}
		}
		return $is_mobile_device;
	}
}
