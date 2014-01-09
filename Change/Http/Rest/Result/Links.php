<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\Links
 */
class Links extends \ArrayObject
{
	/**
	 * @param mixed $index
	 * @return bool
	 */
	public function offsetExists($index)
	{
		if (is_string($index))
		{
			return $this->getByRel($index) !== false;
		}
		else
		{
			return parent::offsetExists($index);
		}
	}

	/**
	 * @param mixed $index
	 * @return array|false|mixed
	 */
	public function offsetGet($index)
	{
		if (is_string($index))
		{
			return $this->getByRel($index);
		}
		else
		{
			return parent::offsetGet($index);
		}
	}

	/**
	 * @param integer|string|null $index
	 * @param string|array|\Change\Http\Rest\Result\Link $newVal
	 */
	public function offsetSet($index, $newVal)
	{
		if (is_string($index))
		{
			if (is_string($newVal))
			{
				parent::offsetSet(null, array('rel' => $index, 'href' => $newVal));
			}
			elseif (is_array($newVal))
			{
				$newVal['rel'] = $index;
				parent::offsetSet(null, $newVal);
			}
			elseif ($newVal instanceof Link)
			{
				$newVal->setRel($index);
				parent::offsetSet(null, $newVal);
			}
		}
		else
		{
			parent::offsetSet($index, $newVal);
		}
	}

	public function offsetUnset($index)
	{
		if (is_string($index))
		{
			$relArray = array();
			foreach ($this as $link)
			{
				if ($link instanceof \Change\Http\Rest\Result\Link)
				{
					if ($index != $link->getRel())
					{
						$relArray[] = $link;
					}
				}
				elseif (is_array($link) && isset($link['rel']))
				{
					if ($index != $link['rel'])
					{
						$relArray[] = $link;
					}
				}
				else
				{
					$relArray[] = $link;
				}
			}
			$this->exchangeArray($relArray);
		}
		else
		{
			parent::offsetUnset($index);
		}
	}

	/**
	 * @param string $rel
	 * @return array|false
	 */
	public function getByRel($rel)
	{
		$relArray = array();
		foreach ($this as $link)
		{
			if ($link instanceof \Change\Http\Rest\Result\Link)
			{
				if ($rel == $link->getRel())
				{
					$relArray[] = $link;
				}
			}
			elseif (is_array($link) && isset($link['rel']))
			{
				if ($rel == $link['rel'])
				{
					$relArray[] = $link;
				}
			}
		}
		return count($relArray) ? $relArray : false;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array();
		foreach ($this as $link)
		{
			if (is_object($link) && is_callable(array($link, 'toArray')))
			{
				$array[] = $link->toArray();
			}
			else
			{
				$array[] = $link;
			}
		}
		return $array;
	}
}