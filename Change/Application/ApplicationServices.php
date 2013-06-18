<?php
namespace Change\Application;

/**
 * @api
 * @name \Change\Application\ApplicationServices
 */
class ApplicationServices extends \Zend\Di\Di
{
	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @param \Change\Application $application
	 */
	public function __construct(\Change\Application $application)
	{
		$this->application = $application;

		$dl = new \Zend\Di\DefinitionList(array());

		$this->registerLogging($dl);

		$this->registerTransactionManager($dl);
		$this->registerDbProvider($dl);
		$this->registerI18nManager($dl);
		$this->registerPluginManager($dl);
		$this->registerStorageManager($dl);

		parent::__construct($dl);

		$im = $this->instanceManager();

		$im->setParameters('Change\Logging\Logging', array(
			'config' => $application->getConfiguration(),
			'workspace' => $application->getWorkspace()));

		$im->setParameters('Change\Transaction\TransactionManager',
			array('sharedEventManager' => $application->getSharedEventManager()));

		$im->setParameters('Change\Db\DbProvider', array('config' => $application->getConfiguration()));

		$im->setParameters('Change\I18n\I18nManager', array(
			'configuration' => $application->getConfiguration(),
			'workspace' => $application->getWorkspace(),
			'sharedEventManager' => $application->getSharedEventManager()));

		$im->setParameters('Change\Plugins\PluginManager', array(
			'workspace' => $application->getWorkspace(),
			'sharedEventManager' => $application->getSharedEventManager()));

		$im->setInjections('Change\Storage\StorageManager', array('Change\Db\DbProvider'));
		$im->setParameters('Change\Storage\StorageManager', array(
			'configuration' => $application->getConfiguration(),
			'workspace' => $application->getWorkspace()));
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerLogging($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Logging\Logging');
		$cl->setInstantiator('__construct')
			->addMethod('setConfiguration', true)
			->addMethodParameter('setConfiguration', 'config',
				array('type' => 'Change\Configuration\Configuration', 'required' => true))
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
			->addMethod('newInstance')
			->addMethodParameter('newInstance',
				'config', array('type' => 'Change\Configuration\Configuration', 'required' => true))

			->addMethod('setLogging', true)
			->addMethodParameter('setLogging',
				'logging', array('type' => 'Change\Logging\Logging', 'required' => true))

			->addMethod('setTransactionManager', true)
			->addMethodParameter('setTransactionManager',
				'transactionManager', array('type' => 'Change\Transaction\TransactionManager', 'required' => true));
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
		->addMethod('setSharedEventManager')
		->addMethodParameter('setSharedEventManager', 'sharedEventManager',
			array('type' => 'Change\Events\SharedEventManager', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerI18nManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\I18n\I18nManager');
		$cl->setInstantiator('__construct')
			->addMethod('__construct')

			->addMethod('setConfiguration', true)
			->addMethodParameter('setConfiguration', 'configuration',
				array('type' => 'Change\Configuration\Configuration', 'required' => true))

			->addMethod('setWorkspace', true)
			->addMethodParameter('setWorkspace', 'workspace', array('type' => 'Change\Workspace', 'required' => true))

			->addMethod('setSharedEventManager', true)
			->addMethodParameter('setSharedEventManager', 'sharedEventManager',
				array('type' => 'Change\Events\SharedEventManager', 'required' => true))

			->addMethod('setLogging', true)
			->addMethodParameter('setLogging', 'logging', array('type' => 'Change\Logging\Logging', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerPluginManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Plugins\PluginManager');
		$cl->setInstantiator('__construct')
			->addMethod('__construct')

			->addMethod('setWorkspace', true)
			->addMethodParameter('setWorkspace', 'workspace',
				array('type' => 'Change\Workspace', 'required' => true))

			->addMethod('setDbProvider', true)
			->addMethodParameter('setDbProvider', 'dbProvider',
				array('type' => 'Change\Db\DbProvider', 'required' => true))

			->addMethod('setSharedEventManager', true)
			->addMethodParameter('setSharedEventManager', 'sharedEventManager',
				array('type' => 'Change\Events\SharedEventManager', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param \Zend\Di\DefinitionList $dl
	 */
	protected function registerStorageManager($dl)
	{
		$cl = new \Zend\Di\Definition\ClassDefinition('Change\Storage\StorageManager');
		$cl->setInstantiator('__construct')
			->addMethod('__construct', true)

			->addMethod('setConfiguration', true)
			->addMethodParameter('setConfiguration', 'configuration',
				array('type' => 'Change\Configuration\Configuration', 'required' => true))

			->addMethod('setWorkspace', true)
			->addMethodParameter('setWorkspace', 'workspace',
				array('type' => 'Change\Workspace', 'required' => true))

			->addMethod('setDbProvider', true)
			->addMethodParameter('setDbProvider', 'dbProvider',
				array('type' => 'Change\Db\DbProvider', 'required' => true))

			->addMethod('register', true);
		$dl->addDefinition($cl);
	}

	/**
	 * @api
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @api
	 * @return \Change\Db\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->get('Change\Db\DbProvider');
	}

	/**
	 * @api
	 * @return \Change\I18n\I18nManager
	 */
	public function getI18nManager()
	{
		return $this->get('Change\I18n\I18nManager');
	}

	/**
	 * @api
	 * @return \Change\Logging\Logging
	 */
	public function getLogging()
	{
		return $this->get('Change\Logging\Logging');
	}

	/**
	 * @api
	 * @return \Change\Transaction\TransactionManager
	 */
	public function getTransactionManager()
	{
		return $this->get('Change\Transaction\TransactionManager');
	}

	/**
	 * @api
	 * @return \Change\Plugins\PluginManager
	 */
	public function getPluginManager()
	{
		return $this->get('Change\Plugins\PluginManager');
	}

	/**
	 * @api
	 * @return \Change\Storage\StorageManager
	 */
	public function getStorageManager()
	{
		return $this->get('Change\Storage\StorageManager');
	}
}