<?php
// \Change\Configuration\Configuration::setDefineArray PART //
$this->setDefineArray(array (
	'TEST_1' => 4,
	'TEST_2' => 'INFO',
));

// \Change\Configuration\Configuration::setConfigArray PART //
$this->setConfigArray(array (
	'general' =>
	array (
		'projectName' => 'RBS CHANGE 4.0',
		'server-ip' => '127.0.0.1',
		'phase' => 'development',
	),
	'logging' =>
	array (
		'level' => 'WARN',
		'writers' =>
		array (
			'default' => 'stream',
		),
	),
));