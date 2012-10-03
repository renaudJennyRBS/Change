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
			foreach ($arrayCompiler->getConfigurationArray($module) as $moduleName => $moduleInfos)
			{
				if (is_array($moduleInfos))
				{
					$infos = array('version' => null, 'visible' => true, 'category' => null, 'icon' => 'package', 'usetopic' => false);
					foreach ($moduleInfos as $key => $value)
					{
						switch ($key)
						{
							case 'category' :
							case 'icon' :
							case 'version' :
								$infos[$key] = $value;
								break;
							case 'visible' :
								$infos[$key] = ($value !== 'false');
								break;
							case 'usetopic' :
								$infos[$key] = ($value === 'true');
								break;
						}
					}
					$modulesInfos[$moduleName] = $infos;
				}
			}
		}
		
		\Change\Stdlib\File::write(\Change\Stdlib\Path::compilationPath('Config', 'modulesinfos.ser'), serialize($modulesInfos));

		return array();
	}
}