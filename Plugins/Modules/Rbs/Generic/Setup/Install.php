<?php
namespace Rbs\Generic\Setup;
use Change\Db\Schema\FieldDefinition;
use Change\Db\Schema\KeyDefinition;

/**
 * @name \Rbs\Generic\Setup\Install
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
		$td = $schemaManager->newTableDefinition('change_document_filters');
		$idField = new FieldDefinition('filter_id');
		$idField->setType(FieldDefinition::INTEGER);
		$idField->setAutoNumber(true);
		$idField->setNullable(false);
		$td->addField($idField);

		$modelField = new FieldDefinition('model_name');
		$modelField->setType(FieldDefinition::VARCHAR);
		$modelField->setLength(80);
		$modelField->setNullable(false);
		$td->addField($modelField);

		$userIdField = new FieldDefinition('user_id');
		$userIdField->setType(FieldDefinition::INTEGER);
		$userIdField->setDefaultValue(0);
		$td->addField($userIdField);

		$jsonField = new FieldDefinition('content');
		$jsonField->setType(FieldDefinition::TEXT);
		$td->addField($jsonField);

		$titleField = new FieldDefinition('title');
		$titleField->setType(FieldDefinition::VARCHAR);
		$titleField->setLength(255);
		$titleField->setNullable(false);
		$td->addField($titleField);

		$key = new KeyDefinition();
		$key->setType(KeyDefinition::PRIMARY);
		$key->addField($idField);
		$td->addKey($key);
		$schemaManager->createOrAlterTable($td);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$configuration->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Generic',
			'\Rbs\Generic\Events\SharedListeners');
		$configuration->addPersistentEntry('Change/Events/CollectionManager/Rbs_Generic', '\Rbs\Generic\Collection\Listeners');

		$configuration->addPersistentEntry('Change/Events/Http/Rest/Rbs_Generic', '\Rbs\Generic\Events\Http\Rest\Listeners');
		$configuration->addPersistentEntry('Change/Events/Http/Web/Rbs_Generic', '\Rbs\Generic\Events\Http\Web\Listeners');

		$configuration->addPersistentEntry('Change/Events/Commands/Rbs_Generic', '\Rbs\Generic\Events\Commands\Listeners');
		$configuration->addPersistentEntry('Change/Events/Db/Rbs_Generic', '\Rbs\Generic\Events\Db\Listeners');
		$configuration->addPersistentEntry('Change/Events/WorkflowManager/Rbs_Generic',
			'\Rbs\Generic\Events\WorkflowManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Generic',
			'\Rbs\Generic\Events\BlockManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/PageManager/Rbs_Generic',
			'\Rbs\Generic\Events\PageManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/ProfileManager/Rbs_Generic',
			'\Rbs\Generic\Events\ProfileManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/AuthenticationManager/Rbs_Generic',
			'\Rbs\Generic\Events\AuthenticationManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/ThemeManager/Rbs_Generic',
			'\Rbs\Generic\Events\ThemeManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/RichTextManager/Rbs_Generic',
			'\Rbs\Generic\Events\RichTextManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/JobManager/Rbs_Generic', '\Rbs\Generic\Events\JobManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/OAuthManager/Rbs_Generic', '\Rbs\Generic\Events\OAuthManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/ModelManager/Rbs_Generic', '\Rbs\Generic\Events\ModelManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/PathRuleManager/Rbs_Generic', 'Rbs\Generic\Events\PathRuleManager\Listeners');
		$configuration->addPersistentEntry('Rbs/Mail/Events/MailManager/Rbs_Generic', 'Rbs\Generic\Events\MailManager\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$applicationServices->getThemeManager()->installPluginTemplates($plugin);
		(new \Rbs\User\Setup\Install())->executeServices($plugin, $applicationServices);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}