<?php

/**
 * CHANGE LOG
 * 05/04/2011	(Robert Jones) Added support for geoip_* functions
 **/

FrameworkManager::loadStruct('geolocation');

class GeoLocationLogic {
	
	/**
	 * @return bool
	 **/
	public static function hasGeoIPSupport() {
		
		// Check whether requisite functions and constants are defined
		$installed = (function_exists('geoip_db_avail') && function_exists('geoip_record_by_name') && ( defined('GEOIP_CITY_EDITION_REV0') || defined('GEOIP_CITY_EDITION_REV1') ));
		
		// Check that the city edition is installed
		$has_city_edition = $installed && (geoip_db_avail(GEOIP_CITY_EDITION_REV0) || geoip_db_avail(GEOIP_CITY_EDITION_REV1));
		
		return ($installed && $has_city_edition);
		
	}
	
	public static function getLatitudeLongitudeFromIpAddress($ip_address) {
		
		/**
		 * Use geoip functions, if available.  They are provided with data provided by MaxMind.com, using their API
		 **/
		
		$geoip_edition = 0;
		
		if (GeoLocationLogic::hasGeoIPSupport()) {
			
			// Set correct db edition
			if (geoip_db_avail(GEOIP_CITY_EDITION_REV1)) $geoip_edition = GEOIP_CITY_EDITION_REV1;
			else if (geoip_db_avail(GEOIP_CITY_EDITION_REV0)) $geoip_edition = GEOIP_CITY_EDITION_REV0;
			
			if ($geoip_edition != 0) {
	
				$city_info = geoip_record_by_name($ip_address);
				
				/** 
				 * $city_info = array(
				 *	[continent_code] => 
				 *	[country_code] => 
				 *	[country_code3] => 
				 *	[country_name] => 
				 *	[region] => 
				 *	[city] => 
				 *	[postal_code] => 
				 *	[latitude] => 
				 *	[longitude] => 
				 *	[dma_code] => 
				 *	[area_code] => 
				 **/

				$location = new GeoLocationStruct();
				
				if (isset($city_info['postal_code'])) $location->postal_code = $city_info['postal_code'];
				
				if (isset($city_info['latitude'])) $location->latitude = $city_info['latitude'];
				
				if (isset($city_info['longitude'])) $location->longitude = $city_info['longitude'];
				
				if (isset($city_info['city'])) $location->city_name = $city_info['city'];
				return $location;
				
			}

		}
		
		/**
		 * If we make it this far then we are falling back to the free location check provided by ipinfodb
		 **/
			
		$api_key = ConfigurationManager::get('IPINFODB_API_KEY');
		if (empty($api_key)) return false;
		
		//$url = 'http://ipinfodb.com/ip_query.php?ip=' . $ip_address;
		$url = 'http://api.ipinfodb.com/v2/ip_query.php?key=' . $api_key . '&ip=' . $ip_address . '&timezone=false';
		
		FrameworkManager::loadLogic('remoterequest');
		if ($xml_response = RemoteRequestLogic::getXmlResponse($url)) {
			/*
			$xml_text = '<Response> 
				  <Ip />
				  <Status />
				  <CountryCode />
				  <CountryName />
				  <RegionCode />
				  <RegionName />
				  <City />
				  <ZipPostalCode />
				  <Latitude />
				  <Longitude />
				  <Timezone />
				  <Gmtoffset />
				  <Dstoffset />
				</Response>';
			$xml_response = CWI_XML_Compile::compile($xml_text);
			*/
			$location = new GeoLocationStruct();
			$location->postal_code = trim($xml_response->getData('ZipPostalCode'));
			$location->latitude = trim($xml_response->getData('Latitude'));
			$location->longitude = trim($xml_response->getData('Longitude'));
			$location->city_name = trim($xml_response->getData('City'));
			
			return $location;
		} else return false;
	}
}

?>