<?php
/**
 * Created by JetBrains PhpStorm.
 * User: inthause
 * Date: 09/04/13
 * Time: 15:55
 * To change this template use File | Settings | File Templates.
 */

namespace Change\Http\Web\Layout;

class Factory
{
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