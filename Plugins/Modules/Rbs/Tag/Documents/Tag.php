<?php
namespace Rbs\Tag\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Tag\Documents\Tag
 */
class Tag extends \Compilation\Rbs\Tag\Documents\Tag
{

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_CREATED, array($this, 'setSearchTag'));
		$eventManager->attach(Event::EVENT_UPDATED, array($this, 'updateSearchTag'));
	}


	/**
	 * @param \Change\Documents\Events\Event $event
	 * @throws
	 */
	public function setSearchTag($event)
	{
		$tag = $event->getDocument();
		$appServices = $event->getDocument()->getApplicationServices();

		$transactionManager = $appServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$stmt = $appServices->getDbProvider()->getNewStatementBuilder();
			$fb = $stmt->getFragmentBuilder();
			$stmt->insert($fb->table('rbs_tag_search'), 'tag_id', 'search_tag_id');
			$stmt->addValues($fb->integerParameter('tagId'), $fb->integerParameter('searchTagId'));
			$iq = $stmt->insertQuery();

			$iq->bindParameter('tagId', $tag->getId());
			$iq->bindParameter('searchTagId', $tag->getId());
			$iq->execute();

			foreach ($tag->getChildren() as $childTag)
			{
				$stmt->insert($fb->table('rbs_tag_search'), 'tag_id', 'search_tag_id');
				$stmt->addValues($fb->integerParameter('tagId'), $fb->integerParameter('childTagId'));
				$iq = $stmt->insertQuery();
				$iq->bindParameter('tagId', $tag->getId());
				$iq->bindParameter('childTagId', $childTag->getId());
				$iq->execute();
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}


	/**
	 * @param \Change\Documents\Events\Event $event
	 * @throws
	 */
	public function updateSearchTag($event)
	{
		/** @var \Rbs\Tag\Documents\Tag $tag */
		$tag = $event->getDocument();

		// TODO Check if "children" property has been modified.

		$appServices = $event->getDocument()->getApplicationServices();
		$transactionManager = $appServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$stmt = $appServices->getDbProvider()->getNewStatementBuilder();
			$fb = $stmt->getFragmentBuilder();

			// Create delete all document's tags statement
			$stmt->delete($fb->table('rbs_tag_search'));
			$stmt->where($fb->eq($fb->column('tag_id'), $fb->integerParameter('tagId')));
			$dq = $stmt->deleteQuery();
			$dq->bindParameter('tagId', $tag->getId());
			$dq->execute();

			foreach ($tag->getChildren() as $childTag)
			{
				$stmt->insert($fb->table('rbs_tag_search'), 'tag_id', 'search_tag_id');
				$stmt->addValues($fb->integerParameter('tagId'), $fb->integerParameter('childTagId'));
				$iq = $stmt->insertQuery();
				$iq->bindParameter('tagId', $tag->getId());
				$iq->bindParameter('childTagId', $childTag->getId());
				$iq->execute();
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}

	}

}
