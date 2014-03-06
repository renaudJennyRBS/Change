<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Services;

/**
* @name \Change\Services\ApplicationServices
*/
class ApplicationServices extends \Zend\Di\Di
{
	use ServicesCapableTrait;

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
	 */
	function __construct($application)
	{
		$this->setApplication($application);
		$configuration = $application->getConfiguration();

		$definitionList = new \Zend\Di\DefinitionList(array());

		//DbProvider : Application
		$section = $configuration->getEntry('Change/Db/use', 'default');
		$dbProviderClassName = $configuration->getEntry('Change/Db/' . $section . '/dbprovider', 'Change\Db\Mysql\DbProvider');
		$classDefinition = $this->getClassDefinition($dbProviderClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//TransactionManager : Application
		$transactionManagerClassName = $this->getInjectedClassName('TransactionManager', 'Change\Transaction\TransactionManager');
		$classDefinition = $this->getClassDefinition($transactionManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//I18nManager : Application, PluginManager
		$i18nManagerClassName = $this->getInjectedClassName('I18nManager', 'Change\I18n\I18nManager');
		$classDefinition = $this->getClassDefinition($i18nManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setPluginManager', true)
			->addMethodParameter('setPluginManager', 'pluginManager', array('type' => 'PluginManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);


		//PluginManager : Application, DbProvider, TransactionManager
		$pluginManagerClassName = $this->getInjectedClassName('PluginManager', 'Change\Plugins\PluginManager');
		$classDefinition = $this->getClassDefinition($pluginManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//StorageManager : Application, DbProvider, TransactionManager
		$storageManagerClassName = $this->getInjectedClassName('StorageManager', 'Change\Storage\StorageManager');
		$classDefinition = $this->getClassDefinition($storageManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//MailManager : Application
		$mailManagerClassName = $this->getInjectedClassName('MailManager', 'Change\Mail\MailManager');
		$classDefinition = $this->getClassDefinition($mailManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//ModelManager : Application, PluginManager
		$modelManagerClassName = $this->getInjectedClassName('ModelManager', 'Change\Documents\ModelManager');
		$classDefinition = $this->getClassDefinition($modelManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setPluginManager', true)
			->addMethodParameter('setPluginManager', 'pluginManager', array('type' => 'PluginManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//DocumentManager : Application, ModelManager, DbProvider, I18nManager
		$documentManagerClassName = $this->getInjectedClassName('DocumentManager', 'Change\Documents\DocumentManager');
		$classDefinition = $this->getClassDefinition($documentManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setModelManager', true)
				->addMethodParameter('setModelManager', 'modelManager', array('type' => 'ModelManager', 'required' => true))
			->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setI18nManager', true)
				->addMethodParameter('setI18nManager', 'i18nManager', array('type' => 'I18nManager', 'required' => true));
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

		//CollectionManager : Application
		$collectionManagerClassName = $this->getInjectedClassName('CollectionManager', 'Change\Collection\CollectionManager');
		$classDefinition = $this->getClassDefinition($collectionManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//JobManager : Application, DbProvider
		$jobManagerClassName = $this->getInjectedClassName('CollectionManager', 'Change\Job\JobManager');
		$classDefinition = $this->getClassDefinition($jobManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition
			->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//BlockManager: Application
		$blockManagerClassName = $this->getInjectedClassName('BlockManager', 'Change\Presentation\Blocks\BlockManager');
		$classDefinition = $this->getClassDefinition($blockManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//RichTextManager : Application
		$richTextManagerClassName = $this->getInjectedClassName('RichTextManager', 'Change\Presentation\RichText\RichTextManager');
		$classDefinition = $this->getClassDefinition($richTextManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//ThemeManager : Application
		$themeManagerClassName = $this->getInjectedClassName('ThemeManager', 'Change\Presentation\Themes\ThemeManager');
		$classDefinition = $this->getClassDefinition($themeManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//TemplateManager : Application, ThemeManager
		$templateManagerClassName = $this->getInjectedClassName('TemplateManager', 'Change\Presentation\Templates\TemplateManager');
		$classDefinition = $this->getClassDefinition($templateManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setThemeManager', true)
			->addMethodParameter('setThemeManager', 'themeManager', array('type' => 'ThemeManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);


		//PageManager : Application
		$pageManagerClassName = $this->getInjectedClassName('PageManager', 'Change\Presentation\Pages\PageManager');
		$classDefinition = $this->getClassDefinition($pageManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);


		//WorkflowManager : Application
		$workflowManagerClassName = $this->getInjectedClassName('WorkflowManager', 'Change\Workflow\WorkflowManager');
		$classDefinition = $this->getClassDefinition($workflowManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);


		//AuthenticationManager : Application
		$authenticationManagerClassName = $this->getInjectedClassName('AuthenticationManager', 'Change\User\AuthenticationManager');
		$classDefinition = $this->getClassDefinition($authenticationManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);


		//ProfileManager : Application
		$profileManagerClassName = $this->getInjectedClassName('ProfileManager', 'Change\User\ProfileManager');
		$classDefinition = $this->getClassDefinition($profileManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//PermissionsManager : DbProvider, TransactionManager
		$permissionsManagerClassName = $this->getInjectedClassName('PermissionsManager', 'Change\Permissions\PermissionsManager');
		$classDefinition = $this->getClassDefinition($permissionsManagerClassName);
		$classDefinition
			->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//OAuthManager : Application, DbProvider, TransactionManager
		$oAuthManagerClassName = $this->getInjectedClassName('OAuthManager', 'Change\Http\OAuth\OAuthManager');
		$classDefinition = $this->getClassDefinition($oAuthManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('type' => 'TransactionManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);


		//PathRuleManager : Application, DbProvider
		$pathRuleManagerClassName = $this->getInjectedClassName('PathRuleManager', 'Change\Http\Web\PathRuleManager');
		$classDefinition = $this->getClassDefinition($pathRuleManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setDbProvider', true)
			->addMethodParameter('setDbProvider', 'dbProvider', array('type' => 'DbProvider', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);

		$instanceManager = $this->instanceManager();

		$instanceManager->addAlias('DbProvider', $dbProviderClassName,
			array('application' => $application));

		$instanceManager->addAlias('TransactionManager', $transactionManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('I18nManager', $i18nManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('PluginManager', $pluginManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('StorageManager', $storageManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('MailManager', $mailManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('ModelManager', $modelManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('DocumentManager', $documentManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('DocumentCodeManager', $documentCodeManagerClassName, array());

		$instanceManager->addAlias('TreeManager', $treeManagerClassName, array());

		$instanceManager->addAlias('ConstraintsManager', $constraintsManagerClassName, array());

		$instanceManager->addAlias('CollectionManager', $collectionManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('JobManager', $jobManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('BlockManager', $blockManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('RichTextManager', $richTextManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('ThemeManager', $themeManagerClassName,
				array('application' => $application));

		$instanceManager->addAlias('TemplateManager', $templateManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('PageManager', $pageManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('WorkflowManager', $workflowManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('AuthenticationManager', $authenticationManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('ProfileManager', $profileManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('PermissionsManager', $permissionsManagerClassName, array());

		$instanceManager->addAlias('OAuthManager', $oAuthManagerClassName,
			array('application' => $application));

		$instanceManager->addAlias('PathRuleManager', $pathRuleManagerClassName,
			array('application' => $application));
	}

	public function shutdown()
	{
		$this->application = null;
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
		return $this->getApplication()->getLogging();
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

	/**
	 * @api
	 * @return \Change\Http\Web\PathRuleManager
	 */
	public function getPathRuleManager()
	{
		return $this->get('PathRuleManager');
	}
}