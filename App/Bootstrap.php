<?php

namespace App;

class Bootstrap
{
	public static function main(\Change\Application $app)
	{
		require_once PROJECT_HOME . "/framework/Framework.php";
		
		// TODO: generate "compatibility application services 
		
		// Load configuration
		$app->getConfiguration();
		if (!defined('FRAMEWORK_VERSION'))
		{
			define('FRAMEWORK_VERSION', CHANGE_VERSION);
		}
		
		\Framework::registerChangeAutoload();
		\Framework::registerConfiguredAutoloads();
		
		
		if ($app->inDevelopmentMode()) {error_reporting(E_ALL | E_STRICT);}
		
		ini_set('arg_separator.output', '&');
		ini_set('magic_quotes_runtime', 0);
		
		//\Change\Logging\Logging::getInstance()->registerErrorHandler();
		
		// Set the locale.
		$localResult = setlocale(LC_ALL, 'en_US.UTF-8');
		
		// Set GMT TimeZone
		date_default_timezone_set('GMT');		
	}
}