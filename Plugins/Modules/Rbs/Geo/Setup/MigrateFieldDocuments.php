<?php
require_once(getcwd() . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

class MigrateFieldDocuments
{
	public function migrate(\Change\Events\Event $event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$tableNames = $dbProvider->getSchemaManager()->getTableNames();
		if (!in_array('rbs_geo_doc_addressfield', $tableNames))
		{
			return;
		}

		$tm->begin();

		$fixTable = $dbProvider->getNewStatementBuilder();
		$fb = $fixTable->getFragmentBuilder();
		$fixTable->update($fb->getDocumentTable('Rbs_Geo_AddressFields'));
		$fixTable->assign($fb->column('fields'), $fb->parameter('emptyFields'));
		$fixTable->where($fb->eq($fb->column('fields'), $fb->string('0')));
		$update = $fixTable->updateQuery();
		$update->bindParameter('emptyFields', null);
		$result = $update->execute();
		echo 'Cleaned empty fields: ' . $result, PHP_EOL;

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$rel = $fb->getDocumentRelationTable('Rbs_Geo_AddressFields');
		$fieldTable = $fb->table('rbs_geo_doc_addressfield');
		$fieldI18nTable = $fb->table('rbs_geo_doc_addressfield_i18n');
		$qb->select(
			$fb->alias($fb->column('document_id', $rel), 'fields_id'),
			$fb->alias($fb->column('document_id', $fieldTable), 'field_id'),
			$fb->column('reflcid', $fieldTable),
			$fb->column('label', $fieldTable),
			$fb->column('match', $fieldTable),
			$fb->column('defaultvalue', $fieldTable),
			$fb->column('collectioncode', $fieldTable),
			$fb->column('required', $fieldTable),
			$fb->column('code', $fieldTable),
			$fb->column('locked', $fieldTable),
			$fb->column('lcid', $fieldI18nTable),
			$fb->column('title', $fieldI18nTable)
		)
			->from($rel)
			->where($fb->eq($fb->column('relname', $rel), $fb->string('fields')));

		$qb->innerJoin($fieldTable, $fb->eq($fb->column('relatedid', $rel), $fb->column('document_id', $fieldTable)));
		$qb->innerJoin($fieldI18nTable, $fb->eq($fb->column('document_id', $fieldTable), $fb->column('document_id', $fieldI18nTable)));
		$qb->orderAsc($fb->column('document_id', $rel))
			->orderAsc($fb->column('relorder', $rel))
			->orderAsc($fb->column('relatedid', $rel));
		$query = $qb->query();
		$result = $query->getResults($query->getRowsConverter()
			->addBoolCol('required', 'locked')
			->addIntCol('fields_id', 'field_id')
			->addStrCol('reflcid', 'label', 'match', 'defaultvalue', 'collectioncode', 'code', 'lcid', 'title'));

		/** @var $addressFields \Rbs\Geo\Documents\AddressFields */
		$addressFields = null;
		$fieldId = null;
		/** @var $addressField \Rbs\Geo\Documents\AddressField */
		$addressField = null;
		foreach ($result as $itemInfo)
		{
			if (!$addressFields || $addressFields->getId() != $itemInfo['fields_id'])
			{
				if ($addressFields)
				{
					echo $addressFields->getLabel(), ' -> ', $addressFields->getFields()->count(), ' fields', PHP_EOL;
					$addressFields->save();
				}
				$addressFields = $documentManager->getDocumentInstance($itemInfo['fields_id'], 'Rbs_Geo_AddressFields');
				$addressFields->useCorrection(false);
				$addressFields->setFields([]);
			}

			if ($addressFields)
			{
				if ($fieldId != $itemInfo['field_id'])
				{
					$fieldId = $itemInfo['field_id'];
					$addressField = $addressFields->newAddressField();
					$addressField->setRefLCID($itemInfo['reflcid']);
					$addressField->setLabel($itemInfo['label']);

					$addressField->setCode($itemInfo['code']);
					$addressField->setLocked($itemInfo['locked']);
					$addressField->setRequired($itemInfo['required']);
					$addressField->setDefaultValue($itemInfo['defaultvalue']);
					$addressField->setCollectionCode($itemInfo['collectioncode']);
					$addressField->setMatch($itemInfo['match']);
					$addressField->getRefLocalization();
					$addressFields->getFields()->add($addressField);
				}

				$documentManager->pushLCID($itemInfo['lcid']);
				$localizationPart = $addressField->getCurrentLocalization();
				$localizationPart->setTitle($itemInfo['title']);
				$documentManager->popLCID();
			}
		}

		if ($addressFields)
		{
			echo $addressFields->getLabel(), ' -> ', $addressFields->getFields()->count(), ' fields', PHP_EOL;
			$addressFields->save();
		}

		$tm->commit();
	}
}

$eventManager = $application->getNewEventManager('Collection');
$eventManager->attach('migrate', function (\Change\Events\Event $event)
{
	(new MigrateFieldDocuments())->migrate($event);
});

$eventManager->trigger('migrate', null, []);