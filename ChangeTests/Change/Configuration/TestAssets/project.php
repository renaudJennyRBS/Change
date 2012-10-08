<?php
// \Change\Application\Configuration::setDefineArray PART //
$this->setDefineArray(array (
	'CHANGE_RELEASE' => 4,
	'LOGGING_LEVEL' => 'INFO',
));

// \Change\Application\Configuration::setConfigArray PART //
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