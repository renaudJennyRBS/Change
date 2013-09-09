<?php
namespace Rbs\Order\Documents;

/**
 * @name \Rbs\Order\Documents\Order
 */
class Order extends \Compilation\Rbs\Order\Documents\Order
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getCode() ? $this->getCode() : '[' . $this->getId() . ']';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @return \Rbs\Order\Std\OrderLine[]
	 */
	public function getLines()
	{
		$config = $this->getLinesData();
		if (is_array($config))
		{
			return array_map(function($line) { return new \Rbs\Order\Std\OrderLine($line);}, $config);
		}
		return array();
	}

	/**
	 * @param \Rbs\Order\Std\OrderLine[] $lines
	 * @return $this
	 */
	public function setLines(array $lines = null)
	{
		if (is_array($lines) && count($lines))
		{
			$lineData = array();
			foreach ($lines as $line)
			{
				if ($line instanceof \Rbs\Order\Std\OrderLine)
				{
					$lineData[] = $line->toArray();
				}
			}
			return $this->setLinesData(count($lineData) ? $lineData : null);
		}
		else
		{
			return $this->setLinesData(null);
		}
	}
}
