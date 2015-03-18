#!/usr/local/php5/bin/php
<?php

require(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'config.php');
require(DIR_FS_CLI_LIB . 'argumentparser.php');
require(FILE_FRAMEWORK);

$args = new ArgumentParser($argv);

if (!$args->isFlagSet('config-file') || !$args->isFlagSet('table-prefix')) {
	die('Error: Missing -config-file and -table-prefix' . PHP_EOL);
}

$config_path = $args->getFlag('config-file');
$config_dir = dirname($config_path) . DIRECTORY_SEPARATOR;

if (file_exists($config_dir)) {
	if (!is_writable($config_dir)) die("Error: $config_dir is not writable\n");
} else {
	if (!@mkdir($config_dir, 0777, true)) die("Error: Config directory $config_dir does not exist\n");
	@chmod($config_dir, 0777);
}

if (substr($config_path, -11) != '/config.xml') die("Error: -config-file value must include config.xml\n");

FrameworkManager::init(FRAMEWORK_MODE_CLI);
FrameworkManager::loadLibrary('xml.xml');

$xml_config = new CWI_XML_Traversal('config', null, array('version'=>1));
$xml_database = new CWI_XML_Traversal('database');
$xml_tables = new CWI_XML_Traversal('tables', null, array('prefix'=>$args->getFlag('table-prefix')));

$xml_database->addChild($xml_tables);
$xml_config->addChild($xml_database);

$config_contents	= $xml_config->render();

if (file_put_contents($config_path, $config_contents)) {
	echo "Success\n";
} else {
	echo "Error: file_put_contents returned false\n";
}


?>