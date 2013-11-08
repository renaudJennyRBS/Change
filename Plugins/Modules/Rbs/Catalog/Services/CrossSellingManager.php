<?php
namespace Rbs\Catalog\Services;

/**
 * @name \Rbs\Catalog\Services\CrossSellingManager
 */
class CrossSellingManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'CrossSellingManager';
	const EVENT_GET_CROSS_SELLING_FOR_PRODUCT = 'getCrossSellingForProduct';
	const EVENT_GET_CROSS_SELLING_FOR_CART = 'getCrossSellingForCart';
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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Commerce/Events/CrossSellingManager');
	}

	/**
	 * Gets Cross Selling info for a product using parameters array
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $csParameters
	 * @return \Rbs\Catalog\Std\ProductItem[]
	 */
	public function getCrossSellingForProduct($product, $csParameters)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('product' => $product, 'csParameters' => $csParameters));
		$this->getEventManager()->trigger(static::EVENT_GET_CROSS_SELLING_FOR_PRODUCT, $this, $args);
		return $args['csProducts'];
	}

	/**
	 * Gets Cross Selling info for a cart using parameters array
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $csParameters
	 * @return \Rbs\Catalog\Std\ProductItem[]
	 */
	public function getCrossSellingForCart($cart, $csParameters)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cart' => $cart, 'csParameters' => $csParameters));
		$this->getEventManager()->trigger(static::EVENT_GET_CROSS_SELLING_FOR_CART, $this, $args);
		return $args['csProducts'];
	}
}