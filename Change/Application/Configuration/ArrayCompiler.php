<?php
namespace Change\Application\Configuration;

/**
 * @name \Change\Application\Configuration\ArrayCompiler
 */
class ArrayCompiler
{
	/**
	 * @param \DOMElement $node
	 * @return array
	 */
	public function getConfigurationArray($node)
	{
		if ($node->hasAttribute('name'))
		{
			$result = $node->textContent;
		}
		else
		{
			$result = array();
			foreach ($node->childNodes as $subNode)
			{
				/* @var $subNode \DOMElement */
				if ($subNode->nodeType == XML_ELEMENT_NODE)
				{
					$subResult = $this->getConfigurationArray($subNode);
					if (count($subResult))
					{
						$result = array_merge($result, $subResult);
					}
				}
			}
		}
	
		$key = ($node->hasAttribute('name')) ? $node->getAttribute('name') : $node->localName;
		return array($key => $result);
	}
}