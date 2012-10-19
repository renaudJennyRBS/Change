<?php

namespace Change\Application;

class ApplicationServices extends \Zend\Di\Di
{	
	/**
	 * @return \Change\Db\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->get('Change\Db\DbProvider');
	}
	
	/**
	 * @return \Change\I18n\I18nManager
	 */
	public function getI18nManager()
	{
		return $this->get('Change\I18n\I18nManager');
	}
	
	/**
	 * @return \Change\Logging\Logging
	 */
	public function getLogging()
	{
		return $this->get('Change\Logging\Logging');
	}
	
	/**
	 * @return \Change\Configuration\Configuration
	 */
	public function getConfiguration()
	{
		return $this->get('Change\Configuration\Configuration');
	}
	
	/**
	 * @return \Change\Workspace
	 */
	public function getWorkspace()
	{
		return $this->get('Change\Workspace');
	}

	/**
	 * @return \Change\Application\PackageManager
	 */
	public function getPackageManager()
	{
		return $this->get('Change\Application\PackageManager');
	}
}