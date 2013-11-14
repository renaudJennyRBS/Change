<?php
namespace Rbs\Tag\Setup;
use Change\Db\Schema\FieldDefinition;
use Change\Db\Schema\KeyDefinition;

/**
 * @name \Rbs\Tag\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @throws \RuntimeException
	 */
	public function executeDbSchema($plugin, $schemaManager)
	{
		$this->initializeTables($schemaManager);
	}

	/**
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 */
	private function initializeTables($schemaManager)
	{
		// Create table tag <-> doc
		$td = $schemaManager->newTableDefinition('rbs_tag_document');
		$tagIdField = new FieldDefinition('tag_id');
		$tagIdField->setType(FieldDefinition::INTEGER);
		$td->addField($tagIdField);
		$docIdField = new FieldDefinition('doc_id');
		$docIdField->setType(FieldDefinition::INTEGER);
		$td->addField($docIdField);
		$key = new KeyDefinition();
		$key->setType(KeyDefinition::PRIMARY);
		$key->addField($tagIdField);
		$key->addField($docIdField);
		$td->addKey($key);
		$schemaManager->createOrAlterTable($td);

		// Create table tag_search
		$td = $schemaManager->newTableDefinition('rbs_tag_search');
		$tagIdField = new FieldDefinition('tag_id');
		$tagIdField->setType(FieldDefinition::INTEGER);
		$td->addField($tagIdField);
		$searchTagIdField = new FieldDefinition('search_tag_id');
		$searchTagIdField->setType(FieldDefinition::INTEGER);
		$td->addField($searchTagIdField);
		$schemaManager->createOrAlterTable($td);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$this->createDefaultTags($applicationServices);
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws
	 */
	private function createDefaultTags($applicationServices)
	{
		$tagModel = $applicationServices->getModelManager()->getModelByName('Rbs_Tag_Tag');
		$documentManager = $applicationServices->getDocumentManager();

		$query = $applicationServices->getDocumentManager()->getNewQuery($tagModel);
		if ($query->getCountDocuments())
		{
			return;
		}

		//$lcid = $applicationServices->getI18nManager()->getLCID();
		$i18nManager = $applicationServices->getI18nManager();

		//Default tags
		$tags[] = array('label' => $i18nManager->trans('m.rbs.tag.setup-large-picture', array('ucf')), 'color' => 'gray', 'module' => 'Rbs_Media');
		$tags[] = array('label' => $i18nManager->trans('m.rbs.tag.setup-medium-picture', array('ucf')), 'color' => 'gray', 'module' => 'Rbs_Media');
		$tags[] = array('label' => $i18nManager->trans('m.rbs.tag.setup-to-translate', array('ucf')), 'color' => 'red', 'module' => NULL);

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();
			foreach ($tags as $defaultTag)
			{
				/* @var $tag \Rbs\Tag\Documents\Tag */
				$tag = $documentManager->getNewDocumentInstanceByModel($tagModel);
				$tag->setLabel($defaultTag['label']);
				$tag->setColor($defaultTag['color']);
				$tag->setModule($defaultTag['module']);
				$tag->create();
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}


	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
