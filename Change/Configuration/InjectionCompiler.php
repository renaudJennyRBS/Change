<?php
namespace Change\Configuration;

/**
 * @name \Change\Configuration\InjectionCompiler
 */
class InjectionCompiler
{
	/**
	 * @param \DOMElement $node
	 * @return array
	 */
	public function getConfigurationArray($node)
	{
		$classInjection = array();
		foreach ($node->getElementsByTagName('class') as $subNode)
		{
			foreach ($subNode->getElementsByTagName('entry') as $entry)
			{
				/* @var $entry DOMElement */
				$classInjection[$entry->getAttribute('name')] = $entry->textContent;
			}
		}

		// TODO: documents.
		
		\Change\Stdlib\File::write(\Change\Stdlib\Path::compilationPath('Config', 'injection.ser'), serialize($classInjection));
		
		\Change\Injection\Service::getInstance()->compile();
		
		return array();
	}
}