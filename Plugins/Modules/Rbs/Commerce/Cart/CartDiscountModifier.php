<?php
namespace Rbs\Commerce\Cart;

/**
 * @name \Rbs\Commerce\Cart\CartDiscountModifier
 */
class CartDiscountModifier implements \Rbs\Commerce\Process\ModifierInterface
{
	/**
	 * @var \Rbs\Commerce\Cart\Cart
	 */
	protected $cart;

	/**
	 * @var \Rbs\Discount\Documents\Discount
	 */
	protected $discount;

	/**
	 * @var \Rbs\Price\PriceInterface
	 */
	protected $price;

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $taxes = [];

	/**
	 * @var string[]
	 */
	protected $lineKeys = [];

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @param \Rbs\Discount\Documents\Discount $discount
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Price\PriceManager $priceManager
	 */
	function __construct(\Rbs\Discount\Documents\Discount $discount, \Rbs\Commerce\Cart\Cart $cart, \Rbs\Price\PriceManager $priceManager)
	{
		$this->discount = $discount;
		$this->cart = $cart;
		$this->priceManager = $priceManager;
	}

	/**
	 * @param \string[] $lineKeys
	 * @return $this
	 */
	public function setLineKeys($lineKeys)
	{
		$this->lineKeys = $lineKeys;
		return $this;
	}

	/**
	 * @param \Rbs\Price\PriceInterface $price
	 * @return $this
	 */
	public function setPrice($price)
	{
		$this->price = $price;
		return $this;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxes
	 * @return $this
	 */
	public function setTaxes($taxes)
	{
		$this->taxes = $taxes;
		return $this;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions($options)
	{
		$this->options = $options;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function apply()
	{
		if ($this->price instanceof \Rbs\Price\PriceInterface)
		{
			$cart = $this->cart;
			$options = $this->options;
			$options['discountId'] = $this->discount->getId();
			$parameters = [
				'id' => $this->discount->getId(),
				'title' => $this->discount->getCurrentLocalization()->getTitle(),
				'options' => $options,
				'lineKeys' => $this->lineKeys,
				'price' => $this->price,
				'taxes' => array_map(function (\Rbs\Price\Tax\TaxApplication $tax)
				{
					return $tax->toArray();
				}, $this->taxes)
			];

			$discount = $cart->getNewDiscount($parameters);
			$cart->appendDiscount($discount);
			return true;
		}
		return false;
	}
}