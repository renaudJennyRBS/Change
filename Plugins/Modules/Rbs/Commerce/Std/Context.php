<?php
namespace Rbs\Commerce\Std;

/**
* @name \Rbs\Commerce\Std\Context
*/
class Context implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	/**
	 * @var \Rbs\Store\Documents\WebStore
	 */
	protected $webStore;

	/**
	 * @var \Rbs\Price\Tax\BillingAreaInterface
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
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return 'CommerceContext';
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Commerce/Events/CommerceContext');
	}

	public function load()
	{
		if (!$this->loaded)
		{
			$this->loaded = true;
			$em = $this->getEventManager();
			$em->trigger('load', $this);
		}
	}

	public function save()
	{
		$this->loaded = true;
		$em = $this->getEventManager();
		$em->trigger('save', $this);
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @return $this
	 */
	public function setWebStore($webStore)
	{
		$this->webStore = $webStore;
		return $this;
	}

	/**
	 * @return \Rbs\Store\Documents\WebStore
	 */
	public function getWebStore()
	{
		$this->load();
		return $this->webStore;
	}

	/**
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @return $this
	 */
	public function setBillingArea(\Rbs\Price\Tax\BillingAreaInterface $billingArea)
	{
		$this->billingArea = $billingArea;
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\BillingAreaInterface
	 */
	public function getBillingArea()
	{
		$this->load();
		return $this->billingArea;
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
} 