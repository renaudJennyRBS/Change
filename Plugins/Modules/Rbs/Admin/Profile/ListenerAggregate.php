<?php
namespace Rbs\Admin\Profile;

use Change\User\ProfileManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Admin\Profile\ListenerAggregate
 */
class ListenerAggregate implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach(array(ProfileManager::EVENT_LOAD), array($this, 'onLoad'), 5);
		$events->attach(array(ProfileManager::EVENT_SAVE), array($this, 'onSave'), 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

	/**
	 * @param Event $event
	 */
	public function onLoad(Event $event)
	{
		if ($event->getParam('profileName') === 'Rbs_Admin')
		{
			$profile = new Profile();

			$user = $event->getParam('user');
			$documentServices = $event->getParam('documentServices');
			if ($documentServices instanceof \Change\Documents\DocumentServices && $user instanceof \Change\User\UserInterface)
			{
				$docUser = $documentServices->getDocumentManager()->getDocumentInstance($user->getId());
				if ($docUser instanceof \Rbs\User\Documents\User)
				{
					$result = $docUser->getMeta('profile_Rbs_Admin');
					if (is_array($result))
					{
						foreach ($result as $name => $value)
						{
							$profile->setPropertyValue($name, $value);
						}
					}
				}
			}
			$event->setParam('profile', $profile);
		}
	}

	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function onSave(Event $event)
	{
		$profile = $event->getParam('profile');
		if ($profile instanceof Profile)
		{
			$user = $event->getParam('user');
			$documentServices = $event->getParam('documentServices');
			if ($documentServices instanceof \Change\Documents\DocumentServices && $user instanceof \Change\User\UserInterface)
			{
				$transactionManager = $documentServices->getApplicationServices()->getTransactionManager();
				try
				{
					$transactionManager->begin();
					$docUser = $documentServices->getDocumentManager()->getDocumentInstance($user->getId());
					if ($docUser instanceof \Rbs\User\Documents\User)
					{
						$props = array();
						foreach ($profile->getPropertyNames() as $name)
						{
							$props[$name] = $profile->getPropertyValue($name);
						}
						$docUser->setMeta('profile_Rbs_Admin', $props);
						$docUser->saveMetas();
					}
					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
		}
	}
}