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
	protected static function getNewEventManagerFactory(\Change\Application $application)
	{
		return new \Change\Events\EventManagerFactory($application);
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Events\EventManagerFactory $eventManagerFactory
	 * @return \Change\Services\ApplicationServices
	 */
	protected static function getNewApplicationServices(\Change\Application $application, \Change\Events\EventManagerFactory $eventManagerFactory)
	{
		return new \Change\Services\ApplicationServices($application, $eventManagerFactory);
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
	}

	/**
	 * @var \ChangeTests\Change\TestAssets\Application
	 */
	protected $application;

	/**
	 * @var \Change\Events\EventManagerFactory
	 */
	protected $eventManagerFactory;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

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
	 * @return \Change\Events\EventManagerFactory
	 */
	protected function getEventManagerFactory()
	{
		if (!$this->eventManagerFactory)
		{
			$this->eventManagerFactory = new \Change\Events\EventManagerFactory($this->getApplication());
		}
		return $this->eventManagerFactory;
	}

	/**
	 * @return array
	 */
	protected function getDefaultEventArguments()
	{
		$arguments = array('application' => $this->getApplication());
		$arguments['services'] = new \Zend\Stdlib\Parameters(array('applicationServices' => $this->getApplicationServices()));
		return $arguments;
	}

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	public function getApplicationServices()
	{
		if (!$this->applicationServices)
		{
			$this->applicationServices  = static::getNewApplicationServices($this->getApplication(), $this->getEventManagerFactory());
			$this->getEventManagerFactory()->addSharedService('applicationServices', $this->applicationServices);
		}
		return $this->applicationServices;
	}


	public function closeDbConnection()
	{
		$this->getApplicationServices()->getDbProvider()->closeConnection();
	}

	/**
	 * @param \Change\Application $app
	 * @param \Change\Events\EventManagerFactory $eventManagerFactory
	 * @return \Change\Services\ApplicationServices
	 */
	public static function initDb(&$app = null, &$eventManagerFactory = null)
	{
		if ($app === null)
		{
			$app = static::getNewApplication();
		}

		if ($eventManagerFactory === null)
		{
			$eventManagerFactory = static::getNewEventManagerFactory($app);
		}

		$appServices = static::getNewApplicationServices($app, $eventManagerFactory);
		$generator = new \Change\Db\Schema\Generator($app->getWorkspace(), $appServices->getDbProvider());
		$generator->generateSystemSchema();

		$appServices->getDbProvider()->getSchemaManager()->closeConnection();
		return $appServices;
	}

	/**
	 * @param \Change\Application $app
	 * @param \Change\Events\EventManagerFactory $eventManagerFactory
	 * @return \Change\Services\ApplicationServices
	 */
	public static function initDocumentsClasses(&$app = null, &$eventManagerFactory = null)
	{
		if ($app === null)
		{
			$app = static::getNewApplication();
		}

		if ($eventManagerFactory === null)
		{
			$eventManagerFactory = static::getNewEventManagerFactory($app);
		}

		$appServices = static::getNewApplicationServices($app, $eventManagerFactory);

		$compiler = new \Change\Documents\Generators\Compiler($app, $appServices);
		$compiler->generate();
	}

	/**
	 * @param \Change\Application $app
	 * @param \Change\Events\EventManagerFactory $eventManagerFactory
	 * @return \Change\Services\ApplicationServices
	 */
	public static function initDocumentsDb(&$app = null, &$eventManagerFactory = null)
	{
		if ($app === null)
		{
			$app = static::getNewApplication();
		}

		if ($eventManagerFactory === null)
		{
			$eventManagerFactory = static::getNewEventManagerFactory($app);
		}

		$appServices = static::getNewApplicationServices($app, $eventManagerFactory);

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
	 * @param \Change\Events\EventManagerFactory $eventManagerFactory
	 */
	public static function clearDB(&$app = null, &$eventManagerFactory = null)
	{
		if ($app === null)
		{
			$app = static::getNewApplication();
		}

		if ($eventManagerFactory === null)
		{
			$eventManagerFactory = static::getNewEventManagerFactory($app);
		}
		$appServices = static::getNewApplicationServices($app, $eventManagerFactory);
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
