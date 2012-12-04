<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\Property
 */
class Workflow
{
	/**
	 * @var string
	 */
	protected $startTask = null;
	
	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @param DOMElement $xmlElement
	 */
	public function initialize($xmlElement)
	{
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;
			switch ($name)
			{
				case "start-task":
					$this->startTask = $value;
					break;
				default:
					break;
			}
		}

		foreach ($xmlElement->childNodes as $node)
		{
			if ($node->nodeName == 'parameter')
			{
				$nodeValue = $node->nodeValue;
				foreach($node->attributes as $attribute)
				{
					$name = $attribute->nodeName;
					$value = $attribute->nodeValue;
					switch ($name)
					{
						case "name":
							$this->parameters[$value] = $nodeValue;
							break;
						default:
							break;
					}
				}
			}
		}
	}

	/**
	 * @var string
	 */
	public function getStartTask()
	{
		return $this->startTask;
	}

	/**
	 * @var array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}
}