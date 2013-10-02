<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\SectionProductList
 */
class SectionProductList extends \Compilation\Rbs\Catalog\Documents\SectionProductList
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, array($this, 'onCreated'), 5);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onCreated(\Change\Documents\Events\Event $event)
	{
		$section = $this->getSynchronizedSection();
		if ($section)
		{
			$jm = new \Change\Job\JobManager();
			$jm->setApplicationServices($this->getApplicationServices());
			$jm->createNewJob('Rbs_Catalog_InitializeItemsForSectionList', array('docId' => $this->getId()));
		}
	}
}
