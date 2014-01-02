<?php
namespace ChangeTests\Change\Services;

/**
* @name \ChangeTests\Change\Services\ApplicationServicesTest
*/
class ApplicationServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstruct()
	{
		$applicationServices = $this->getApplicationServices();
		$this->assertInstanceOf('Change\Services\ApplicationServices', $applicationServices);

		$this->assertInstanceOf('Change\Logging\Logging', $applicationServices->getLogging());

		$this->assertInstanceOf('Change\Db\DbProvider', $applicationServices->getDbProvider());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getDbProvider()->getEventManager());

		$this->assertInstanceOf('Change\Transaction\TransactionManager', $applicationServices->getTransactionManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getTransactionManager()->getEventManager());

		$this->assertInstanceOf('Change\I18n\I18nManager', $applicationServices->getI18nManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getI18nManager()->getEventManager());

		$this->assertInstanceOf('Change\Plugins\PluginManager', $applicationServices->getPluginManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getPluginManager()->getEventManager());

		$this->assertInstanceOf('Change\Storage\StorageManager', $applicationServices->getStorageManager());

		$this->assertInstanceOf('Change\Mail\MailManager', $applicationServices->getMailManager());

		$this->assertInstanceOf('Change\Documents\ModelManager', $applicationServices->getModelManager());

		$this->assertInstanceOf('Change\Documents\DocumentManager', $applicationServices->getDocumentManager());

		$this->assertInstanceOf('Change\Documents\DocumentCodeManager', $applicationServices->getDocumentCodeManager());

		$this->assertInstanceOf('Change\Documents\TreeManager', $applicationServices->getTreeManager());

		$this->assertInstanceOf('Change\Documents\Constraints\ConstraintsManager', $applicationServices->getConstraintsManager());

		$this->assertInstanceOf('Change\Collection\CollectionManager', $applicationServices->getCollectionManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getCollectionManager()->getEventManager());

		$this->assertInstanceOf('Change\Job\JobManager', $applicationServices->getJobManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getJobManager()->getEventManager());

		$this->assertInstanceOf('Change\Presentation\Blocks\BlockManager', $applicationServices->getBlockManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getBlockManager()->getEventManager());

		$this->assertInstanceOf('Change\Presentation\RichText\RichTextManager', $applicationServices->getRichTextManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getRichTextManager()->getEventManager());

		$this->assertInstanceOf('Change\Presentation\Themes\ThemeManager', $applicationServices->getThemeManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getThemeManager()->getEventManager());

		$this->assertInstanceOf('Change\Presentation\Templates\TemplateManager', $applicationServices->getTemplateManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getTemplateManager()->getEventManager());

		$this->assertInstanceOf('Change\Presentation\Pages\PageManager', $applicationServices->getPageManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getPageManager()->getEventManager());

		$this->assertInstanceOf('Change\Workflow\WorkflowManager', $applicationServices->getWorkflowManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getWorkflowManager()->getEventManager());

		$this->assertInstanceOf('Change\User\AuthenticationManager', $applicationServices->getAuthenticationManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getAuthenticationManager()->getEventManager());

		$this->assertInstanceOf('Change\User\ProfileManager', $applicationServices->getProfileManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getProfileManager()->getEventManager());

		$this->assertInstanceOf('Change\Permissions\PermissionsManager', $applicationServices->getPermissionsManager());

		$this->assertInstanceOf('Change\Http\OAuth\OAuthManager', $applicationServices->getOAuthManager());
		$this->assertInstanceOf('Change\Events\EventManager', $applicationServices->getOAuthManager()->getEventManager());
	}
} 