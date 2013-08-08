<?php
namespace Rbs\Collection\Documents;

use Change\Http\Rest\Result\DocumentLink;

/**
 * @name \Rbs\Collection\Documents\Item
 */
class Item extends \Compilation\Rbs\Collection\Documents\Item implements \Change\Collection\ItemInterface
{
	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onUpdate()
	{
		if ($this->isPropertyModified('value') && $this->getLocked())
		{
			$this->setValue($this->getValueOldValue());
		}
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onDelete()
	{
		if ($this->getLocked())
		{
			throw new \RuntimeException('can not delete locked item', 999999);
		}
	}
}
