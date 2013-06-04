<?php
namespace Change\Users\Setup;

/**
 * @name \Change\Users\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application)
	{
		/* @var $config \Change\Configuration\EditableConfiguration */
		$config = $application->getConfiguration();
		$config->addPersistentEntry('Change/Presentation/Blocks/Change_Users',
			'\\Change\\Users\\Blocks\\SharedListenerAggregate');

		$config->addPersistentEntry('Change/Admin/Listeners/Change_Users',
			'\\Change\\Users\\Admin\\Register');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $documentServices, $presentationServices)
	{
		$groupModel = $documentServices->getModelManager()->getModelByName('Change_Users_Group');
		$query = new \Change\Documents\Query\Builder($documentServices, $groupModel);
		$group = $query->andPredicates($query->eq('realm', 'rest'))->getFirstDocument();
		if (!$group)
		{
			/* @var $group \Change\Users\Documents\Group */
			$group = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
			$group->setLabel('Backoffice');
			$group->setRealm('rest');
			$group->create();

			/* @var $group2 \Change\Users\Documents\Group */
			$group2 = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
			$group2->setLabel('Site Web');
			$group2->setRealm('web');
			$group2->create();

			/* @var $user \Change\Users\Documents\User */
			$user = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Change_Users_User');
			$user->setLabel('Administrator');
			$user->setEmail('admin@temporary.fr');
			$user->setLogin('admin');
			$user->setPassword('admin');
			$user->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE);
			$user->addGroups($group);
			$user->addGroups($group2);
			$user->create();
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}