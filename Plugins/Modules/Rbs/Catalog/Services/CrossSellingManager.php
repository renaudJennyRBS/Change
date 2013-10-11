<?php
namespace Rbs\Catalog\Services;

/**
 * @name \Rbs\Catalog\Services\CrossSellingManager
 */
class CrossSellingManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'CrossSellingManager';

	/**
	 * @var \Rbs\Commerce\Services\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 */
	public function setCommerceServices(\Rbs\Commerce\Services\CommerceServices $commerceServices)
	{
		$this->commerceServices = $commerceServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($commerceServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
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
	protected function getDocumentServices()
	{
		return $this->commerceServices->getDocumentServices();
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->commerceServices->getApplicationServices();
	}

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
		$config = $this->getApplicationServices()->getApplication()->getConfiguration();
		$classNames = $config->getEntry('Change/Events/CrossSellingManager');
		return is_array($classNames) ? $classNames : array();
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
		$args = $em->prepareArgs(array('product' => $product, 'csParameters' => $csParameters, 'commerceServices' => $this->getCommerceServices()));
		$this->getEventManager()->trigger('getCrossSellingForProduct', $this, $args);
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
		$args = $em->prepareArgs(array('cart' => $cart, 'csParameters' => $csParameters, 'commerceServices' => $this->getCommerceServices()));
		$this->getEventManager()->trigger('getCrossSellingForCart', $this, $args);
		return $args['csProducts'];
	}
}