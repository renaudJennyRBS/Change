<?php
namespace Rbs\Admin\Setup;

use Change\Http\OAuth\Consumer;
use Change\Http\OAuth\OAuthManager;
use Change\Plugins\PluginManager;

/**
 * @name \Rbs\Admin\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function attach($events, $plugin)
	{
		parent::attach($events, $plugin);
		$events->attach(PluginManager::EVENT_SETUP_SUCCESS, array($this, 'onSuccess'));

	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onSuccess(\Zend\EventManager\Event $event)
	{
		$manager = new \Rbs\Admin\Manager($event->getParam('applicationServices'), $event->getParam('documentServices'));
		$manager->getResources();
		$manager->dumpResources();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function initialize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$webBaseDirectory = $application->getWorkspace()->composeAbsolutePath($configuration->getEntry('Change/Install/webBaseDirectory'));
		if (is_dir($webBaseDirectory))
		{
			$srcPath = __DIR__ . '/Assets/admin.php';
			$content = \Change\Stdlib\File::read($srcPath);
			$content = str_replace('__DIR__', var_export($application->getWorkspace()->projectPath(), true), $content);
			\Change\Stdlib\File::write($webBaseDirectory . DIRECTORY_SEPARATOR . basename($srcPath), $content);
		}
		else
		{
			throw new \RuntimeException('Invalid document root path: ' . $webBaseDirectory .
			'. Check "Change/Install/webBaseDirectory" configuration entry.', 999999);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$OAuth = new OAuthManager();
		$OAuth->setApplicationServices($documentServices->getApplicationServices());
		$consumer = $OAuth->getConsumerByApplication('Rbs_Admin');
		if ($consumer)
		{
			return;
		}

		$consumer = new Consumer($OAuth->generateConsumerKey(), $OAuth->generateConsumerSecret());

		$applicationServices = $documentServices->getApplicationServices();
		$isb = $applicationServices->getDbProvider()->getNewStatementBuilder('Install::executeApplication');
		$fb = $isb->getFragmentBuilder();
		$isb->insert($fb->table($isb->getSqlMapping()->getOAuthApplicationTable()), $fb->column('application'),
			$fb->column('consumer_key'), $fb->column('consumer_secret'), $fb->column('timestamp_max_offset'),
			$fb->column('token_access_validity'), $fb->column('token_request_validity'), $fb->column('active'));
		$isb->addValues($fb->parameter('application'), $fb->parameter('consumer_key'), $fb->parameter('consumer_secret'),
			$fb->integerParameter('timestamp_max_offset'), $fb->parameter('token_access_validity'),
			$fb->parameter('token_request_validity'), $fb->booleanParameter('active'));
		$iq = $isb->insertQuery();
		$iq->bindParameter('application', 'Rbs_Admin');
		$iq->bindParameter('consumer_key', $consumer->getKey());
		$iq->bindParameter('consumer_secret', $consumer->getSecret());
		$iq->bindParameter('timestamp_max_offset', 60);
		$iq->bindParameter('token_access_validity', 'P10Y');
		$iq->bindParameter('token_request_validity', 'P1D');
		$iq->bindParameter('active', true);
		$iq->execute();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}