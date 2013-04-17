<?php
namespace Change\Presentation\Layout;

/**
 * @package Change\Presentation\Layout
 * @name \Change\Presentation\Layout\Layout
 */
class Layout
{
	/**
	 * @var Item[]
	 */
	protected $items;

	/**
	 * @param array $array
	 */
	function __construct(array $array = null)
	{
		if ($array !== null && count($array))
		{
			$this->items = $this->fromArray($array);
		}
		else
		{
			$this->items = array();
		}
	}

	/**
	 * @return Item[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param array $items
	 */
	public function setItems(array $items)
	{
		$this->items = array();
		foreach ($items as $value)
		{
			if ($value instanceof Item)
			{
				$this->items[$value->getId()] = $value;
			}
		}
	}

	/**
	 * @param string $type
	 * @return Item[]
	 */
	public function getItemsByType($type)
	{
		$result = array();
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
	 * @param string $id
	 * @return Item|null
	 */
	public function getById($id)
	{
		if (count($this->items))
		{
			foreach ($this->items as $item)
			{
				$result = $item->getById($id);
				if ($result)
				{
					return $result;
				}
			}
		}
		return null;
	}

	/**
	 * @return Block[]
	 */
	public function getBlocks()
	{
		return $this->getItemsByType('block');
	}

	/**
	 * @return Container[]
	 */
	public function getContainers()
	{
		return $this->getItemsByType('container');
	}

	/**
	 * @param array $array
	 * @return Item[]
	 */
	public function fromArray($array)
	{
		$result = array();
		foreach ($array as $key => $data)
		{
			$type = $data['type'];
			$id = $data['id'];
			$item = $this->getNewItem($type, $id);
			$item->initialize($data);
			if (isset($data['items']) && count($data['items']))
			{
				$item->setItems($this->fromArray($data['items']));
			}
			else
			{
				$item->setItems(array());
			}
			$result[$key] = $item;
		}
		return $result;
	}

	/**
	 * @param string $type
	 * @param string $id
	 * @throws \InvalidArgumentException
	 * @return Item
	 */
	public function getNewItem($type, $id)
	{
		switch ($type)
		{
			case 'container':
				$item = new Container();
				break;
			case 'row':
				$item = new Row();
				break;
			case 'cell':
				$item = new Cell();
				break;
			case 'block':
				$item = new Block();
				break;
			default:
				throw new \InvalidArgumentException('Argument 1 must be a valid type', 999999);
		}

		$item->setId($id);
		return $item;
	}
}