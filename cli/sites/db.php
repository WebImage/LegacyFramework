<?php

require(dirname(__FILE__) . '/lib/config.php');

include(DIR_FS_CLI_LIB . 'tableformat.php');
include(DIR_FS_CLI_LIB . 'argumentparser.php');

$args = new ArgumentParser($argv);

$process_prefixes = array();
$model_package = 'core';

if ($args->isFlagSet('prefix')) {
	array_push($process_prefixes, $args->getFlag('prefix'));
} else {
	
}

if ($args->isFlagSet('package')) {
	$model_package = $args->getFlag('package');
	echo 'Using model package: ' . $model_package . "\n";
}

include(FILE_FRAMEWORK);
FrameworkManager::init(FRAMEWORK_MODE_CLI);

FrameworkManager::loadManager('sync');

echo "Syncing database models for " . count($process_prefixes) . " sites.  ";
if (count($process_prefixes) > 0) echo " This may take a minute...";
echo "\n";

foreach($process_prefixes as $process_prefix) {
	
	echo "Process tables with prefix: " . $process_prefix . "\n";
	
	DatabaseManager::setTablePrefix($process_prefix);

	$test_table_key = 'my_test_table';
	$test_table_name = $process_prefix . $test_table_key;

	echo 'Testing DatabaseManager::getTable(' . $test_table_key . ') - should output: ' . $test_table_name . "\n";
	echo '  DatabaseManager::getTable($table_key) returned: ' . DatabaseManager::getTable($test_table_key) . "\n";
	if (DatabaseManager::getTable($test_table_key) != $test_table_name) die('  Invalid test results.' . "\n");

	echo 'Database core sync operation results...' . "\n";
	$model_results = CWI_MANAGER_SyncManager::syncModelPackage($model_package);
	$rows = array();
	while ($model_result = $model_results->getNext()) {
		$rows[] = array(
			'Model' => $model_result->getModel()->getName(),
			'Table' => $model_result->getModel()->getTableName(), 
			'Result' => $model_result->getType()
		);
	}
	echo table_format_rows($rows);
}





?>