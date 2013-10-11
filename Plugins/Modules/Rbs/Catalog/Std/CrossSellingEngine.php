<?php
namespace Rbs\Catalog\Std;

/**
 * @name \Rbs\Catalog\Std\CrossSellingEngine
 */
class CrossSellingEngine
{
	/**
	 * @var \Rbs\Commerce\Services\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return \Rbs\Catalog\Std\CrossSellingEngine
	 */
	public function __construct(\Rbs\Commerce\Services\CommerceServices $commerceServices)
	{
		$this->commerceServices = $commerceServices;
	}

	/**
	 * @return \Rbs\Commerce\Services\CommerceServices
	 */
	public function getCommerceServices()
	{
		return $this->commerceServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->getCommerceServices()->getDocumentServices();
	}

	/**
	 * Gets Cross Selling products for a product using parameters array
	 * $event requires two parameters : product and csParameters
	 * @api
	 * @param \Zend\EventManager\Event $event
	 * @return \Rbs\Catalog\Std\ProductItem[]
	 */
	public function getCrossSellingProductsByProduct(\Zend\EventManager\Event $event)
	{
		$documentServices = $this->getDocumentServices();
		$commerceServices = $this->getCommerceServices();
		$products = array();
		$product = $event->getParam('product');
		$parameters = $event->getParam('csParameters');
		if (isset($parameters['crossSellingType']))
		{
			//Gets CrossSellingProductList
			$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Catalog_CrossSellingProductList');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->eq('product', $product), $pb->eq('crossSellingType', $parameters['crossSellingType']));
			$crossSellingList = $query->getFirstDocument();

			/* @var $crossSellingList \Rbs\Catalog\Documents\CrossSellingProductList */
			if($crossSellingList)
			{
				$documentManager = $documentServices->getDocumentManager();
				$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Catalog_Product');
				$query->andPredicates($query->published());
				$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductListItem', 'product');
				$subQuery->andPredicates(
					$subQuery->eq('productList', $crossSellingList),
					$subQuery->activated()
				);
				$subQuery->addOrder('position', true);
				$query->addOrder($crossSellingList->getProductSortOrder(), $crossSellingList->getProductSortDirection());

				foreach($query->getDocuments() as $p)
				{
					/* @var $p \Rbs\Catalog\Documents\Product */
					$website = $p->getCanonicalSection()->getWebsite();
					$lcid = $documentServices->getApplicationServices()->getI18nManager()->getLCID();
					$url = $website->getUrlManager($lcid)->getCanonicalByDocument($p)->toString();
					$row = array('id' => $p->getId(), 'url' => $url);
					$visual = $p->getFirstVisual();
					$row['visual'] = $visual ? $visual->getPath() : null;

					$productPresentation = $p->getPresentation($commerceServices, $commerceServices->getWebStore()->getId());
					if ($productPresentation)
					{
						$productPresentation->evaluate();
						$row['productPresentation'] = $productPresentation;
					}

					$products[] = (new \Rbs\Catalog\Std\ProductItem($row))->setDocumentManager($documentManager);
				}
			}
		}

		return $products;
	}

	/**
	 * Gets Cross Selling products for a product using parameters array
	 * $event requires two parameters : cart and csParameters
	 * @api
	 * @param \Zend\EventManager\Event $event
	 * @return \Rbs\Catalog\Std\ProductItem[]
	 */
	public function getCrossSellingProductsByCart(\Zend\EventManager\Event $event)
	{
		$products = array();
		$cart = $event->getParam('cart');
		$parameters = $event->getParam('csParameters');
		$product = $this->getProductFromCart($cart, $parameters['productChoiceStrategy']);

		if ($product && isset($parameters['crossSellingType']))
		{
			$event->setParam('product', $product);
			$products = $this->getCrossSellingProductsByProduct($event);
		}

		return $products;
	}

	/**
	 * Choose a product form cart according to strategy
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param string $strategy
	 * @return \Rbs\Catalog\Documents\Product|null
	 */
	protected function getProductFromCart(\Rbs\Commerce\Cart\Cart $cart, $strategy)
	{
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && isset($strategy))
		{
			$line = null;
			$documentManager = $this->getDocumentServices()->getDocumentManager();
			/* Let's be optimistic : cartline key = productId */
			switch($strategy)
			{
				case 'LAST_PRODUCT':
					$lineCount = count($cart->getLines());
					if ($lineCount)
					{
						$line = $cart->getLineByNumber($lineCount);
					}
					echo $line->getNumber() . " " . $line->getDesignation();
					break;
				case 'RANDOM_PRODUCT':
					$lineCount = count($cart->getLines());
					if ($lineCount)
					{
						$line = $cart->getLineByNumber(rand(1, $lineCount));
					}
					echo $line->getNumber() . " " . $line->getDesignation();
					break;
				case 'MOST_EXPENSIVE_PRODUCT':
					$lines = $cart->getLines();
					usort($lines, array($this, "mostExpensiveUnitPrice"));
					$line = $lines[0];
					echo $line->getNumber() . " " . $line->getDesignation();
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
	 * usort comparison function
	 * @api
	 * @param \Rbs\Commerce\Cart\CartLine $line1
	 * @param \Rbs\Commerce\Cart\CartLine $line2
	 * @return \Rbs\Catalog\Documents\Product
	 */
	function mostExpensiveUnitPrice($line1, $line2)
	{
		$price1 = $line1->getUnitPriceValueWithTax();
		$price2 = $line2->getUnitPriceValueWithTax();
		echo $price1 ." vs. " .$price2;
		if ($price1 == $price2) {
			return 0;
		}
		return ($price1 > $price2) ? -1 : 1;
	}
}

