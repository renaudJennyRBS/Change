<?php
namespace Rbs\Order\Documents;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Order\Documents\Order
 */
class Order extends \Compilation\Rbs\Order\Documents\Order
{
	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

	/**
	 * @var \Rbs\Order\OrderLine[]
	 */
	protected $lines;

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
	 * @param array $context
	 * @return $this
	 */
	public function setContext($context = null)
	{
		$this->context = new \Zend\Stdlib\Parameters();
		if (is_array($context))
		{
			$this->context->fromArray($context);
		}
		elseif ($context instanceof \Traversable)
		{
			foreach ($context as $n => $v)
			{
				$this->context->set($n, $v);
			}
		}
		return $this;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext()
	{
		if ($this->context === null)
		{
			$v = $this->getContextData();
			$this->context = new \Zend\Stdlib\Parameters(is_array($v) ? $v : null);
		}
		return $this->context;
	}

	/**
	 * @return  \Rbs\Order\OrderLine[]
	 */
	public function getLines()
	{
		if ($this->lines === null)
		{
			$linesData = $this->getLinesData();
			if (is_array($linesData))
			{
				$this->lines = array_map(function($line) {return new \Rbs\Order\OrderLine($line);}, $linesData);
			}
			else
			{
				$this->lines = [];
			}
		}
		return $this->lines;
	}

	/**
	 * @param  \Rbs\Order\OrderLine[] $lines
	 * @return $this
	 */
	public function setLines(array $lines)
	{
		$this->lines = array();
		foreach ($lines as $line)
		{
			if ($line instanceof \Rbs\Order\OrderLine)
			{
				$this->lines[] = $line;
			}
			elseif (is_array($line))
			{
				$this->lines[] = new \Rbs\Order\OrderLine($line);
			}
		}
		return $this;
	}

	/**
	 * @param \Rbs\Order\OrderLine|array $line
	 */
	public function appendLine($line)
	{
		//Unserialise lines
		$this->getLines();
		if ($line instanceof \Rbs\Order\OrderLine)
		{
			$this->lines[] = $line;
		}
		elseif (is_array($line))
		{
			$this->lines[] = new \Rbs\Order\OrderLine($line);
		}
	}

	/**
	 * @param \Rbs\Order\OrderLine[] $lines
	 */
	protected function updateLinesIndex(array $lines)
	{
		foreach ($lines as $index => $line)
		{
			$line->setIndex($index);
		}
	}

	protected function onCreate()
	{
		$this->setWrappedFields();
	}

	protected function onUpdate()
	{
		$this->setWrappedFields();
	}

	protected function setWrappedFields()
	{
		if ($this->context instanceof \Zend\Stdlib\Parameters)
		{
			$this->setContextData($this->context->toArray());
			$this->context = null;
		}

		if (is_array($this->lines))
		{
			$this->updateLinesIndex($this->lines);
			$this->setLinesData(array_map(function(\Rbs\Order\OrderLine $line) {return $line->toArray();}, $this->lines));
			$this->lines = null;
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

			/** @var $order Order */
			$order = $event->getDocument();
			$documentResult->setProperty('context', $order->getContext()->toArray());
			$documentResult->setProperty('lines', array_map(function(\Rbs\Order\OrderLine $line) {return $line->toArray();}, $this->getLines()));
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return boolean
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		if ($name === 'context')
		{
			$this->setContext($value);
		}
		elseif ($name === 'lines')
		{
			$this->setLines($value);
		}
		else
		{
			return parent::processRestData($name, $value, $event);
		}
		return true;
	}
}
