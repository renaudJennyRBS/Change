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
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$this->initializeTables($applicationServices);
		$this->createDefaultTags($applicationServices, $documentServices);
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	private function initializeTables($applicationServices)
	{
		$schemaManager = $applicationServices->getDbProvider()->getSchemaManager();

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

		$applicationServices->getDbProvider()->closeConnection();
	}


	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @throws
	 */
	private function createDefaultTags($applicationServices, $documentServices)
	{
		$tagModel = $documentServices->getModelManager()->getModelByName('Rbs_Tag_Tag');
		$documentManager = $documentServices->getDocumentManager();

		$query = new \Change\Documents\Query\Query($documentServices, $tagModel);
		if ($query->getCountDocuments())
		{
			return;
		}

		$lcid = $applicationServices->getI18nManager()->getLCID();

		// TODO Move hard-coded text elsewhere.
		if ($lcid === 'fr_FR')
		{
			$tags = array(
				// Media
				'grande image'  => 'grey',
				'moyenne image' => 'grey',
				// Other...
				'à traduire' => 'red'
			);
		}
		else
		{
			$tags = array(
				// Media
				'large picture'  => 'grey',
				'medium picture' => 'grey',
				// Other...
				'to translate' => 'red'
			);
		}

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();
			foreach ($tags as $label => $color)
			{
				/* @var $tag \Rbs\Tag\Documents\Tag */
				$tag = $documentManager->getNewDocumentInstanceByModel($tagModel);
				$tag->setLabel($label);
				$tag->setColor($color);
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
