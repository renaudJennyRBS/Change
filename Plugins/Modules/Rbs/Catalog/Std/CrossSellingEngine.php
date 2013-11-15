<?php
namespace Rbs\Catalog\Std;

/**
 * @name \Rbs\Catalog\Std\CrossSellingEngine
 */
class CrossSellingEngine
{
	const LAST_PRODUCT = 'LAST_PRODUCT';
	const RANDOM_PRODUCT = 'RANDOM_PRODUCT';
	const MOST_EXPENSIVE_PRODUCT = 'MOST_EXPENSIVE_PRODUCT';

	/**
	 * Gets Cross Selling products for a product using parameters array
	 * $event requires two parameters : product and csParameters
	 * @api
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Catalog\Std\ProductItem[]
	 */
	public function getCrossSellingProductsByProduct(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$products = array();
		$product = $event->getParam('product');
		$parameters = $event->getParam('csParameters');
		if (isset($parameters['crossSellingType']))
		{
			//Gets CrossSellingProductList
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Catalog_CrossSellingProductList');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->eq('product', $product), $pb->eq('crossSellingType', $parameters['crossSellingType']));
			$crossSellingList = $query->getFirstDocument();

			/* @var $crossSellingList \Rbs\Catalog\Documents\CrossSellingProductList */
			if($crossSellingList)
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

				foreach($query->getDocuments() as $p)
				{
					/* @var $p \Rbs\Catalog\Documents\Product */
					$website = $p->getCanonicalSection()->getWebsite();
					$lcid = $applicationServices->getI18nManager()->getLCID();
					$url = $website->getUrlManager($lcid)->getCanonicalByDocument($p)->toString();
					$row = array('id' => $p->getId(), 'url' => $url);
					$visual = $p->getFirstVisual();
					$row['visual'] = $visual ? $visual->getPath() : null;

					$productPresentation = $p->getPresentation($commerceServices, $commerceServices->getContext()->getWebStore()->getId());
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
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Catalog\Std\ProductItem[]
	 */
	public function getCrossSellingProductsByCart(\Change\Events\Event $event)
	{
		$products = array();
		$parameters = $event->getParam('csParameters');
		$product = $this->getProductFromCart($event);

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
			/* Let's be optimistic : cartline key = productId */
			switch($strategy)
			{
				case self::LAST_PRODUCT:
					$lineCount = count($cart->getLines());
					if ($lineCount)
					{
						$line = $cart->getLineByNumber($lineCount);
					}
					break;
				case self::RANDOM_PRODUCT:
					$lineCount = count($cart->getLines());
					if ($lineCount)
					{
						$line = $cart->getLineByNumber(rand(1, $lineCount));
					}
					break;
				case self::MOST_EXPENSIVE_PRODUCT:
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
		if ($price1 == $price2) {
			return 0;
		}
		return ($price1 > $price2) ? -1 : 1;
	}
}

