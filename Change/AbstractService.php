<?php
namespace Change;

abstract class AbstractService extends AbstractSingleton
{
	/**
	 * @return \Change\Db\Provider
	 */
	protected function getDbProvider()
	{
		return \Change\Db\Provider::getInstance();
	}
	
	/**
	 * @return \Change\Application\Configuration
	 */
	protected function getAppConfiguration()
	{
		return \Change\Application::getInstance()->getConfiguration();
	}
	
	/**
	 * @return \Change\Application\LoggingManager
	 */
	protected function getLoggingManager()
	{
		return \Change\Application\LoggingManager::getInstance();
	}
	
	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{	
		return \Change\I18n\I18nManager::getInstance();
	}
} 