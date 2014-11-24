<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce;

use Change\Application;
use Change\Services\ApplicationServices;
use Zend\Di\Di;

/**
 * @name \Rbs\Commerce\CommerceServices
 */
class CommerceServices extends Di
{
	use \Change\Services\ServicesCapableTrait;

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
	 * @return array<alias => className>
	 */
	protected function loadInjectionClasses()
	{
		$classes = $this->getApplication()->getConfiguration('Rbs/Generic/Services');
		return is_array($classes) ? $classes : array();
	}

	/**
	 * @param Application $application
	 * @param ApplicationServices $applicationServices
	 */
	function __construct(Application $application, ApplicationServices $applicationServices)
	{
		$this->setApplication($application);
		$this->setApplicationServices($applicationServices);

		$definitionList = new \Zend\Di\DefinitionList(array());

		//Context : Application
		$contextClassName = $this->getInjectedClassName('Context', 'Rbs\Commerce\Std\Context');
		$classDefinition = $this->getClassDefinition($contextClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//PriceManager : Application, Context
		$priceManagerClassName = $this->getInjectedClassName('PriceManager', 'Rbs\Price\PriceManager');
		$classDefinition = $this->getClassDefinition($priceManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setContext', true)
				->addMethodParameter('setContext', 'context',array('type' => 'Context', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//StockManager : Application, Context, DbProvider, TransactionManager, DocumentManager, CollectionManager
		$stockManagerClassName = $this->getInjectedClassName('StockManager', 'Rbs\Stock\StockManager');
		$classDefinition = $this->getClassDefinition($stockManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setContext', true)
			->addMethodParameter('setContext', 'context',array('type' => 'Context', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//CatalogManager : Application, DbProvider, TransactionManager, DocumentManager, PriceManager, StockManager, AttributeManager
		$catalogManagerClassName = $this->getInjectedClassName('CatalogManager', 'Rbs\Catalog\CatalogManager');
		$classDefinition = $this->getClassDefinition($catalogManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setDbProvider', true)
			->addMethodParameter('setDbProvider', 'dbProvider', array('required' => true))
			->addMethod('setTransactionManager', true)
			->addMethodParameter('setTransactionManager', 'transactionManager', array('required' => true))
			->addMethod('setDocumentManager', true)
			->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true))
			->addMethod('setPriceManager', true)
			->addMethodParameter('setPriceManager', 'priceManager', array('type' => 'PriceManager', 'required' => true))
			->addMethod('setStockManager', true)
			->addMethodParameter('setStockManager', 'stockManager', array('type' => 'StockManager', 'required' => true))
			->addMethod('setAttributeManager', true)
			->addMethodParameter('setAttributeManager', 'attributeManager', array('type' => 'AttributeManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//ProductManager : Application
		$productManagerClassName = $this->getInjectedClassName('ProductManager', 'Rbs\Catalog\Product\ProductManager');
		$classDefinition = $this->getClassDefinition($productManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//CartManager : Application, StockManager, PriceManager, DocumentManager
		$cartManagerClassName = $this->getInjectedClassName('CartManager', 'Rbs\Commerce\Cart\CartManager');
		$classDefinition = $this->getClassDefinition($cartManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setStockManager', true)
				->addMethodParameter('setStockManager', 'stockManager', array('type' => 'StockManager', 'required' => true))
			->addMethod('setPriceManager', true)
				->addMethodParameter('setPriceManager', 'priceManager', array('type' => 'PriceManager', 'required' => true))
			->addMethod('setDocumentManager', true)
				->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true));
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

		//ProcessManager: Application, CartManager
		$processManagerClassName = $this->getInjectedClassName('ProcessManager', 'Rbs\Commerce\Process\ProcessManager');
		$classDefinition = $this->getClassDefinition($processManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setCartManager', true)
			->addMethodParameter('setCartManager', 'cartManager', array('type' => 'CartManager', 'required' => true));
		$definitionList->addDefinition($classDefinition);

		//OrderManager: Application, DocumentManager
		$orderManagerClassName = $this->getInjectedClassName('OrderManager', '\Rbs\Order\OrderManager');
		$classDefinition = $this->getClassDefinition($orderManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setDocumentManager', true)
			->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		//DiscountManager: Application
		$discountManagerClassName = $this->getInjectedClassName('DiscountManager', '\Rbs\Discount\DiscountManager');
		$classDefinition = $this->getClassDefinition($discountManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//PaymentManager : Application, TransactionManager, DocumentManager
		$paymentManagerClassName = $this->getInjectedClassName('PaymentManager', 'Rbs\Payment\PaymentManager');
		$classDefinition = $this->getClassDefinition($paymentManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setTransactionManager', true)
			->addMethodParameter('setTransactionManager', 'transactionManager', array('required' => true))
			->addMethod('setDocumentManager', true)
			->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		//WishlistManager: Application, DocumentManager
		$wishlistManagerClassName = $this->getInjectedClassName('WishlistManager', '\Rbs\Wishlist\WishlistManager');
		$classDefinition = $this->getClassDefinition($wishlistManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition->addMethod('setDocumentManager', true)
			->addMethodParameter('setDocumentManager', 'documentManager', array('required' => true));
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);

		$im = $this->instanceManager();

		$dbProvider = function() use ($applicationServices) {return $applicationServices->getDbProvider();};
		$transactionManager = function() use ($applicationServices) {return $applicationServices->getTransactionManager();};
		$documentManager = function() use ($applicationServices) {return $applicationServices->getDocumentManager();};
		$i18nManager = function() use ($applicationServices) {return $applicationServices->getI18nManager();};
		$collectionManager = function() use ($applicationServices) {return $applicationServices->getCollectionManager();};

		$im->addAlias('Context', $contextClassName, array('application' => $application));

		$im->addAlias('PriceManager', $priceManagerClassName,
			array('application' => $application, 'i18nManager' => $i18nManager, 'documentManager' => $documentManager));

		$im->addAlias('CatalogManager', $catalogManagerClassName,
			array('application' => $application, 'dbProvider' => $dbProvider,
				'transactionManager' => $transactionManager, 'documentManager' => $documentManager));

		$im->addAlias('ProductManager', $productManagerClassName,
			array('application' => $application));

		$im->addAlias('StockManager', $stockManagerClassName,
			array('application' => $application));

		$im->addAlias('CartManager', $cartManagerClassName,
			array('application' => $application, 'documentManager' => $documentManager));

		$im->addAlias('AttributeManager', $attributeManagerClassName,
			array('dbProvider' => $dbProvider, 'i18nManager' => $i18nManager,
				'documentManager' => $documentManager, 'collectionManager' => $collectionManager));

		$im->addAlias('OrderManager', $orderManagerClassName,
			array('application' => $application, 'documentManager' => $documentManager));

		$im->addAlias('ProcessManager', $processManagerClassName,
			array('application' => $application));

		$im->addAlias('PaymentManager', $paymentManagerClassName,
			array('application' => $application, 'transactionManager' => $transactionManager,
				'documentManager' => $documentManager));

		$im->addAlias('DiscountManager', $discountManagerClassName,
			array('application' => $application));

		$im->addAlias('WishlistManager', $wishlistManagerClassName,
			array('application' => $application, 'documentManager' => $documentManager));
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
	 * @return \Rbs\Stock\StockManager
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
	 * @return \Rbs\Order\OrderManager
	 */
	public function getOrderManager()
	{
		return $this->get('OrderManager');
	}

	/**
	 * @return \Rbs\Payment\PaymentManager
	 */
	public function getPaymentManager()
	{
		return $this->get('PaymentManager');
	}

	/**
	 * @return \Rbs\Discount\DiscountManager
	 */
	public function getDiscountManager()
	{
		return $this->get('DiscountManager');
	}

	/**
	 * @return \Rbs\Wishlist\WishlistManager
	 */
	public function getWishlistManager()
	{
		return $this->get('WishlistManager');
	}
}