<?php
namespace Change\Users\Setup;

/**
 * Class Install
 * @package Change\Users\Setup
 * @name \Change\Users\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Application $application
	 */
	public function executeApplication($application)
	{
		$application->getConfiguration()->addPersistentEntry('Change/Presentation/Blocks/Change_Users', '\\Change\\Users\\Blocks\\SharedListenerAggregate');
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function executeServices($applicationServices, $documentServices)
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

			/* @var $user \Change\Users\Documents\User */
			$user = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Change_Users_User');
			$user->setLabel('Administrateur');
			$user->setEmail('admin@temporary.fr');
			$user->setLogin('admin');
			$user->setPassword('admin');
			$user->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE);
			$user->addGroups($group);

			$user->create();
		}
	}
}