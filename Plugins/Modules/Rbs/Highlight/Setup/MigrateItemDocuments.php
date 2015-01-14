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
		if (!in_array('rbs_highlight_doc_item', $tableNames))
		{
			return;
		}

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$rel = $fb->getDocumentRelationTable('rbs_highlight_highlight');
		$item = $fb->table('rbs_highlight_doc_item');
		$itemI18n = $fb->table('rbs_highlight_doc_item_i18n');
		$qb->select(
			$fb->alias($fb->column('document_id', $rel), 'list_id'),
			$fb->alias($fb->column('document_id', $item), 'item_id'),
			$fb->column('label', $item),
			$fb->column('visual', $item),
			$fb->column('reflcid', $item),
			$fb->column('targetdocument', $item),
			$fb->column('extendeddata', $item),
			$fb->column('lcid', $itemI18n),
			$fb->column('title', $itemI18n),
			$fb->column('description', $itemI18n),
			$fb->column('targeturl', $itemI18n),
			$fb->column('active', $itemI18n),
			$fb->column('startactivation', $itemI18n),
			$fb->column('endactivation', $itemI18n)
		)
			->from($rel)
			->where($fb->eq($fb->column('relname', $rel), $fb->string('items')));

		$qb->innerJoin($item, $fb->eq($fb->column('relatedid', $rel), $fb->column('document_id', $item)));
		$qb->innerJoin($itemI18n, $fb->eq($fb->column('document_id', $item), $fb->column('document_id', $itemI18n)));
		$qb->orderAsc($fb->column('document_id', $rel))
			->orderAsc($fb->column('relorder', $rel))
			->orderAsc($fb->column('relatedid', $rel));
		$query = $qb->query();
		$result = $query->getResults($query->getRowsConverter()
			->addBoolCol('active')->addDtCol('startactivation', 'endactivation')
			->addIntCol('list_id', 'item_id', 'visual', 'targetdocument')
			->addStrCol('label', 'value', 'extendeddata', 'reflcid', 'lcid', 'title', 'description', 'targeturl'));

		$tm->begin();

		/** @var $highlight \Rbs\Highlight\Documents\Highlight */
		$highlight = null;
		$itemId = null;
		foreach ($result as $itemInfo)
		{
			if (!$highlight || $highlight->getId() != $itemInfo['list_id'])
			{
				if ($highlight)
				{
					echo $highlight->getLabel(), ' -> ', $highlight->getItems()->count(), ' items', PHP_EOL;
					$highlight->save();
				}
				$highlight = $documentManager->getDocumentInstance($itemInfo['list_id'], 'Rbs_Highlight_Highlight');
				$highlight->useCorrection(false);
				$highlight->setItems([]);
			}

			if ($highlight)
			{
				if ($itemId != $itemInfo['item_id'])
				{
					$itemId = $itemInfo['item_id'];
					$item = $highlight->newHighlightItem();
					$item->setRefLCID($itemInfo['reflcid']);
					$item->setLabel($itemInfo['label']);

					$item->setVisual($documentManager->getDocumentInstance($itemInfo['visual']));
					$item->setTargetDocument($documentManager->getDocumentInstance($itemInfo['targetdocument']));
					$item->setExtendedData($itemInfo['extendeddata']);
					$item->getRefLocalization();
					$highlight->getItems()->add($item);
				}

				$documentManager->pushLCID($itemInfo['lcid']);
				$localizationPart = $item->getCurrentLocalization();

				$localizationPart->setTitle($itemInfo['title']);
				$description = $itemInfo['description'];
				if ($description)
				{
					$description = json_decode($description, true);
				}
				$localizationPart->setDescription(new \Change\Documents\RichtextProperty($description));
				$localizationPart->setTargetUrl($itemInfo['targeturl']);

				$localizationPart->setActive($itemInfo['active']);
				$localizationPart->setStartActivation($itemInfo['startactivation']);
				$localizationPart->setEndActivation($itemInfo['endactivation']);
				$documentManager->popLCID();
			}
		}

		if ($highlight)
		{
			echo $highlight->getLabel(), ' -> ', $highlight->getItems()->count(), ' items', PHP_EOL;
			$highlight->save();
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