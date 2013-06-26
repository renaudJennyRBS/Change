<?php
namespace Rbs\Tag\Setup;
use Change\Application\ApplicationServices;
use Change\Db\Schema\FieldDefinition;
use Change\Db\Schema\KeyDefinition;

/**
 * @name \Rbs\Tag\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function initialize($plugin)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application)
	{
		/* @var $config \Change\Configuration\EditableConfiguration */
		$config = $application->getConfiguration();

		$config->addPersistentEntry('Change/Events/Http.Rest/Rbs_Tag',
			'\\Rbs\\Tag\\Http\\Rest\\ListenerAggregate');

		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Tag',
			'\\Rbs\\Tag\\Admin\\Register');

		$config->addPersistentEntry('Change/Events/Db/Rbs_Tag',
			'\\Rbs\\Tag\\Db\\ListenerAggregate');

		$appServices = new ApplicationServices($application);
		$schemaManager = $appServices->getDbProvider()->getSchemaManager();

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
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
//	public function executeServices($plugin, $documentServices, $presentationServices)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function finalize($plugin)
//	{
//	}
}
