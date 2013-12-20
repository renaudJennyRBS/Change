<?php
namespace Rbs\Order\Documents;

/**
 * @name \Rbs\Order\Documents\Expedition
 */
class Expedition extends \Compilation\Rbs\Order\Documents\Expedition
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getCode())
		{
			return $this->getCode();
		}
		return 'NO-CODE-DEFINED';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}
}
