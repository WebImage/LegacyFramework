<?php

FrameworkManager::loadLogic('site');
$crm_enabled = FrameworkManager::loadLogic('crm');
$max_length = 30;
$sites = SiteLogic::getAllSites();
while ($site = $sites->getNext()) {
	if (empty($site->company_id) || !$crm_enabled) {
		$site->company_name = 'N/A';
	} else if ($crm_enabled) {
		if ($company_struct = CrmLogic::getCompanyById($site->company_id)) {
			$company_name = $company_struct->name;
			if (strlen($company_name) > $max_length) $company_name = substr($company_name, 0, $max_length-3) . '...';
			$site->company_name = $company_name;
		} else {
			$site->company_name = 'N/A';
		}
	}
	#if (strlen($site->name) > $max_length) $site->name = substr($site->name, 0, $max_length-3) . '...';
	$sites->setAt($sites->getCurrentIndex(), $site);
}

$dg_sites = Page::getControlById('dg_sites');
$dg_sites->setData($sites);

?>