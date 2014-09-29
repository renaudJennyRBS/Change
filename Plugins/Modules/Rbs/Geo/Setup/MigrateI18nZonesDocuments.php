<?php
require_once(getcwd() . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

class MigrateI18nZonesDocuments
{
	public function migrate(\Change\Events\Event $event)
	{
		$refLCID = $event->getApplicationServices()->getI18nManager()->getLCID();
		$tm = $event->getApplicationServices()->getTransactionManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$tableNames = $dbProvider->getSchemaManager()->getTableNames();
		if (!in_array('rbs_geo_doc_zone_i18n', $tableNames))
		{
			echo 'Execute the following commands before.', PHP_EOL,
			'php bin/change.phar change:compile-documents', PHP_EOL,
			'php bin/change.phar change:generate-db-schema -m', PHP_EOL, PHP_EOL;
			return;
		}

		$tm->begin();

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->getDocumentColumn('id'), 'id'),
			$fb->alias($fb->getDocumentColumn('creationDate'), 'creationDate'),
			$fb->alias($fb->getDocumentColumn('modificationDate'), 'modificationDate'),
			$fb->alias($fb->getDocumentColumn('authorName'), 'authorName'),
			$fb->alias($fb->getDocumentColumn('authorId'), 'authorId'),
			$fb->alias($fb->getDocumentColumn('documentVersion'), 'documentVersion')
			)
			->from($fb->getDocumentTable('Rbs_Geo_Zone'));
		$qb->where($fb->isNull($fb->getDocumentColumn('refLCID')));
		$select = $qb->query();
		$rows = $select->getResults($select->getRowsConverter()->addIntCol('id', 'authorId', 'documentVersion')
			->addStrCol('authorName')->addDtCol('creationDate', 'modificationDate'));

		foreach ($rows as $row)
		{
			echo 'Update document: ', $row['id'], PHP_EOL;

			$fixTable = $dbProvider->getNewStatementBuilder();
			$fb = $fixTable->getFragmentBuilder();
			$fixTable->update($fb->getDocumentTable('Rbs_Geo_Zone'));
			$fixTable->assign($fb->getDocumentColumn('refLCID'), $fb->parameter('refLCID'));
			$fixTable->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			$update = $fixTable->updateQuery();
			$update->bindParameter('refLCID', $refLCID);
			$update->bindParameter('id', $row['id']);
			$result = $update->execute();
			echo ', Update refLCID: ', $result;

			$fixTable = $dbProvider->getNewStatementBuilder();
			$fb = $fixTable->getFragmentBuilder();
			$fixTable->insert($fb->getDocumentI18nTable('Rbs_Geo_Zone'));
			$fixTable->addColumns($fb->getDocumentColumn('id'), $fb->getDocumentColumn('LCID'),
				$fb->getDocumentColumn('creationDate'), $fb->getDocumentColumn('modificationDate'),
				$fb->getDocumentColumn('authorName'), $fb->getDocumentColumn('authorId'),
				$fb->getDocumentColumn('documentVersion')
			);
			$fixTable->addValues($fb->integerParameter('id'), $fb->parameter('LCID'),
				$fb->dateTimeParameter('creationDate'),$fb->dateTimeParameter('modificationDate'),
				$fb->parameter('authorName'),$fb->integerParameter('authorId'),
				$fb->integerParameter('documentVersion'));
			$insert = $fixTable->insertQuery();
			$insert->bindParameter('id', $row['id']);
			$insert->bindParameter('LCID', $refLCID);
			$insert->bindParameter('creationDate', $row['creationDate']);
			$insert->bindParameter('modificationDate', $row['modificationDate']);
			$insert->bindParameter('authorName', $row['authorName']);
			$insert->bindParameter('authorId', $row['authorId']);
			$insert->bindParameter('documentVersion', $row['documentVersion']);
			$result = $insert->execute();
			echo ', Insert I18n: ', $result;
			echo PHP_EOL;
		}
		$tm->commit();
		echo 'Execute code below on project database', PHP_EOL,
		 'ALTER TABLE rbs_geo_doc_zone DROP COLUMN `creationdate`, DROP COLUMN `modificationdate`, DROP COLUMN `authorname`, DROP COLUMN `authorid`, DROP COLUMN `documentversion`;', PHP_EOL;
		return;
	}
}

$eventManager = $application->getNewEventManager('MigrateI18nZonesDocuments');
$eventManager->attach('migrate', function (\Change\Events\Event $event)
{
	(new MigrateI18nZonesDocuments())->migrate($event);
});

$eventManager->trigger('migrate', null, []);