<?php

namespace ChangeTests\Change\TestAssets;

/**
 * @name \ChangeTests\Change\TestAssets\TestCase
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
	/**
	 * @return \ChangeTests\Change\TestAssets\Application
	 */
	protected static function getNewApplication()
	{
		return new \ChangeTests\Change\TestAssets\Application();
	}

	/**
	 * @param \Change\Application $application
	 * @return \Change\Services\ApplicationServices
	 */
	protected static function getNewApplicationServices(\Change\Application $application)
	{
		return new \Change\Services\ApplicationServices($application);
	}


	protected function tearDown()
	{
		parent::tearDown();

		if ($this->applicationServices)
		{
			$this->closeDbConnection();
			$this->applicationServices->shutdown();
			$this->applicationServices = null;
		}

		$this->application = null;
	}

	/**
	 * @var \ChangeTests\Change\TestAssets\Application
	 */
	protected $application;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Rbs\Commerce\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @var \Rbs\Generic\GenericServices
	 */
	protected $genericServices;

	/**
	 * @return \ChangeTests\Change\TestAssets\Application
	 */
	protected function getApplication()
	{
		if (!$this->application)
		{
			$this->application = static::getNewApplication();
		}
		return $this->application;
	}

	/**
	 * @return array
	 */
	protected function getDefaultEventArguments()
	{
		$arguments = array('application' => $this->getApplication());
		$services = new \Zend\Stdlib\Parameters();
		$services->set('applicationServices', $this->getApplicationServices());
		$services->set('genericServices', $this->genericServices);
		$services->set('commerceServices', $this->commerceServices);
		$arguments['services'] = $services;
		return $arguments;
	}

	/**
	 * @param \Zend\EventManager\SharedEventManager $sharedEventManager
	 */
	protected function attachSharedListener(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{

	}

	protected function attachCommerceServicesSharedListener(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		$sharedEventManager->attach('*', '*', function($event)
		{
			if ($event instanceof \Change\Events\Event)
			{
				if ($this->commerceServices === null) {

					$this->commerceServices = new \Rbs\Commerce\CommerceServices($event->getApplication(), $event->getApplicationServices());
				}
				$event->getServices()->set('commerceServices', $this->commerceServices);
			}
			return true;
		}, 9997);
	}

	protected function attachGenericServicesSharedListener(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		$sharedEventManager->attach('*', '*', function($event) {
			if ($event instanceof \Change\Events\Event)
			{
				if ($this->genericServices === null) {

					$this->genericServices = new \Rbs\Generic\GenericServices($event->getApplication(), $event->getApplicationServices());
				}
				$event->getServices()->set('genericServices', $this->genericServices);
			}
			return true;
		}, 9998);
	}


	protected function initServices(\Change\Application $application)
	{
		$this->attachSharedListener($application->getSharedEventManager());
		$evt = $application->getNewEventManager('PhpUnit');
		$evt->attach('initServices', [$this, 'onInitServices']);
		$evt->trigger('initServices', $application);
	}

	public function onInitServices(\Change\Events\Event $event)
	{
		$this->applicationServices = $event->getApplicationServices();
	}

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	public function getApplicationServices()
	{
		if (!$this->applicationServices)
		{
			$this->initServices($this->getApplication());
		}
		return $this->applicationServices;
	}


	public function closeDbConnection()
	{
		$this->getApplicationServices()->getDbProvider()->closeConnection();
	}

	/**
	 * @param \Change\Application $app
	 * @return \Change\Services\ApplicationServices
	 */
	public static function initDb(&$app = null)
	{
		if ($app === null)
		{
			$app = static::getNewApplication();
		}

		$appServices = static::getNewApplicationServices($app);
		$generator = new \Change\Db\Schema\Generator($app->getWorkspace(), $appServices->getDbProvider());
		$generator->generateSystemSchema();

		$appServices->getDbProvider()->getSchemaManager()->closeConnection();
		return $appServices;
	}

	/**
	 * @param \Change\Application $app
	 * @return \Change\Services\ApplicationServices
	 */
	public static function initDocumentsClasses(&$app = null)
	{
		if ($app === null)
		{
			$app = static::getNewApplication();
		}

		$appServices = static::getNewApplicationServices($app);

		$compiler = new \Change\Documents\Generators\Compiler($app, $appServices);
		$compiler->generate();
	}

	/**
	 * @param \Change\Application $app
	 * @return \Change\Services\ApplicationServices
	 */
	public static function initDocumentsDb(&$app = null)
	{
		if ($app === null)
		{
			$app = static::getNewApplication();
		}

		$appServices = static::getNewApplicationServices($app);

		$generator = new \Change\Db\Schema\Generator($app->getWorkspace(), $appServices->getDbProvider());
		$generator->generateSystemSchema();

		$compiler = new \Change\Documents\Generators\Compiler($app, $appServices);
		$compiler->generate();

		$generator->generatePluginsSchema();
		$appServices->getDbProvider()->getSchemaManager()->closeConnection();

		return $appServices;
	}

	/**
	 * @param \Change\Application $app
	 */
	public static function clearDB(&$app = null)
	{
		if ($app === null)
		{
			$app = static::getNewApplication();
		}

		$appServices = static::getNewApplicationServices($app);
		$dbp = $appServices->getDbProvider();
		$dbp->getSchemaManager()->clearDB();
		$dbp->getSchemaManager()->closeConnection();
	}

	/**
	 * Returns a new "loaded" instance of a document that can't be save in DB.
	 * @param string $modelName
	 * @param integer $id
	 * @param integer $persistentState
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getNewReadonlyDocument($modelName, $id, $persistentState = \Change\Documents\AbstractDocument::STATE_LOADED)
	{
		$dm = $this->getApplicationServices()->getDocumentManager();
		$doc = $dm->getNewDocumentInstanceByModelName($modelName);
		if ($doc instanceof \Change\Documents\Interfaces\Localizable)
		{
			$doc->setRefLCID($dm->getLCID());
			$doc->getCurrentLocalization();
		}
		$doc->initialize($id, $persistentState);
		return $doc;
	}
}
