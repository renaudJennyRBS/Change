<?php
namespace Rbs\Commerce\Services;

use Change\Application\ApplicationServices;
use Change\Events\EventsCapableTrait;
use Change\Documents\DocumentServices;
use Zend\Di\Definition\ClassDefinition;
use Zend\Di\DefinitionList;
use Zend\Di\Di;

/**
 * @name \Rbs\Commerce\Services\CommerceServices
 */
class CommerceServices extends Di implements \Zend\EventManager\EventsCapableInterface
{
	use EventsCapableTrait;

	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var \Rbs\Commerce\Interfaces\BillingArea
	 */
	protected $billingArea;

	/**
	 * @var string
	 */
	protected $zone;

	/**
	 * @var string
	 */
	protected $cartIdentifier;

	/**
	 * @var boolean
	 */
	protected $loaded = false;

	/**
	 * @param ApplicationServices $applicationServices
	 * @param DocumentServices $documentServices
	 */
	function __construct(ApplicationServices $applicationServices = null, DocumentServices $documentServices = null)
	{
		if ($applicationServices)
		{
			$this->setApplicationServices($applicationServices);
		}
		if ($documentServices)
		{
			$this->setDocumentServices($documentServices);
		}
		$dl = new DefinitionList(array());

		$this->registerTaxManager($dl);
		$this->registerPriceManager($dl);
		$this->registerCatalogManager($dl);
		$this->registerStockManager($dl);
		$this->registerCartManager($dl);
		parent::__construct($dl);

		$im = $this->instanceManager();
		$im->setParameters('Rbs\Price\Services\TaxManager', array('commerceServices' => $this));
		$im->setParameters('Rbs\Price\Services\PriceManager', array('commerceServices' => $this));
		$im->setParameters('Rbs\Catalog\Services\CatalogManager', array('commerceServices' => $this));
		$im->setParameters('Rbs\Stock\Services\StockManager', array('commerceServices' => $this));
		$im->setParameters('Rbs\Commerce\Cart\CartManager', array('commerceServices' => $this));
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerTaxManager($dl)
	{
		$cl = new ClassDefinition('Rbs\Price\Services\TaxManager');
		$cl->setInstantiator('__construct')
			->addMethod('setCommerceServices', true)
			->addMethodParameter('setCommerceServices', 'commerceServices',
				array('type' => 'Rbs\Commerce\Services\CommerceServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerCatalogManager($dl)
	{
		$cl = new ClassDefinition('Rbs\Catalog\Services\CatalogManager');
		$cl->setInstantiator('__construct')
			->addMethod('setCommerceServices', true)
			->addMethodParameter('setCommerceServices', 'commerceServices',
				array('type' => 'Rbs\Commerce\Services\CommerceServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerPriceManager($dl)
	{
		$cl = new ClassDefinition('Rbs\Price\Services\PriceManager');
		$cl->setInstantiator('__construct')
			->addMethod('setCommerceServices', true)
			->addMethodParameter('setCommerceServices', 'commerceServices',
				array('type' => 'Rbs\Commerce\Services\CommerceServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerStockManager($dl)
	{
		$cl = new ClassDefinition('Rbs\Stock\Services\StockManager');
		$cl->setInstantiator('__construct')
			->addMethod('setCommerceServices', true)
			->addMethodParameter('setCommerceServices', 'commerceServices',
				array('type' => 'Rbs\Commerce\Services\CommerceServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerCartManager($dl)
	{
		$cl = new ClassDefinition('Rbs\Commerce\Cart\CartManager');
		$cl->setInstantiator('__construct')
			->addMethod('setCommerceServices', true)
			->addMethodParameter('setCommerceServices', 'commerceServices',
				array('type' => 'Rbs\Commerce\Services\CommerceServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		$this->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
		return $this;
	}

	/**
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param DocumentServices $documentServices
	 * @return $this
	 */
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->applicationServices === null)
		{
			$this->setApplicationServices($documentServices->getApplicationServices());
		}
		return $this;
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @param \Callable|null $loaderCallback
	 * @return $this
	 */
	public function setLoaderCallback($loaderCallback)
	{
		$this->loaderCallback = $loaderCallback;
		return $this;
	}

	protected function load()
	{
		if (!$this->loaded)
		{
			$this->loaded = true;
			$em = $this->getEventManager();
			$em->trigger('load', $this, array('commerceServices' => $this));
		}
	}

	/**
	 * @param string $zone
	 * @return $this
	 */
	public function setZone($zone)
	{
		$this->zone = $zone;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getZone()
	{
		$this->load();
		return $this->zone;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @return $this
	 */
	public function setBillingArea(\Rbs\Commerce\Interfaces\BillingArea $billingArea)
	{
		$this->billingArea = $billingArea;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\BillingArea
	 */
	public function getBillingArea()
	{
		$this->load();
		return $this->billingArea;
	}

	/**
	 * @param string $cartIdentifier
	 * @return $this
	 */
	public function setCartIdentifier($cartIdentifier)
	{
		$this->cartIdentifier = $cartIdentifier;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCartIdentifier()
	{
		$this->load();
		return $this->cartIdentifier;
	}

	/**
	 * @return \Rbs\Price\Services\TaxManager
	 */
	public function getTaxManager()
	{
		return $this->get('Rbs\Price\Services\TaxManager');
	}

	/**
	 * @return \Rbs\Price\Services\PriceManager
	 */
	public function getPriceManager()
	{
		return $this->get('Rbs\Price\Services\PriceManager');
	}

	/**
	 * @return \Rbs\Catalog\Services\CatalogManager
	 */
	public function getCatalogManager()
	{
		return $this->get('Rbs\Catalog\Services\CatalogManager');
	}

	/**
	 * @return \Rbs\Stock\Services\StockManager
	 */
	public function getStockManager()
	{
		return $this->get('Rbs\Stock\Services\StockManager');
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartManager
	 */
	public function getCartManager()
	{
		return $this->get('Rbs\Commerce\Cart\CartManager');
	}

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return 'CommerceServices';
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		$config = $this->getApplicationServices()->getApplication()->getConfiguration();
		return $config->getEntry('Change/Events/CommerceServices', array());
	}
}