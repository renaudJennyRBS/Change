<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Product;

/**
 * @name \Rbs\Catalog\Product\ProductManager
 */
class ProductManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'ProductManager';
	const EVENT_GET_CROSS_SELLING_FOR_PRODUCT = 'getCrossSellingForProduct';
	const EVENT_GET_CROSS_SELLING_FOR_CART = 'getCrossSellingForCart';

	const LAST_PRODUCT = 'LAST_PRODUCT';
	const RANDOM_PRODUCT = 'RANDOM_PRODUCT';
	const MOST_EXPENSIVE_PRODUCT = 'MOST_EXPENSIVE_PRODUCT';

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Commerce/Events/ProductManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_GET_CROSS_SELLING_FOR_PRODUCT, [$this, 'onDefaultGetCrossSellingProductsByProduct'], 5);
		$eventManager->attach(static::EVENT_GET_CROSS_SELLING_FOR_CART, [$this, 'onDefaultCrossSellingProductsByCart'], 5);
	}

	/**
	 * Gets Cross Selling info for a product using parameters array
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $csParameters
	 * @return \Rbs\Catalog\Product\ProductItem[]
	 */
	public function getCrossSellingForProduct($product, $csParameters)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('product' => $product, 'csParameters' => $csParameters));
		$this->getEventManager()->trigger(static::EVENT_GET_CROSS_SELLING_FOR_PRODUCT, $this, $args);
		if (isset($args['csProducts']))
		{
			return $args['csProducts'];
		}
		return array();
	}

	/**
	 * Gets Cross Selling info for a cart using parameters array
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $csParameters
	 * @return \Rbs\Catalog\Product\ProductItem[]
	 */
	public function getCrossSellingForCart($cart, $csParameters)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cart' => $cart, 'csParameters' => $csParameters));
		$this->getEventManager()->trigger(static::EVENT_GET_CROSS_SELLING_FOR_CART, $this, $args);
		if (isset($args['csProducts']))
		{
			return $args['csProducts'];
		}
		return array();
	}

	/**
	 * Gets Cross Selling products for a product using parameters array
	 * $event requires two parameters: product and csParameters
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCrossSellingProductsByProduct(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$products = array();
		$product = $event->getParam('product');
		$parameters = $event->getParam('csParameters');
		if (isset($parameters['crossSellingType']) && isset($parameters['urlManager']))
		{
			// Gets CrossSellingProductList.
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Catalog_CrossSellingProductList');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->eq('product', $product), $pb->eq('crossSellingType', $parameters['crossSellingType']));
			$crossSellingList = $query->getFirstDocument();

			/* @var $crossSellingList \Rbs\Catalog\Documents\CrossSellingProductList */
			if ($crossSellingList)
			{
				$documentManager = $applicationServices->getDocumentManager();
				$query = $documentManager->getNewQuery('Rbs_Catalog_Product');
				$query->andPredicates($query->published());
				$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductListItem', 'product');
				$subQuery->andPredicates(
					$subQuery->eq('productList', $crossSellingList),
					$subQuery->activated()
				);
				$subQuery->addOrder('position', true);
				$query->addOrder($crossSellingList->getProductSortOrder(), $crossSellingList->getProductSortDirection());

				/* @var $urlManager \Change\Http\Web\UrlManager */
				$urlManager = $parameters['urlManager'];
				foreach ($query->getDocuments() as $p)
				{
					/* @var $p \Rbs\Catalog\Documents\Product */
					$url = $urlManager->getCanonicalByDocument($p)->toString();
					$row = array('id' => $p->getId(), 'url' => $url);
					$visual = $p->getFirstVisual();
					$row['visual'] = $visual ? $visual->getPath() : null;

					$productPresentation = $p->getPresentation($commerceServices, $commerceServices->getContext()->getWebStore()->getId(), $urlManager);
					if ($productPresentation)
					{
						$productPresentation->evaluate();
						$row['productPresentation'] = $productPresentation;
					}

					$products[] = (new \Rbs\Catalog\Product\ProductItem($row))->setDocumentManager($documentManager);
				}
			}
			$event->setParam('csProducts', $products);
		}
	}

	/**
	 * Gets Cross Selling products for a product using parameters array
	 * $event requires two parameters: cart and csParameters
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultCrossSellingProductsByCart(\Change\Events\Event $event)
	{
		$parameters = $event->getParam('csParameters');
		$product = $this->getProductFromCart($event);

		if ($product && isset($parameters['crossSellingType']))
		{
			$event->setParam('product', $product);
			$this->onDefaultGetCrossSellingProductsByProduct($event);
		}
	}

	/**
	 * Choose a product form cart according to strategy.
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Catalog\Documents\Product|null
	 */
	protected function getProductFromCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$parameters = $event->getParam('csParameters');
		$strategy = $parameters['productChoiceStrategy'];
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && isset($strategy))
		{
			$line = null;
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			// Let's be optimistic: cart line key = productId.
			switch($strategy)
			{
				case ProductManager::LAST_PRODUCT:
					$lineCount = count($cart->getLines());
					if ($lineCount)
					{
						$line = $cart->getLineByNumber($lineCount);
					}
					break;
				case ProductManager::RANDOM_PRODUCT:
					$lineCount = count($cart->getLines());
					if ($lineCount)
					{
						$line = $cart->getLineByNumber(rand(1, $lineCount));
					}
					break;
				case ProductManager::MOST_EXPENSIVE_PRODUCT:
					$lines = $cart->getLines();
					usort($lines, array($this, "mostExpensiveUnitPrice"));
					$line = $lines[0];
					break;
			}
			if ($line)
			{
				/* @var $line \Rbs\Commerce\Cart\CartLine */
				return $documentManager->getDocumentInstance($line->getKey(), 'Rbs_Catalog_Product');
			}
		}

		return null;
	}

	/**
	 * usort comparison function.
	 * @param \Rbs\Commerce\Cart\CartLine $line1
	 * @param \Rbs\Commerce\Cart\CartLine $line2
	 * @return \Rbs\Catalog\Documents\Product
	 */
	protected function mostExpensiveUnitPrice($line1, $line2)
	{
		$price1 = $line1->getUnitAmountWithTaxes();
		$price2 = $line2->getUnitAmountWithTaxes();
		if ($price1 == $price2) {
			return 0;
		}
		return ($price1 > $price2) ? -1 : 1;
	}
}