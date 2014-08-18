<?php
require_once(getcwd() . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

class MigrateMenuDocuments
{
	public function migrate(\Change\Events\Event $event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$menu = $fb->table('rbs_website_doc_menu');
		$menuI18n = $fb->table('rbs_website_doc_menu_i18n');
		$qb->select(
			$fb->column('document_id', $menu),
			$fb->column('items', $menuI18n)
		)->from($menu);

		$qb->innerJoin($menuI18n, $fb->logicAnd(
			$fb->eq($fb->column('document_id', $menu), $fb->column('document_id', $menuI18n)),
			$fb->eq($fb->column('reflcid', $menu), $fb->column('lcid', $menuI18n))
		));
		$qb->orderAsc($fb->column('document_id', $menu));
		$query = $qb->query();
		$result = $query->getResults($query->getRowsConverter()
			->addIntCol('document_id')
			->addStrCol('items'));

		$tm->begin();

		/** @var $menu \Rbs\Website\Documents\Menu */
		echo count($result), ' menus to migrate', PHP_EOL;
		foreach ($result as $menuInfo)
		{
			$menu = $documentManager->getDocumentInstance($menuInfo['document_id'], 'Rbs_Website_Menu');
			$menu->useCorrection(false);
			$menu->setEntries([]);

			$entriesInfos = $menuInfo['items'] ? json_decode($menuInfo['items'], true) : null;
			if (is_array($entriesInfos) && count($entriesInfos))
			{
				foreach ($entriesInfos as $entryInfos)
				{
					$entry = $menu->newMenuEntry();
					$entry->setRefLCID($menu->getRefLCID());
					$entry->setLabel($entryInfos['label']);

					$localizationPart = $entry->getCurrentLocalization();
					$localizationPart->setTitle($entryInfos['title']);
					if (isset($entryInfos['documentId']))
					{
						$doc = $documentManager->getDocumentInstance($entryInfos['documentId']);
						if (!$doc)
						{
							continue;
						}
						$entry->setEntryTypeCode('document');
						$entry->setTargetDocument($doc);
					}
					elseif (isset($entryInfos['url']))
					{
						$entry->setEntryTypeCode('url');
						$localizationPart->setUrl($entryInfos['url']);
					}
					$menu->getEntries()->add($entry);
				}
			}
			echo $menu->getLabel(), ' -> ', $menu->getEntries()->count(), ' entries', PHP_EOL;
			$menu->save();
		}

		$tm->commit();
	}
}

$eventManager = $application->getNewEventManager('Menu');
$eventManager->attach('migrate', function (\Change\Events\Event $event)
{
	(new MigrateMenuDocuments())->migrate($event);
});

$eventManager->trigger('migrate', null, []);