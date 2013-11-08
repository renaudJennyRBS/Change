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
	 * @return \Change\Services\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
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
	function __construct(Application $application, EventManagerFactory $eventManagerFactory,
		ApplicationServices $applicationServices)
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

		//TaxManager : Application, ApplicationServices, Context
		$taxManagerClassName = $this->getInjectedClassName('TaxManager', 'Rbs\Price\Services\TaxManager');
		$classDefinition = $this->getDefaultClassDefinition($taxManagerClassName);
		$classDefinition->addMethod('setContext', true)
			->addMethodParameter('setContext', 'context',array('type' => 'Context', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//PriceManager : EventManagerFactory, Application, ApplicationServices, Context
		$priceManagerClassName = $this->getInjectedClassName('PriceManager', 'Rbs\Price\Services\PriceManager');
		$classDefinition = $this->getDefaultClassDefinition($priceManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setContext', true)
			->addMethodParameter('setContext', 'context',array('type' => 'Context', 'required' => true));
		$definitionList->addDefinition($classDefinition);


		//CatalogManager : Application, ApplicationServices
		$catalogManagerClassName = $this->getInjectedClassName('CatalogManager', 'Rbs\Catalog\Services\CatalogManager');
		$classDefinition = $this->getDefaultClassDefinition($catalogManagerClassName);
		$definitionList->addDefinition($classDefinition);


		//CrossSellingManager : EventManagerFactory, Application, ApplicationServices
		$crossSellingManagerClassName = $this->getInjectedClassName('CrossSellingManager', 'Rbs\Catalog\Services\CrossSellingManager');
		$classDefinition = $this->getClassDefinition($crossSellingManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);


		//StockManager : Application, ApplicationServices, Context
		$stockManagerClassName = $this->getInjectedClassName('StockManager', 'Rbs\Stock\Services\StockManager');
		$classDefinition = $this->getDefaultClassDefinition($stockManagerClassName);
		$classDefinition->addMethod('setContext', true)
			->addMethodParameter('setContext', 'context',array('type' => 'Context', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//CartManager : EventManagerFactory, Application, ApplicationServices, Context
		$cartManagerClassName = $this->getInjectedClassName('CartManager', 'Rbs\Commerce\Cart\CartManager');
		$classDefinition = $this->getDefaultClassDefinition($cartManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$classDefinition->addMethod('setStockManager', true)
				->addMethodParameter('setStockManager', 'stockManager',array('type' => 'StockManager', 'required' => true))
			->addMethod('setPriceManager', true)
				->addMethodParameter('setPriceManager', 'priceManager',array('type' => 'PriceManager', 'required' => true))
			->addMethod('setTaxManager', true)
				->addMethodParameter('setTaxManager', 'taxManager',array('type' => 'TaxManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);

		$im = $this->instanceManager();

		$defaultParameters = array('application' => $this->getApplication(),
			'applicationServices' => $this->getApplicationServices(),
			'eventManagerFactory' => $this->getEventManagerFactory());

		$im->addAlias('Context', $contextClassName, array('eventManagerFactory' => $this->getEventManagerFactory()));
		$im->addAlias('TaxManager', $taxManagerClassName, $defaultParameters);
		$im->addAlias('PriceManager', $priceManagerClassName, $defaultParameters);
		$im->addAlias('CatalogManager', $catalogManagerClassName, $defaultParameters);
		$im->addAlias('CrossSellingManager', $crossSellingManagerClassName, $defaultParameters);
		$im->addAlias('StockManager', $stockManagerClassName, $defaultParameters);
		$im->addAlias('CartManager', $cartManagerClassName, $defaultParameters);
	}


	/**
	 * @return \Rbs\Commerce\Std\Context
	 */
	public function getContext()
	{
		return $this->get('Context');
	}

	/**
	 * @return \Rbs\Price\Services\TaxManager
	 */
	public function getTaxManager()
	{
		return $this->get('TaxManager');
	}

	/**
	 * @return \Rbs\Price\Services\PriceManager
	 */
	public function getPriceManager()
	{
		return $this->get('PriceManager');
	}

	/**
	 * @return \Rbs\Catalog\Services\CatalogManager
	 */
	public function getCatalogManager()
	{
		return $this->get('CatalogManager');
	}

	/**
	 * @return \Rbs\Catalog\Services\CrossSellingManager
	 */
	public function getCrossSellingManager()
	{
		return $this->get('CrossSellingManager');
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
}