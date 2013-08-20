<?php
namespace Rbs\Commerce\Cart;

/**
 * @name \Rbs\Commerce\Cart\CartManager
 */
class CartManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'CartManager';

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
		$classNames = $config->getEntry('Change/Events/CartManager');
		return is_array($classNames) ? $classNames : array();
	}

	/**
	 * @param string $cartIdentifier
	 * @return \Rbs\Commerce\Interfaces\Cart|null
	 */
	public function getCartByIdentifier($cartIdentifier)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cartIdentifier' => $cartIdentifier, 'commerceServices' => $this->getCommerceServices()));
		$this->getEventManager()->trigger('getCartByIdentifier', $this, $args);
		if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Interfaces\Cart)
		{
			return $args['cart'];
		}
		return null;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Interfaces\Cart
	 */
	public function getNewCart()
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('commerceServices' => $this->getCommerceServices()));
		$this->getEventManager()->trigger('getNewCart', $this, $args);
		if (isset($args['cart']) && $args['cart'] instanceof \Rbs\Commerce\Interfaces\Cart)
		{
			return $args['cart'];
		}
		throw new \RuntimeException('Unable to get a new cart', 999999);
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 */
	public function saveCart($cart)
	{
		if (!$cart->isLocked())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('cart' => $cart, 'commerceServices' => $this->getCommerceServices()));
			$this->getEventManager()->trigger('saveCart', $this, $args);
		}
	}
}