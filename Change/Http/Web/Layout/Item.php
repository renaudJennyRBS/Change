<?php
namespace Change\Http\Web\Layout;

/**
 * @name \Change\Http\Web\Layout\Item
 */
abstract class Item
{
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var Item[]
	 */
	protected $items;

	/**
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param Item[] $items
	 */
	public function setItems($items)
	{
		$this->items = $items;
	}

	/**
	 * @return Item[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @return string
	 */
	abstract public function getType();

	/**
	 * @param array $data
	 * @return void
	 */
	public function initialize(array $data)
	{
		$this->id = $data['id'];

		if (isset($data['parameters']) && count($data['parameters']))
		{
			$this->setParameters($data['parameters']);
		}
		else
		{
			$this->setParameters(array());
		}
	}

	/**
	 * @param string $type
	 * @return Item[]
	 */
	public function getItemsByType($type)
	{
		$result = ($this->getType() === $type) ? array($this) : array();
		if (count($this->items))
		{
			foreach ($this->items as $item)
			{
				/* @var $item Item */
				$result = array_merge($result, $item->getItemsByType($type));
			}
		}
		return $result;
	}

	/**
	 * @return Block[]
	 */
	public function getBlocks()
	{
		return $this->getItemsByType('block');
	}
}