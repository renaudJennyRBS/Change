<?php
namespace Change\Services;

/**
* @name \Change\Services\ApplicationServices
*/
class ApplicationServices extends \Zend\Di\Di
{
	use ServicesCapableTrait;

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return array<alias => className>
	 */
	protected function loadInjectionClasses()
	{
		$classes = $this->application->getConfiguration('Change/Services');
		return is_array($classes) ? $classes : array();
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Events\EventManagerFactory $eventManagerFactory
	 */
	function __construct($application, $eventManagerFactory)
	{
		$this->setApplication($application);
		$configuration = $application->getConfiguration();
		$this->setEventManagerFactory($eventManagerFactory);

		$definitionList = new \Zend\Di\DefinitionList(array());

		//Logging : Configuration, Workspace
		$loggingClassName = $this->getInjectedClassName('Logging', 'Change\Logging\Logging');
		$classDefinition = $this->getConfigAndWorkspaceClassDefinition($loggingClassName);
		$definitionList->addDefinition($classDefinition);

		//DbProvider : Configuration, Workspace, EventManagerFactory, Logging
		$section = $configuration->getEntry('Change/Db/use', 'default');
		$dbProviderClassName = $configuration->getEntry('Change/Db/' . $section . '/dbprovider', 'Change\Db\Mysql\DbProvider');
		$classDefinition = $this->getConfigAndWorkspaceClassDefinition($dbProviderClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setLogging', true)
			->addMethodParameter('setLogging', 'logging', array('type' => 'Logging', 'required' => true));
		$definitionList->addDefinition($classDefinition);


		//TransactionManager : EventManagerFactory
		$transactionManagerClassName = $this->getInjectedClassName('TransactionManager', 'Change\Transaction\TransactionManager');
		$classDefinition = $this->getClassDefinition($transactionManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//I18nManager : Configuration, Workspace, EventManagerFactory, Logging, PluginManager
		$i18nManagerClassName = $this->getInjectedClassName('I18nManager', 'Change\I18n\I18nManager');
		$classDefinition = $this->getConfigAndWorkspaceClassDefinition($i18nManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setLogging', true)
			->addMethodParameter('setLogging', 'logging', array('type' => 'Logging', 'required' => true));
		$classDefinition->addMethod('setPluginManager', true)
			->addMethodParameter('setPluginManager', 'pluginManager', array('type' => 'PluginManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);


		//PluginManager : Workspace, EventManagerFactory, DbProvider
		$pluginManagerClassName = $this->getInjectedClassName('PluginManager', 'Change\Plugins\PluginManager');
		$classDefinition = $this->getClassDefinition($pluginManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition)
			->addWorkspaceClassDefinition($classDefinition);
		$classDefinition->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//StorageManager : Configuration, Workspace, DbProvider
		$storageManagerClassName = $this->getInjectedClassName('StorageManager', 'Change\Storage\StorageManager');
		$classDefinition = $this->getConfigAndWorkspaceClassDefinition($storageManagerClassName);
		$classDefinition->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//MailManager : Configuration, Logging
		$mailManagerClassName = $this->getInjectedClassName('MailManager', 'Change\Mail\MailManager');
		$classDefinition = $this->getClassDefinition($mailManagerClassName);
		$this->addConfigurationClassDefinition($classDefinition);
		$classDefinition->addMethod('setLogging', true)
			->addMethodParameter('setLogging', 'logging', array('type' => 'Logging', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//ModelManager : EventManagerFactory, Workspace, PluginManager
		$modelManagerClassName = $this->getInjectedClassName('ModelManager', 'Change\Documents\ModelManager');
		$classDefinition = $this->getClassDefinition($modelManagerClassName);
		$this->addWorkspaceClassDefinition($classDefinition);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setPluginManager', true)
			->addMethodParameter('setPluginManager', 'pluginManager', array('type' => 'PluginManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//DocumentManager : EventManagerFactory, Configuration, ModelManager, DbProvider, I18nManager, Logging
		$documentManagerClassName = $this->getInjectedClassName('DocumentManager', 'Change\Documents\DocumentManager');
		$classDefinition = $this->getClassDefinition($documentManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$this->addConfigurationClassDefinition($classDefinition);
		$classDefinition->addMethod('setModelManager', true)
				->addMethodParameter('setModelManager', 'modelManager', array('type' => 'ModelManager', 'required' => true))
			->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setI18nManager', true)
				->addMethodParameter('setI18nManager', 'i18nManager', array('type' => 'I18nManager', 'required' => true))
			->addMethod('setLogging', true)
				->addMethodParameter('setLogging', 'logging', array('type' => 'Logging', 'required' => true));
		$definitionList->addDefinition($classDefinition);


		//DocumentCodeManager : DbProvider, DocumentManager, TransactionManager
		$documentCodeManagerClassName = $this->getInjectedClassName('DocumentCodeManager', 'Change\Documents\DocumentCodeManager');
		$classDefinition = $this->getClassDefinition($documentCodeManagerClassName);
		$classDefinition->addMethod('setDocumentManager', true)
			->addMethodParameter('setDocumentManager', 'documentManager', array('type' => 'DocumentManager', 'required' => true))
			->addMethod('setDbProvider', true)
			->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
			->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//TreeManager : ModelManager, DocumentManager, DbProvider
		$treeManagerClassName = $this->getInjectedClassName('TreeManager', 'Change\Documents\TreeManager');
		$classDefinition = $this->getClassDefinition($treeManagerClassName);
		$classDefinition->addMethod('setModelManager', true)
				->addMethodParameter('setModelManager', 'modelManager', array('type' => 'ModelManager', 'required' => true))
			->addMethod('setDocumentManager', true)
				->addMethodParameter('setDocumentManager', 'documentManager', array('type' => 'DocumentManager', 'required' => true))
			->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//ConstraintsManager : I18nManager
		$constraintsManagerClassName = $this->getInjectedClassName('ConstraintsManager', 'Change\Documents\Constraints\ConstraintsManager');
		$classDefinition = $this->getClassDefinition($constraintsManagerClassName);
		$classDefinition->addMethod('setI18nManager', true)
			->addMethodParameter('setI18nManager', 'i18nManager', array('type' => 'I18nManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//CollectionManager : EventManagerFactory
		$collectionManagerClassName = $this->getInjectedClassName('CollectionManager', 'Change\Collection\CollectionManager');
		$classDefinition = $this->getClassDefinition($collectionManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//JobManager : EventManagerFactory, DbProvider, Logging
		$jobManagerClassName = $this->getInjectedClassName('CollectionManager', 'Change\Job\JobManager');
		$classDefinition = $this->getClassDefinition($jobManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition
			->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true))
			->addMethod('setLogging', true)
				->addMethodParameter('setLogging', 'logging', array('type' => 'Logging', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//BlockManager: EventManagerFactory, Configuration
		$blockManagerClassName = $this->getInjectedClassName('BlockManager', 'Change\Presentation\Blocks\BlockManager');
		$classDefinition = $this->getClassDefinition($blockManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$this->addConfigurationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//RichTextManager : EventManagerFactory
		$richTextManagerClassName = $this->getInjectedClassName('RichTextManager', 'Change\Presentation\RichText\RichTextManager');
		$classDefinition = $this->getClassDefinition($richTextManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//ThemeManager : EventManagerFactory, Configuration, Workspace
		$themeManagerClassName = $this->getInjectedClassName('ThemeManager', 'Change\Presentation\Themes\ThemeManager');
		$classDefinition = $this->getConfigAndWorkspaceClassDefinition($themeManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//TemplateManager : EventManagerFactory, Workspace, ThemeManager
		$templateManagerClassName = $this->getInjectedClassName('TemplateManager', 'Change\Presentation\Templates\TemplateManager');
		$classDefinition = $this->getClassDefinition($templateManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition)
			->addWorkspaceClassDefinition($classDefinition);
		$classDefinition->addMethod('setThemeManager', true)
			->addMethodParameter('setThemeManager', 'themeManager', array('type' => 'ThemeManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);


		//PageManager : EventManagerFactory, Configuration
		$pageManagerClassName = $this->getInjectedClassName('PageManager', 'Change\Presentation\Pages\PageManager');
		$classDefinition = $this->getClassDefinition($pageManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition)
			->addConfigurationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);


		//WorkflowManager : EventManagerFactory
		$workflowManagerClassName = $this->getInjectedClassName('WorkflowManager', 'Change\Workflow\WorkflowManager');
		$classDefinition = $this->getClassDefinition($workflowManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);


		//AuthenticationManager : EventManagerFactory
		$authenticationManagerClassName = $this->getInjectedClassName('AuthenticationManager', 'Change\User\AuthenticationManager');
		$classDefinition = $this->getClassDefinition($authenticationManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);


		//ProfileManager : EventManagerFactory
		$profileManagerClassName = $this->getInjectedClassName('ProfileManager', 'Change\User\ProfileManager');
		$classDefinition = $this->getClassDefinition($profileManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//PermissionsManager :
		$permissionsManagerClassName = $this->getInjectedClassName('PermissionsManager', 'Change\Permissions\PermissionsManager');
		$classDefinition = $this->getClassDefinition($permissionsManagerClassName);
		$classDefinition
			->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//OAuthManager : EventManagerFactory
		$oAuthManagerClassName = $this->getInjectedClassName('OAuthManager', 'Change\Http\OAuth\OAuthManager');
		$classDefinition = $this->getClassDefinition($oAuthManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);

		$instanceManager = $this->instanceManager();

		$workspace = $application->getWorkspace();

		$instanceManager->addAlias('Logging', $loggingClassName, array('configuration' => $configuration, 'workspace' => $workspace));

		$instanceManager->addAlias('DbProvider', $dbProviderClassName,
			array('configuration' => $configuration, 'workspace' => $workspace, 'eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('TransactionManager', $transactionManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('I18nManager', $i18nManagerClassName,
			array('configuration' => $configuration, 'workspace' => $workspace, 'eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('PluginManager', $pluginManagerClassName,
			array('workspace' => $workspace, 'eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('StorageManager', $storageManagerClassName,
			array('configuration' => $configuration, 'workspace' => $workspace));

		$instanceManager->addAlias('MailManager', $mailManagerClassName, array('configuration' => $configuration));

		$instanceManager->addAlias('ModelManager', $modelManagerClassName, array('eventManagerFactory' => $eventManagerFactory, 'workspace' => $workspace));

		$instanceManager->addAlias('DocumentManager', $documentManagerClassName, array('configuration' => $configuration, 'eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('DocumentCodeManager', $documentCodeManagerClassName, array());

		$instanceManager->addAlias('TreeManager', $treeManagerClassName, array());

		$instanceManager->addAlias('ConstraintsManager', $constraintsManagerClassName, array());

		$instanceManager->addAlias('CollectionManager', $collectionManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('JobManager', $jobManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('BlockManager', $blockManagerClassName,
			array('configuration' => $configuration, 'eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('RichTextManager', $richTextManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('ThemeManager', $themeManagerClassName,
				array('configuration' => $configuration, 'workspace' => $workspace, 'eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('TemplateManager', $templateManagerClassName,
			array('workspace' => $workspace, 'eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('PageManager', $pageManagerClassName,
			array('configuration' => $configuration, 'eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('WorkflowManager', $workflowManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('AuthenticationManager', $authenticationManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('ProfileManager', $profileManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));

		$instanceManager->addAlias('PermissionsManager', $permissionsManagerClassName, array());

		$instanceManager->addAlias('OAuthManager', $oAuthManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));
	}

	public function shutdown()
	{
		$this->application = null;
		$this->applicationServices = null;
		$this->eventManagerFactory = null;
		$im = $this->instanceManager;
		foreach ($im->getAliases() as $alias => $className)
		{
			if ($im->hasSharedInstance($alias))
			{
				$instance = $im->getSharedInstance($alias);
				if (is_callable(array($instance, 'shutdown')))
				{
					call_user_func(array($instance, 'shutdown'));
				}
			}
		}
	}

	/**
	 * @api
	 * @return \Change\Logging\Logging
	 */
	public function getLogging()
	{
		return $this->get('Logging');
	}

	/**
	 * @api
	 * @return \Change\Db\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->get('DbProvider');
	}

	/**
	 * @api
	 * @return \Change\Transaction\TransactionManager
	 */
	public function getTransactionManager()
	{
		return $this->get('TransactionManager');
	}

	/**
	 * @api
	 * @return \Change\I18n\I18nManager
	 */
	public function getI18nManager()
	{
		return $this->get('I18nManager');
	}

	/**
	 * @api
	 * @return \Change\Plugins\PluginManager
	 */
	public function getPluginManager()
	{
		return $this->get('PluginManager');
	}

	/**
	 * @api
	 * @return \Change\Storage\StorageManager
	 */
	public function getStorageManager()
	{
		return $this->get('StorageManager');
	}

	/**
	 * @api
	 * @return \Change\Mail\MailManager
	 */
	public function getMailManager()
	{
		return $this->get('MailManager');
	}

	/**
	 * @api
	 * @return \Change\Documents\ModelManager
	 */
	public function getModelManager()
	{
		return $this->get('ModelManager');
	}

	/**
	 * @api
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->get('DocumentManager');
	}

	/**
	 * @api
	 * @return \Change\Documents\DocumentCodeManager
	 */
	public function getDocumentCodeManager()
	{
		return $this->get('DocumentCodeManager');
	}

	/**
	 * @api
	 * @return \Change\Documents\TreeManager
	 */
	public function getTreeManager()
	{
		return $this->get('TreeManager');
	}

	/**
	 * @api
	 * @return \Change\Documents\Constraints\ConstraintsManager
	 */
	public function getConstraintsManager()
	{
		return $this->get('ConstraintsManager');
	}

	/**
	 * @api
	 * @return \Change\Collection\CollectionManager
	 */
	public function getCollectionManager()
	{
		return $this->get('CollectionManager');
	}

	/**
	 * @api
	 * @return \Change\Job\JobManager
	 */
	public function getJobManager()
	{
		return $this->get('JobManager');
	}


	/**
	 * @api
	 * @return \Change\Presentation\Blocks\BlockManager
	 */
	public function getBlockManager()
	{
		return $this->get('BlockManager');
	}

	/**
	 * @api
	 * @return \Change\Presentation\RichText\RichTextManager
	 */
	public function getRichTextManager()
	{
		return $this->get('RichTextManager');
	}

	/**
	 * @api
	 * @return \Change\Presentation\Themes\ThemeManager
	 */
	public function getThemeManager()
	{
		return $this->get('ThemeManager');
	}

	/**
	 * @api
	 * @return \Change\Presentation\Templates\TemplateManager
	 */
	public function getTemplateManager()
	{
		return $this->get('TemplateManager');
	}

	/**
	 * @api
	 * @return \Change\Presentation\Pages\PageManager
	 */
	public function getPageManager()
	{
		return $this->get('PageManager');
	}

	/**
	 * @api
	 * @return \Change\Workflow\WorkflowManager
	 */
	public function getWorkflowManager()
	{
		return $this->get('WorkflowManager');
	}

	/**
	 * @api
	 * @return \Change\User\AuthenticationManager
	 */
	public function getAuthenticationManager()
	{
		return $this->get('AuthenticationManager');
	}

	/**
	 * @api
	 * @return \Change\User\ProfileManager
	 */
	public function getProfileManager()
	{
		return $this->get('ProfileManager');
	}

	/**
	 * @api
	 * @return \Change\Permissions\PermissionsManager
	 */
	public function getPermissionsManager()
	{
		return $this->get('PermissionsManager');
	}

	/**
	 * @api
	 * @return \Change\Http\OAuth\OAuthManager
	 */
	public function getOAuthManager()
	{
		return $this->get('OAuthManager');
	}
}