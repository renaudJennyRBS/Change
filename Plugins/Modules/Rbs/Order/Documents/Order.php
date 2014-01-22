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
	 * @return  \Rbs\Order\OrderLine[]
	 */
	public function getLines()
	{
		$config = $this->getLinesData();
		if (is_array($config))
		{
			return array_map(function($line) { return new \Rbs\Order\OrderLine($line);}, $config);
		}
		return array();
	}

	/**
	 * @param  \Rbs\Order\OrderLine[] $lines
	 * @return $this
	 */
	public function setLines(array $lines = null)
	{
		if (is_array($lines) && count($lines))
		{
			$lineData = array();
			foreach ($lines as $line)
			{
				if ($line instanceof \Rbs\Order\OrderLine)
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

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$documentResult = $restResult;
			$um = $documentResult->getUrlManager();
			$selfLinks = $documentResult->getRelLink('self');
			$selfLink = array_shift($selfLinks);
			if ($selfLink instanceof \Change\Http\Rest\Result\Link)
			{
				$baseUrl = $selfLink->getPathInfo();
				$documentResult->addLink(new \Change\Http\Rest\Result\Link($um, $baseUrl . '/Shipments/', 'shipments'));
			}
		}
	}
}
