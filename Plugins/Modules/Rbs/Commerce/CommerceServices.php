<?php
namespace Rbs\Commerce;

use Change\Application;
use Change\Events\EventManagerFactory;
use Change\Events\EventsCapableTrait;
use Change\Services\ApplicationServices;
use Zend\Di\Di;

/**
 * @name \Rbs\Commerce\CommerceServices
 */
class CommerceServices extends Di
{
	use \Change\Services\ServicesCapableTrait;

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(\Change\Services\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return array<alias => className>
	 */
	protected function loadInjectionClasses()
	{
		$classes = $this->getApplication()->getConfiguration('Rbs/Generic/Services');
		return is_array($classes) ? $classes : array();
	}

	/**
	 * @param Application $application
	 * @param EventManagerFactory $eventManagerFactory
	 * @param ApplicationServices $applicationServices
	 */
	function __construct(Application $application, EventManagerFactory $eventManagerFactory, ApplicationServices $applicationServices)
	{
		$this->setApplication($application);
		$this->setEventManagerFactory($eventManagerFactory);
		$this->setApplicationServices($applicationServices);

		$definitionList = new \Zend\Di\DefinitionList(array());

		//Context : EventManagerFactory
		$contextClassName = $this->getInjectedClassName('Context', 'Rbs\Commerce\Std\Context');
		$classDefinition = $this->getClassDefinition($contextClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//PriceManager : EventManagerFactory, Application, ApplicationServices, Context
		$priceManagerClassName = $this->getInjectedClassName('PriceManager', 'Rbs\Price\PriceManager');
		$classDefinition = $this->getClassDefinition($priceManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setContext', true)
				->addMethodParameter('setContext', 'context',array('type' => 'Context', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//CatalogManager : DbProvider, TransactionManager, DocumentManager
		$catalogManagerClassName = $this->getInjectedClassName('CatalogManager', 'Rbs\Catalog\CatalogManager');
		$classDefinition = $this->getClassDefinition($catalogManagerClassName);
		$classDefinition->addMethod('setDbProvider', true)
			->addMethodParameter('setDbProvider', 'dbProvider', array('required' => true))
			->addMethod('setTransactionManager', true)
			->addMethodParameter('setTransactionManager', 'transactionManager', array('required' => true))
			->addMethod('setDocumentManager', true)
			->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);


		//ProductManager : EventManagerFactory
		$productManagerClassName = $this->getInjectedClassName('ProductManager', 'Rbs\Catalog\Product\ProductManager');
		$classDefinition = $this->getClassDefinition($productManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);


		//StockManager : Context, DbProvider, TransactionManager, DocumentManager, CollectionManager
		$stockManagerClassName = $this->getInjectedClassName('StockManager', 'Rbs\Stock\Services\StockManager');
		$classDefinition = $this->getClassDefinition($stockManagerClassName);
		$classDefinition->addMethod('setContext', true)
				->addMethodParameter('setContext', 'context',array('type' => 'Context', 'required' => true))
			->addMethod('setDbProvider', true)
				->addMethodParameter('setDbProvider', 'dbProvider', array('required' => true))
			->addMethod('setTransactionManager', true)
				->addMethodParameter('setTransactionManager', 'transactionManager', array('required' => true))
			->addMethod('setDocumentManager', true)
				->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true))
			->addMethod('setCollectionManager', true)
				->addMethodParameter('setCollectionManager', 'collectionManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		//CartManager : StockManager, PriceManager, EventManagerFactory, Logging
		$cartManagerClassName = $this->getInjectedClassName('CartManager', 'Rbs\Commerce\Cart\CartManager');
		$classDefinition = $this->getClassDefinition($cartManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setStockManager', true)
				->addMethodParameter('setStockManager', 'stockManager', array('type' => 'StockManager', 'required' => true))
			->addMethod('setPriceManager', true)
				->addMethodParameter('setPriceManager', 'priceManager', array('type' => 'PriceManager', 'required' => true))
			->addMethod('setLogging', true)
				->addMethodParameter('setLogging', 'logging', array('required' => true));
		$definitionList->addDefinition($classDefinition);


		//AttributeManager : DocumentManager, CollectionManager, DbProvider, I18nManager
		$attributeManagerClassName = $this->getInjectedClassName('AttributeManager', 'Rbs\Catalog\Attribute\AttributeManager');
		$classDefinition = $this->getClassDefinition($attributeManagerClassName);
		$classDefinition->addMethod('setDbProvider', true)
			->addMethodParameter('setDbProvider', 'dbProvider', array('required' => true))
			->addMethod('setDocumentManager', true)
			->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true))
			->addMethod('setCollectionManager', true)
			->addMethodParameter('setCollectionManager', 'collectionManager', array('required' => true))
			->addMethod('setI18nManager', true)
			->addMethodParameter('setI18nManager', 'i18nManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		//ProcessManager: CartManager, EventManagerFactory, Logging
		$processManagerClassName = $this->getInjectedClassName('ProcessManager', 'Rbs\Commerce\Process\ProcessManager');
		$classDefinition = $this->getClassDefinition($processManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setCartManager', true)
			->addMethodParameter('setCartManager', 'cartManager', array('type' => 'CartManager', 'required' => true))
			->addMethod('setLogging', true)
			->addMethodParameter('setLogging', 'logging', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		//PaymentManager : EventManagerFactory, TransactionManager, DocumentManager
		$paymentManagerClassName = $this->getInjectedClassName('PaymentManager', 'Rbs\Payment\PaymentManager');
		$classDefinition = $this->getClassDefinition($paymentManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setTransactionManager', true)
			->addMethodParameter('setTransactionManager', 'transactionManager', array('required' => true))
			->addMethod('setDocumentManager', true)
			->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);

		$im = $this->instanceManager();

		$dbProvider = function() use ($applicationServices) {return $applicationServices->getDbProvider();};
		$transactionManager = function() use ($applicationServices) {return $applicationServices->getTransactionManager();};
		$documentManager = function() use ($applicationServices) {return $applicationServices->getDocumentManager();};
		$i18nManager = function() use ($applicationServices) {return $applicationServices->getI18nManager();};
		$collectionManager = function() use ($applicationServices) {return $applicationServices->getCollectionManager();};
		$logging = function() use ($applicationServices) {return $applicationServices->getLogging();};

		$im->addAlias('Context', $contextClassName, array('eventManagerFactory' => $eventManagerFactory));

		$im->addAlias('PriceManager', $priceManagerClassName,
			array('eventManagerFactory' => $this->getEventManagerFactory(), 'i18nManager' => $i18nManager, 'documentManager' => $documentManager));

		$im->addAlias('CatalogManager', $catalogManagerClassName,
			array('dbProvider' => $dbProvider, 'transactionManager' => $transactionManager, 'documentManager' => $documentManager));

		$im->addAlias('ProductManager', $productManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory));

		$im->addAlias('StockManager', $stockManagerClassName,
			array('dbProvider' => $dbProvider, 'transactionManager' => $transactionManager,
				'documentManager' => $documentManager, 'collectionManager' => $collectionManager));

		$im->addAlias('CartManager', $cartManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory, 'logging' => $logging));

		$im->addAlias('AttributeManager', $attributeManagerClassName,
			array('dbProvider' => $dbProvider, 'i18nManager' => $i18nManager,
				'documentManager' => $documentManager, 'collectionManager' => $collectionManager));

		$im->addAlias('ProcessManager', $processManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory, 'logging' => $logging));
		
		$im->addAlias('PaymentManager', $paymentManagerClassName,
			array('eventManagerFactory' => $eventManagerFactory,
				'transactionManager' => $transactionManager, 'documentManager' => $documentManager));
	}

	/**
	 * Used for cart unserialize
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->getApplicationServices()->getDocumentManager();
	}

	/**
	 * @return \Rbs\Commerce\Std\Context
	 */
	public function getContext()
	{
		return $this->get('Context');
	}

	/**
	 * @return \Rbs\Price\PriceManager
	 */
	public function getPriceManager()
	{
		return $this->get('PriceManager');
	}

	/**
	 * @return \Rbs\Catalog\CatalogManager
	 */
	public function getCatalogManager()
	{
		return $this->get('CatalogManager');
	}

	/**
	 * @return \Rbs\Catalog\Product\ProductManager
	 */
	public function getProductManager()
	{
		return $this->get('ProductManager');
	}

	/**
	 * @return \Rbs\Stock\Services\StockManager
	 */
	public function getStockManager()
	{
		return $this->get('StockManager');
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartManager
	 */
	public function getCartManager()
	{
		return $this->get('CartManager');
	}

	/**
	 * @return \Rbs\Catalog\Attribute\AttributeManager
	 */
	public function getAttributeManager()
	{
		return $this->get('AttributeManager');
	}

	/**
	 * @return \Rbs\Commerce\Process\ProcessManager
	 */
	public function getProcessManager()
	{
		return $this->get('ProcessManager');
	}

	/**
	 * @return \Rbs\Payment\PaymentManager
	 */
	public function getPaymentManager()
	{
		return $this->get('PaymentManager');
	}
}