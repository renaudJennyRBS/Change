<?php
namespace Change\Application\Configuration;

/**
 * @name \Change\Application\Configuration\ModulesInfosCompiler
 */
class ModulesInfosCompiler
{
	/**
	 * @param \DOMElement $node
	 * @return array
	 */
	public function getConfigurationArray($node)
	{
		$arrayCompiler = new ArrayCompiler();
		$modulesInfos = array();
		foreach ($node->childNodes as $module)
		{
			/* @var $module \DOMElement */
			if ($module->nodeType != XML_ELEMENT_NODE)
			{
				continue;
			}
			foreach ($node->childNodes as $subNode)
			{
				if ($subNode->nodeType !== XML_ELEMENT_NODE)
				{
					continue;
				}
				$moduleName = $subNode->localName;
				$infos = array('version' => null, 'visible' => true, 'category' => null, 'icon' => 'package', 'usetopic' => false);
				
				foreach ($subNode->childNodes as $valueNode)
				{
					if ($subNode->nodeType !== XML_ELEMENT_NODE)
					{
						continue;
					}
					switch ($valueNode->localName)
					{
						case 'category' :
						case 'icon' :
						case 'version' :
							$infos[$valueNode->localName] = $valueNode->textContent;
							break;
						case 'visible' :
							$infos[$valueNode->localName] = ($valueNode->textContent !== 'false');
							break;
						case 'usetopic' :
							$infos[$valueNode->localName] = ($valueNode->textContent === 'true');
							break;
					}
					$modulesInfos[$moduleName] = $infos;
				}
			}
		}
		
		\Change\Stdlib\File::write(\Change\Stdlib\Path::compilationPath('Config', 'modulesinfos.ser'), serialize($modulesInfos));

		return array();
	}
}