<?php
namespace Change\Application;

/**
 * @name \Change\Application\ApplicationServices
 */
class ApplicationServices extends \Zend\Di\Di
{
	public function __construct(\Change\Application $application)
	{
		$dl = new \Zend\Di\DefinitionList(array());

		$this->registerEventManager($dl);
		$this->registerLogging($dl);
		$this->registerDbProvider($dl);
		$this->registerTransactionManager($dl);
		$this->registerI18nManager($dl);

		parent::__construct($dl);

		$im = $this->instanceManager();

		$im->setParameters('Change\Events\EventManager', array('configuration' => $application->getConfiguration()));
		$im->setParameters('Change\Application\PackageManager', array('workspace' => $application->getWorkspace()));
		$im->setParameters('Change\I18n\I18nManager', array('configuration' => $application->getConfiguration(), 'workspace' => $application->getWorkspace()));
		$im->setParameters('Change\Db\DbProvider', array('config' => $application->getConfiguration()));
		$im->setParameters('Change\Logging\Logging', array('config' => $application->getConfiguration(), 'workspace' => $application->getWorkspace()));
		$im->setParameters('Change\Transaction\TransactionManager', array('configuration' => $application->getConfiguration()));

		$im->setInjections('Change\Db\DbProvider', array('Change\Logging\Logging'));
		$im->setInjections('Change\Transaction\TransactionManager', array('Change\Db\DbProvider'));
		$im->setInjections('Change\I18n\I18nManager',  array('Change\Logging\Logging'));
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerEventManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Events\EventManager');
		$cl->setInstantiator('__construct')
			->addMethod('__construct', true)
			->addMethodParameter('__construct', 'configuration', array('type' => 'Change\Configuration\Configuration', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerLogging($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Logging\Logging');
		$cl->setInstantiator('__construct')
			->addMethod('setConfiguration', true)
				->addMethodParameter('setConfiguration', 'config', array('type' => 'Change\Configuration\Configuration', 'required' => true))
			->addMethod('setWorkspace', true)
				->addMethodParameter('setWorkspace', 'workspace', array('type' => 'Change\Workspace', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerDbProvider($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Db\DbProvider');
		$cl->setInstantiator(array('Change\Db\DbProvider', 'newInstance'))
			->addMethod('newInstance', true)
			->addMethodParameter('newInstance', 'config', array('type' => 'Change\Configuration\Configuration', 'required' => true))
			->addMethodParameter('newInstance', 'logging', array('type' => 'Change\Logging\Logging', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerTransactionManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Transaction\TransactionManager');
		$cl->setInstantiator('__construct')
			->addMethod('__construct', true)
			->addMethodParameter('__construct', 'provider', array('type' => 'Change\Db\DbProvider', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerI18nManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\I18n\I18nManager');
		$cl->setInstantiator('__construct')
			->addMethod('__construct', true)
			->addMethod('setConfiguration')
				->addMethodParameter('setConfiguration', 'configuration', array('type' => 'Change\Configuration\Configuration', 'required' => true))
			->addMethod('setWorkspace')
				->addMethodParameter('setWorkspace', 'workspace', array('type' => 'Change\Workspace', 'required' => true))
			->addMethod('setLogging')
				->addMethodParameter('setLogging', 'logging', array('type' => 'Change\Logging\Logging', 'required' => true));
		$dl->addDefinition($cl);
	}

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
	 * @return \Change\Transaction\TransactionManager
	 */
	public function getTransactionManager()
	{
		return $this->get('Change\Transaction\TransactionManager');
	}

	/**
	 * @return \Change\Application\PackageManager
	 */
	public function getPackageManager()
	{
		return $this->get('Change\Application\PackageManager');
	}

	/**
	 * @return \Change\Events\EventManager
	 */
	public function getEventManager()
	{
		return $this->get('Change\Events\EventManager');
	}
}