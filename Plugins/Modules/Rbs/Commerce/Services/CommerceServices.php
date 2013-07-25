<?php
namespace Rbs\Commerce\Services;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Zend\Di\Definition\ClassDefinition;
use Zend\Di\DefinitionList;
use Zend\Di\Di;

/**
 * @name \Rbs\Commerce\Services\CommerceServices
 */
class CommerceServices extends Di
{
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
	 * @param ApplicationServices $applicationServices
	 * @param DocumentServices $documentServices
	 */
	function __construct($applicationServices, $documentServices)
	{
		$this->applicationServices = $applicationServices;
		$this->documentServices = $documentServices;

		$dl = new DefinitionList(array());

		$this->registerTaxManager($dl);
		$this->registerPriceManager($dl);
		$this->registerCatalogManager($dl)
		;
		parent::__construct($dl);

		$im = $this->instanceManager();
		$im->setParameters('Rbs\Price\Services\TaxManager', array('commerceServices' => $this));
		$im->setParameters('Rbs\Price\Services\PriceManager', array('commerceServices' => $this));
		$im->setParameters('Rbs\Catalog\Services\CatalogManager', array('commerceServices' => $this));
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
	 * @param ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
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
		return $this->billingArea;
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
}