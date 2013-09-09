<?php
namespace Rbs\Order\Std;

/**
* @name \Rbs\Order\Std\OrderLine
*/
class OrderLine
{
	/**
	 * @var integer
	 */
	protected $quantity;

	/**
	 * @var string
	 */
	protected $designation;

	/**
	 * @var OrderItem[]
	 */
	protected $items = array();

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @param array $config
	 */
	function __construct(array $config = null)
	{
		if ($config)
		{
			$this->fromArray($config);
		}
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'quantity': $this->quantity = $value; break;
				case 'designation': $this->designation = $value; break;
				case 'items':
					if (is_array($value))
					{
						$this->items = array_map(function($item) { return new OrderItem($item);}, $value);
					}
					else
					{
						$this->items = array();
					}
					break;
				case 'options':
					$this->options = (is_array($value)) ? $value : array();
					break;
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = get_object_vars($this);
		$array['items'] = array_map(function(OrderItem $item) {return $item->toArray();}, $array['items']);
		return $array;
	}

	/**
	 * @param string $designation
	 * @return $this
	 */
	public function setDesignation($designation)
	{
		$this->designation = $designation;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDesignation()
	{
		return $this->designation;
	}

	/**
	 * @param int $quantity
	 * @return $this
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @param \Rbs\Order\Std\OrderItem[] $items
	 * @return $this
	 */
	public function setItems($items)
	{
		$this->items = $items;
		return $this;
	}

	/**
	 * @return \Rbs\Order\Std\OrderItem[]
	 */
	public function getItems()
	{
		return $this->items;
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
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}


}