<?php
namespace Rbs\Admin\Setup;

use Change\Http\Rest\OAuth\Consumer;
use Change\Http\Rest\OAuth\OAuth;

/**
 * @name \Change\Generic\Setup\Install
 */
class Install
{
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
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application)
	{
		/* @var $config \Change\Configuration\EditableConfiguration */
		$config = $application->getConfiguration();

		$config->addPersistentEntry('Change/Events/Http/Rest/Rbs_Admin',
			'\\Rbs\\Admin\\Http\\Rest\\ListenerAggregate');

		$projectPath = $application->getWorkspace()->projectPath();
		$documentRootPath = $config->getEntry('Change/Install/documentRootPath', $projectPath);

		if (is_dir($documentRootPath))
		{
			$srcPath = __DIR__ . '/Assets/admin.php';
			$content = \Change\Stdlib\File::read($srcPath);
			$content = str_replace('__DIR__', var_export($projectPath, true), $content);
			\Change\Stdlib\File::write($documentRootPath . DIRECTORY_SEPARATOR . basename($srcPath), $content);
		}
		else
		{
			throw new \RuntimeException('Invalid document root path: ' . $documentRootPath .
			'. Check "Change/Install/documentRootPath" configuration entry.', 999999);
		}

		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Admin', '\\Rbs\\Admin\\Register');
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
		$OAuth = new OAuth();
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