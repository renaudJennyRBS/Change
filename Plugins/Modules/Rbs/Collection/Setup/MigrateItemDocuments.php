<?php
require_once(getcwd() . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

class MigrateItemDocuments
{
	public function migrate(\Change\Events\Event $event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$tableNames = $dbProvider->getSchemaManager()->getTableNames();
		if (!in_array('rbs_collection_doc_item', $tableNames))
		{
			return;
		}

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$rel = $fb->getDocumentRelationTable('rbs_collection_collection');
		$item = $fb->table('rbs_collection_doc_item');
		$itemI18n = $fb->table('rbs_collection_doc_item_i18n');
		$qb->select(
			$fb->alias($fb->column('document_id', $rel), 'list_id'),
			$fb->alias($fb->column('document_id', $item), 'item_id'),
			$fb->column('value', $item), $fb->column('label', $item),
			$fb->column('reflcid', $item), $fb->column('locked', $item),
			$fb->column('lcid', $itemI18n), $fb->column('title', $itemI18n)
		)
			->from($rel)
			->where($fb->eq($fb->column('relname', $rel), $fb->string('items')));

		$qb->innerJoin($item, $fb->eq($fb->column('relatedid', $rel), $fb->column('document_id', $item)));
		$qb->innerJoin($itemI18n, $fb->eq($fb->column('document_id', $item), $fb->column('document_id', $itemI18n)));
		$qb->orderAsc($fb->column('document_id', $rel))
			->orderAsc($fb->column('relorder', $rel))
			->orderAsc($fb->column('relatedid', $rel));
		$query = $qb->query();
		$result = $query->getResults($query->getRowsConverter()->addBoolCol('locked')
			->addIntCol('list_id', 'item_id')->addStrCol('label', 'value', 'reflcid', 'lcid', 'title'));
		var_export($result);

		$tm->begin();


		/** @var $collection \Rbs\Collection\Documents\Collection */
		$collection = null;;
		foreach ($result as $itemInfo)
		{
			if (!$collection || $collection->getId() != $itemInfo['list_id'])
			{
				if ($collection)
				{
					echo $collection->getLabel(), ' -> ', $collection->getItems()->count(), ' items', PHP_EOL;
					$collection->save();
				}
				$collection = $documentManager->getDocumentInstance($itemInfo['list_id'], 'Rbs_Collection_Collection');
			}

			if ($collection)
			{
				$value = $itemInfo['value'];
				$item = $collection->getItemByValue($value);
				if (!$item)
				{
					$item = $collection->newCollectionItem();
					$item->setValue($value);
					$item->setRefLCID($itemInfo['reflcid']);
					$item->setLabel($itemInfo['label']);
					$item->setLocked($itemInfo['locked']);
					$item->getRefLocalization()->setTitle($itemInfo['title']);
					$collection->getItems()->add($item);
				}

				if ($itemInfo['reflcid'] != $itemInfo['lcid'])
				{
					$documentManager->pushLCID($itemInfo['lcid']);
					$item->getCurrentLocalization()->setTitle($itemInfo['title']);
					$documentManager->popLCID();
				}
				else
				{
					$item->getRefLocalization()->setTitle($itemInfo['title']);
				}
			}
		}

		if ($collection)
		{
			echo $collection->getLabel(), ' -> ', $collection->getItems()->count(), ' items', PHP_EOL;
			$collection->save();
		}

		$tm->commit();
	}
}

$eventManager = $application->getNewEventManager('Collection');
$eventManager->attach('migrate', function (\Change\Events\Event $event)
{
	(new MigrateItemDocuments())->migrate($event);
});

$eventManager->trigger('migrate', null, []);