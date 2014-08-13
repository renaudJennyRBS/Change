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
		if (!in_array('rbs_simpleform_doc_field', $tableNames))
		{
			return;
		}

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$rel = $fb->getDocumentRelationTable('rbs_simpleform_form');
		$field = $fb->table('rbs_simpleform_doc_field');
		$fieldI18n = $fb->table('rbs_simpleform_doc_field_i18n');
		$qb->select(
			$fb->alias($fb->column('document_id', $rel), 'form_id'),
			$fb->alias($fb->column('document_id', $field), 'field_id'),
			$fb->column('document_id', $field),
			$fb->column('label', $field),
			$fb->column('reflcid', $field),
			$fb->column('fieldtypecode', $field),
			$fb->column('required', $field),
			$fb->column('parameters', $field),
			$fb->column('lcid', $fieldI18n),
			$fb->column('title', $fieldI18n),
			$fb->column('helptext', $fieldI18n)
		)
			->from($rel);

		$qb->innerJoin($field, $fb->eq($fb->column('relatedid', $rel), $fb->column('document_id', $field)));
		$qb->innerJoin($fieldI18n, $fb->eq($fb->column('document_id', $field), $fb->column('document_id', $fieldI18n)));
		$qb->orderAsc($fb->column('document_id', $rel))
			->orderAsc($fb->column('relorder', $rel))
			->orderAsc($fb->column('relatedid', $rel));
		$query = $qb->query();
		$result = $query->getResults($query->getRowsConverter()
			->addBoolCol('required')
			->addIntCol('form_id', 'field_id', 'document_id')
			->addStrCol('label', 'reflcid', 'lcid', 'fieldtypecode', 'parameters', 'title', 'helptext'));

		$tm->begin();

		/** @var $form \Rbs\Simpleform\Documents\Form */
		$form = null;
		$fieldId = null;
		$field = null;
		echo count($result), ' results to migrate', PHP_EOL;
		foreach ($result as $fieldInfo)
		{
			if (!$form || $form->getId() != $fieldInfo['form_id'])
			{
				if ($form)
				{
					echo $form->getLabel(), ' -> ', $form->getFields()->count(), ' items', PHP_EOL;
					$form->save();
				}
				$form = $documentManager->getDocumentInstance($fieldInfo['form_id'], 'Rbs_Simpleform_Form');
				$form->useCorrection(false);
				$form->setFields([]);
			}

			if ($form)
			{
				if ($fieldId != $fieldInfo['field_id'])
				{
					$fieldId = $fieldInfo['field_id'];
					$field = $form->newFormField();
					$field->setRefLCID($fieldInfo['reflcid']);
					$field->setLabel($fieldInfo['label']);
					$field->setName('field' . $fieldInfo['document_id']);

					$field->setFieldTypeCode($fieldInfo['fieldtypecode']);
					$field->setRequired($fieldInfo['required']);
					$field->setParameters(($fieldInfo['parameters']) ? json_decode($fieldInfo['parameters']) : null);
					$field->getRefLocalization();
					$form->getFields()->add($field);
				}

				$documentManager->pushLCID($fieldInfo['lcid']);
				$localizationPart = $field->getCurrentLocalization();

				$localizationPart->setTitle($fieldInfo['title']);
				$helpText = $fieldInfo['helptext'];
				if ($helpText)
				{
					$helpText = json_decode($helpText, true);
				}
				$localizationPart->setHelpText(new \Change\Documents\RichtextProperty($helpText));
				$documentManager->popLCID();
			}
		}

		if ($form)
		{
			echo $form->getLabel(), ' -> ', $form->getFields()->count(), ' items', PHP_EOL;
			$form->save();
		}

		$tm->commit();
	}
}

$eventManager = $application->getNewEventManager('Form');
$eventManager->attach('migrate', function (\Change\Events\Event $event)
{
	(new MigrateFieldDocuments())->migrate($event);
});

$eventManager->trigger('migrate', null, []);