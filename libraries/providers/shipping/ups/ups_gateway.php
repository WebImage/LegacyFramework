<?php

// Load logic for shipping options
FrameworkManager::loadLogic('shippingoption');
FrameworkManager::loadLogic('shippingoptionparameter');

// Load logic for shipping methods (which are children of shipping option)
FrameworkManager::loadLogic('shippingmethod');
FrameworkManager::loadLogic('shippingmethodparameter');

// Load logic for access sku packages
FrameworkManager::loadLogic('package');

// Load logic for warehouess / offices
FrameworkManager::loadLogic('office');

// Load logic for making remote calls to UPS
FrameworkManager::loadLogic('remoterequest');

/**
 * REQUIRED SHIPPING OPTION PARAMETERS
 * api_url:
 * 	https://wwwcie.ups.com/ups.app/xml/Rate (development)
 *	(production)
 * license_number
 * user_id
 * password
 *
 * REQUIRED SHIPPING METHOD PARAMETERS
 *
 * service_code (integer):
 *	1	= UPS Next Day Air
 *	2	= UPS Second Day Air
 *	3	= UPS Ground
 *	7	= UPS Worldwide Express
 *	8	= UPS Worldwide Expedited
 *	11	= UPS Standard
 *	12	= UPS Three-Day Select
 *	14	= UPS Next Day Air  Early A.M.
 *	54	= UPS Worldwide Express PlusSM Shipments Originating in United States
 *	59	= UPS Second Day Air A.M.
 *	65	= UPS Saver Shipments
 * 
 */
class UPSGateway extends IShippingGateway {
	function getRates($shipping_method_id, $shipment, &$message) {
		$total_charges = 0;
		
		$destination_address	= $shipment->getDestinationAddress();
		$items			= $shipment->getItems();
		
		$list = new Collection();
		
		if (!$method = ShippingMethodLogic::getShippingMethodById($shipping_method_id)) {
			$message = 'The shipping method could not be loaded.';
			return null;
		}
		
		// Get options/parameters for the shipping option
		$option_parameters = new Dictionary();
		$shipping_option_parameters = ShippingOptionParameterLogic::getShippingOptionParametersByShippingOptionId($method->shipping_option_id);
		while ($parameter = $shipping_option_parameters->getNext()) {
			$option_parameters->set($parameter->parameter, $parameter->value);
		}
		
		$method_parameters = new Dictionary();
		$shipping_method_parameters = ShippingMethodParameterLogic::getShippingMethodParametersByShippingMethodId($method->id);
		
		while ($parameter = $shipping_method_parameters->getNext()) {
			$method_parameters->set($parameter->parameter, $parameter->value);
		}
		
		#$ups_api_url = 'https://wwwcie.ups.com/ups.app/xml/Rate';
		if (!$ups_api_url = $option_parameters->get('api_url')) {
			$message = 'shipping options missing api_url';
			return null;
		}

		/**
		 * Build XML Access Request
		 */
		$access_request = new CWI_XML_Traversal('AccessRequest', null, array('xml:lang'=>'en-US'));
		
			$access_license_number	= new CWI_XML_Traversal('AccessLicenseNumber', $option_parameters->get('license_number'));
			$user_id		= new CWI_XML_Traversal('UserId', $option_parameters->get('user_id'));
			$password		= new CWI_XML_Traversal('Password', $option_parameters->get('password'));
			
			$access_request->addChild($access_license_number);
			$access_request->addChild($user_id);
			$access_request->addChild($password);
		
		$xml_access_request = '<?xml version="1.0"?>' . "\n" . $access_request->render();
		
		foreach($items as $item) {
			$sku = $item->getSku();
			$weight = $item->getQuantity() * $sku->getWeight();
			$package_struct = PackageLogic::getPackageById($sku->getPackageId());
			$warehouse_struct = OfficeLogic::getOfficeById($sku->getWarehouseId());

			/**
			 * Build XML rate request
			 */
			$rating_service_selection_request = new CWI_XML_Traversal('RatingServiceSelectionRequest');
			
				$request = new CWI_XML_Traversal('Request');
					/*
					$transaction_reference = new CWI_XML_Traversal('TransactionReference');
					
						$customer_context	= new CWI_XML_Traversal('CustomerContext', 'Rating and Service');
						$xpci_version 		= new CWI_XML_Traversal('XpciVersion', '1.0');
						
						$transaction_reference->addChild($customer_context);
						$transaction_reference->addChild($xpci_version);
					*/
					$request_action = new CWI_XML_Traversal('RequestAction', 'Rate');
					#$request_option = new CWI_XML_Traversal('RequestOption', 'Rate');
					
					$request->addChild($request_action);
					#$request->addChild($request_option);
				
				/*
				$pickup_type		= new CWI_XML_Traversal('PickupType');
				
					$code		= new CWI_XML_Traversal('Code', '01');
					$description	= new CWI_XML_Traversal('description', 'Daily Pickup');
					
					$pickup_type->addChild($code);
					$pickup_type->addChild($description);
				*/
				
				$shipment = new CWI_XML_Traversal('Shipment');
				
					$description = new CWI_XML_Traversal('Description', 'Rate Shopping - Domestic');
					
					$shipper = new CWI_XML_Traversal('Shipper');
						
						#$shipper_number = new CWI_XML_Traversal('ShipperNumber', 'ISGB01');
						
						$address = new CWI_XML_Traversal('Address');

							$address1		= new CWI_XML_Traversal('AddressLine1', $warehouse_struct->address1);
							$address2		= new CWI_XML_Traversal('AddressLine2', $warehouse_struct->address2);
							#$address3		= new CWI_XML_Traversal('AddressLine3', $warehouse_struct->address3);
							
							$address->addChild($address1);
							$address->addChild($address2);
							#$address->addChild($address3);
							
							$city			= new CWI_XML_Traversal('City', $warehouse_struct->city);
							$state_province_code	= new CWI_XML_Traversal('StateProvinceCode', $warehouse_struct->state_province_abbrev);
							$postal_code		= new CWI_XML_Traversal('PostalCode', $warehouse_struct->zip);
							$country_code		= new CWI_XML_Traversal('CountryCode', $warehouse_struct->country_iso_code_2);
							
							$address->addChild($city);
							$address->addChild($state_province_code);
							$address->addChild($postal_code);
							$address->addChild($country_code);

						#$shipper->addChild($shipper_number);
						$shipper->addChild($address);			

					$ship_to	= new CWI_XML_Traversal('ShipTo');
					
						#$company_name		= new CWI_XML_Traversal('CompanyName', 'nanana');
						#$attention_name		= new CWI_XML_Traversal('AttentionName', 'nanana');
						#$phone_number		= new CWI_XML_Traversal('PhoneNumber', '7777777777');
						$address		= new CWI_XML_Traversal('Address');

							$address1		= new CWI_XML_Traversal('AddressLine1', $destination_address->address1);
							$address2		= new CWI_XML_Traversal('AddressLine2', $destination_address->address2);
							#$address3		= new CWI_XML_Traversal('AddressLine3');
							$city			= new CWI_XML_Traversal('City', $destination_address->city);
							$state_province_code	= new CWI_XML_Traversal('StateProvinceCode', $destination_address->state_province_abbrev);
							$postal_code		= new CWI_XML_Traversal('PostalCode', $destination_address->zip);
							$country_code		= new CWI_XML_Traversal('CountryCode', $destination_address->country_iso_code_2);
							
							$address->addChild($address1);
							$address->addChild($address2);
							#$address->addChild($address3);
							$address->addChild($city);
							$address->addChild($state_province_code);
							$address->addChild($postal_code);
							$address->addChild($country_code);

						#$ship_to->addChild($company_name);
						#$ship_to->addChild($attention_name);
						#$ship_to->addChild($phone_number);
						$ship_to->addChild($address);
					/*
					$ship_from	= new CWI_XML_Traversal('ShipFrom');
					
						$company_name		= new CWI_XML_Traversal('CompanyName', 'nanana');
						$attention_name		= new CWI_XML_Traversal('AttentionName', 'nanana');
						$phone_number		= new CWI_XML_Traversal('PhoneNumber', '7777777777');
						$address		= new CWI_XML_Traversal('Address');
						
							$address1		= new CWI_XML_Traversal('AddressLine1');
							$address2		= new CWI_XML_Traversal('AddressLine2');
							$address3		= new CWI_XML_Traversal('AddressLine3');
							$city			= new CWI_XML_Traversal('City');
							$state_province_code	= new CWI_XML_Traversal('StateProvinceCode', 'CA');
							$postal_code		= new CWI_XML_Traversal('PostalCode', '92101');
							$country_code		= new CWI_XML_Traversal('CountryCode', 'US');
							
							$address->addChild($address1);
							$address->addChild($address2);
							$address->addChild($address3);
							$address->addChild($city);
							$address->addChild($state_province_code);
							$address->addChild($postal_code);
							$address->addChild($country_code)
						
						$ship_from->addChild($company_name);
						$ship_from->addChild($attention_name);
						$ship_from->addChild($phone_number);
						$ship_from->addChild($address);
					*/
					
					$service = new CWI_XML_Traversal('Service');

						$code = new CWI_XML_Traversal('Code', $method_parameters->get('service_code'));
						
						$service->addChild($code);
					
					$package = new CWI_XML_Traversal('Package');
					
						$packaging_type = new CWI_XML_Traversal('PackagingType');
						
							$code		= new CWI_XML_Traversal('Code', '02');
							#$description	= new CWI_XML_Traversal('Description');
							
							$packaging_type->addChild($code);
							#$packaging_type->addChild($description);
					
					/*	
						$description = new CWI_XML_Traversal('Description', 'Rate');
					*/

						$package_weight = new CWI_XML_Traversal('PackageWeight');
						
							$unit_of_measurement = new CWI_XML_Traversal('UnitOfMeasurement');
							
								$code = new CWI_XML_Traversal('Code', 'LBS');
								
								$unit_of_measurement->addChild($code);
								
							$weight = new CWI_XML_Traversal('Weight', $weight);
							
							$package_weight->addChild($unit_of_measurement);
							$package_weight->addChild($weight);
						
						$dimensions = new CWI_XML_Traversal('Dimensions');
						
							$unit_of_measurement = new CWI_XML_Traversal('UnitOfMeasurement');
							
								$code		= new CWI_XML_Traversal('Code', 'IN');
								
								$unit_of_measurement->addChild($code);
								
							$length		= new CWI_XML_Traversal('Length', $package_struct->length);
							$width		= new CWI_XML_Traversal('Width', $package_struct->width);
							$height		= new CWI_XML_Traversal('Height', $package_struct->height);
							
							$dimensions->addChild($unit_of_measurement);
							$dimensions->addChild($length);
							$dimensions->addChild($width);
							$dimensions->addChild($height);

						$package->addChild($packaging_type);
						#$package->addChild($description);
						$package->addChild($package_weight);
						$package->addChild($dimensions);
					
					//$shipment_service_options = new CWI_XML_Traversal('ShipmentServiceOptions');

					$shipment->addChild($description);
					$shipment->addChild($shipper);
					$shipment->addChild($ship_to);
					//$shipment->addChild($ship_from);
					$shipment->addChild($service);
					$shipment->addChild($package);

					//$shipment->addChild($shipment_service_options);
					
				$rating_service_selection_request->addChild($request);
				//$rating_service_selection_request->addChild($pickup_type);
			
				$rating_service_selection_request->addChild($shipment);
				
			// Join all parts of request into a single string

			$xml_rate_request = $xml_access_request . '<?xml version="1.0"?>' . "\n" . $rating_service_selection_request->render();

			if ($xml_ups_response = RemoteRequestLogic::getXmlResponse($ups_api_url, $xml_rate_request, 'POST')) {
				if ($xml_response = $xml_ups_response->getPathSingle('/RatingServiceSelectionResponse/Response')) {
					if ($response_status_code = $xml_response->getPathSingle('Error')) {
						$message = 'There was an error retrieving a quote from UPS';
						return null;
					} else {
						/*
						RatingServiceSelectionResponse
						   Response
						      ResponseStatusCode
						      ResponseStatusDescription
						   RatedShipment
						      Service
						         Code
						      RatedShipmentWarning
						      RatedShipmentWarning
						      BillingWeight
						         UnitOfMeasurement
							Code
						         Weight
						      TransportationCharges
						         CurrencyCode
						         MonetaryValue
						      ServiceOptionsCharges
						         CurrencyCode
						         MonetaryValue
						      TotalCharges
						         CurrencyCode
						         MonetaryValue
						      GuaranteedDaysToDelivery
						      ScheduledDeliveryTime
						      RatedPackage
						         TransportationCharges
							CurrencyCode
							MonetaryValue
						         ServiceOptionsCharges
							CurrencyCode
							MonetaryValue
						         TotalCharges
							CurrencyCode
							MonetaryValue
						         Weight
						         BillingWeight
							UnitOfMeasurement
							   Code
							Weight
						*/
						if ($charges = $xml_ups_response->getPathSingle('/RatingServiceSelectionResponse/RatedShipment/TotalCharges/MonetaryValue')) {
							$total_charges += trim($charges->getData());
						} else {
							$message = 'UPS returned an estimate, but there was a problem with the monetary value returned.';
							return null;
						}
					}
				}
			} else {
				$message = 'Could not obtain shipping quote.';
				return false;
			}
		}

#$rating_service_selection_request->render()

/* ******************************** */

#echo 'RENDER: ' . htmlentities($rating_service_selection_request->render());exit;

		$list->add( new ShippingPackageOption( new ShippingMethodIdentifier($method->name), $method->friendly_name, $total_charges) );
		
		return $list;
	}
}

?>