<?php
namespace Change\Application\Configuration;

/**
 * @name \Change\Application\Configuration\Generator
 */
class Generator
{
	/**
	 * @param array $changeProperties
	 * @return array old and current configuration
	 */
	public function compile($changeProperties)
	{
		// Compile new config and defines.
		$dom = $this->mergeConfigurationFiles($changeProperties);
		$defines = $this->compileDefines($dom);
		$configs = $this->compileConfigs($dom);
		
		// Save compiled file.
		$content = "<?php\n// \\Change\\Application\\Configuration::setDefineArray PART // \n";
		$content .= '$configuration->setDefineArray(' . var_export($defines, true) . ");\n\n";
		if ($defines['DEVELOPMENT_MODE'])
		{
			$this->buildDevelopmentDefineFile($defines);
		}
		$content .= "// \\Change\\Application\\Configuration::setConfigArray PART // \n";
		$content .= '$configuration->setConfigArray(' . var_export($configs, true) . ');';
		\Change\Stdlib\File::write(\Change\Stdlib\Path::compilationPath('Config', 'project.php'), $content);
				
		return array("config" => $configs, "defines" => $defines);
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @return array
	 */
	protected function compileDefines($dom)
	{
		$xpath = new \DOMXpath($dom);
		$defines = array();
		
		$nodeList = $xpath->query('/project/defines/define');
		foreach ($nodeList as $node)
		{
			/* @var $node \DOMElement */
			$defines[$node->getAttribute('name')] = $node->textContent;
		}
		
		return $this->fixDefinesArray($defines);
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @return array
	 */
	protected function compileConfigs($dom)
	{
		$xpath = new \DOMXpath($dom);
		
		$config = array();
		$nodeList = $xpath->query('/project/config');
		foreach ($nodeList as $node)
		{
			/* @var $node \DOMElement */
			$result = $this->getConfigurationArray($node);
			if (count($result))
			{
				$config = array_merge($config, $result);
			}
		}
		return $config['config'];
	}
	
	/**
	 * @param \DOMElement $node
	 * @return array
	 */
	public function getConfigurationArray($node)
	{
		$result = array();
		foreach ($node->childNodes as $subNode)
		{
			/* @var $subNode \DOMElement */
			if ($subNode->nodeType == XML_ELEMENT_NODE)
			{
				$subResult = $this->getCompiler($subNode)->getConfigurationArray($subNode);
				if (count($subResult))
				{
					$result = array_merge($result, $subResult);
				}
			}
		}
		
		$key = ($node->hasAttribute('name')) ? $node->getAttribute('name') : $node->localName;
		return array($key => $result);
	}
	
	/**
	 * @param \DOMElement $node
	 * @return \Change\Application\Configuration\Generator 
	 */
	public function getCompiler($node)
	{
		switch ($node->localName)
		{
			case 'modulesinfos' :
				return new \Change\Application\Configuration\ModulesInfosCompiler();
			case 'oauth' :
				return new \Change\Application\Configuration\OauthCompiler();
			case 'injection' :
				return new \Change\Application\Configuration\InjectionCompiler();
			default :
				return new \Change\Application\Configuration\ArrayCompiler();
		}
	}
	
	/**
	 * @param array $changeProperties
	 * @return \DOMDocument
	 */
	protected function mergeConfigurationFiles($changeProperties)
	{
		$dom = $this->initDomDocument(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Assets', 'project.xml')));
		
		// Merge module.xml files.
		foreach (glob(implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'modules', '*', 'config', 'module.xml'))) as $filePath)
		{
			if (is_readable($filePath))
			{
				$moduleName = basename(dirname(dirname($filePath)));
				$this->mergeModuleFile($dom, $moduleName, $filePath);
			}
		}
		foreach (glob(implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'override', 'modules', '*', 'config', 'module.xml'))) as $filePath)
		{
			if (is_readable($filePath))
			{
				$moduleName = basename(dirname(dirname($filePath)));
				$this->mergeModuleFile($dom, $moduleName, $filePath);
			}
		}
		
		// Merge framework's install.xml file.
		$filePath = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'framework', 'install.xml'));
		$tmpDom = new \DOMDocument('1.0', 'utf-8');
		$tmpDom->load($filePath);
		$tmpNode = $tmpDom->documentElement;
		if ($tmpNode && $tmpNode->hasAttribute('version'))
		{
			$this->setDefine($dom, 'CHANGE_VERSION', $tmpNode->getAttribute('version'));
		}
		
		// Merge modules' install.xml files.
		foreach (glob(implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'modules', '*', 'install.xml'))) as $filePath)
		{
			if (is_readable($filePath))
			{
				$moduleName = basename(dirname($filePath));
				$this->mergeInstallFile($dom, $moduleName, $filePath);
			}
		}
		
		// Merge project specific config files.
		$filePath = \Change\Stdlib\Path::appPath('Config', 'project.xml');
		if (is_readable($filePath))
		{
			$this->mergeProjectFile($dom, $filePath);
		}
		$filePath = \Change\Stdlib\Path::appPath('Config', 'project.' . \Change\Application::getInstance()->getProfile() . '.xml');
		if (is_readable($filePath))
		{
			$this->mergeProjectFile($dom, $filePath);
		}
		
		// Merge change.properties.
		if (isset($changeProperties['OUTGOING_HTTP_PROXY_HOST']))
		{
			$this->setDefine($dom, 'OUTGOING_HTTP_PROXY_HOST', $changeProperties['OUTGOING_HTTP_PROXY_HOST']);
			$this->setDefine($dom, 'OUTGOING_HTTP_PROXY_PORT', $changeProperties['OUTGOING_HTTP_PROXY_PORT']);
			$node = $this->getOrCreateNode($dom, array('config', 'http', 'adapter'));
			if ($node->textContent == '\Zend\Http\Client\Adapter\Curl')
			{
				$value = $changeProperties["OUTGOING_HTTP_PROXY_HOST"] . ':' . $changeProperties["OUTGOING_HTTP_PROXY_PORT"];
				$this->getOrCreateNode($dom, array('config', 'http', 'curloptions', "entry[@name='" . CURLOPT_PROXY . "']"), $value);
			}
			elseif ($node->textContent == '\Zend\Http\Client\Adapter\Proxy' || $node->textContent == '\Zend\Http\Client\Adapter\Socket')
			{
				$this->getOrCreateNode($dom, array('config', 'http', "entry[@name='adapter']"), '\Zend\Http\Client\Adapter\Proxy');
				$this->getOrCreateNode($dom, array('config', 'http', "entry[@name='proxy_host']"), $changeProperties["OUTGOING_HTTP_PROXY_HOST"]);
				$this->getOrCreateNode($dom, array('config', 'http', "entry[@name='proxy_port']"), $changeProperties["OUTGOING_HTTP_PROXY_PORT"]);
			}
		}
		
		$logLevelNode = $this->getOrCreateNode($dom, array('project', 'config', 'logging', "entry[@name='level']"));
		switch ($logLevelNode->textContent)
		{
			case 'EXCEPTION' :
			case 'ALERT' :
				$logLevel = 'ALERT';
				break;
			case 'ERROR' :
			case 'ERR' :
				$logLevel = 'ERR';
				break;
			case 'NOTICE' :
				$logLevel = 'NOTICE';
				break;
			case 'DEBUG' :
				$logLevel = 'DEBUG';
				break;
			case 'INFO' :
				$logLevel = 'INFO';
				break;
			default :
				$logLevel = 'WARN';
				break;
		}
		$this->setNodeValue($logLevelNode, $logLevel);
		
		foreach (array('TMP_PATH' => true, 'DEFAULT_HOST' => true, 'PROJECT_ID' => true, 'CHANGE_COMMAND' => false, 
			'DOCUMENT_ROOT' => false, 'PROJECT_LICENSE' => false, 'FAKE_EMAIL' => false, 'PHP_CLI_PATH' => true, 
			'DEVELOPMENT_MODE' => false) as $constName => $required)
		{
			if (isset($changeProperties[$constName]))
			{
				$this->setDefine($dom, $constName, $changeProperties[$constName]);
			}
			elseif ($required)
			{
				throw new \Exception('Please define ' . $constName . ' in your change.properties  file');
			}
		}
		
		return $dom;
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param string $name
	 * @param string $value
	 */
	protected function setDefine($dom, $name, $value)
	{
		$this->getOrCreateNode($dom, array('project', 'defines', "define[@name='$name']"), $value);
	}
	
	/**
	 * @param string $filePath
	 * @return \DOMDocument
	 */
	protected function initDomDocument($filePath)
	{
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->load($filePath);
		return $dom;
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param string $filePath
	 */
	protected function mergeProjectFile($dom, $filePath)
	{
		$tmpDom = new \DOMDocument('1.0', 'utf-8');
		$tmpDom->load($filePath);
		if ($tmpDom->documentElement)
		{
			foreach ($tmpDom->documentElement->childNodes as $node)
			{
				if ($node->nodeType === XML_ELEMENT_NODE)
				{
					$this->mergeNodes($dom, $node, array('project'));
				}
			}
		}
	}

	/**
	 * @param \DOMDocument $dom
	 * @param string $moduleName
	 * @param string $filePath
	 */
	protected function mergeInstallFile($dom, $moduleName, $filePath)
	{
		$tmpDom = new \DOMDocument('1.0', 'utf-8');
		$tmpDom->load($filePath);
		$tmpNode = $tmpDom->documentElement;
		if ($tmpNode && $tmpNode->hasAttribute('version'))
		{
			$path = array('project', 'config', 'modulesinfos', $moduleName, 'version');
			$this->getOrCreateNode($dom, $path, $tmpNode->getAttribute('version'));
		}
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param string $moduleName
	 * @param string $filePath
	 */
	protected function mergeModuleFile($dom, $moduleName, $filePath)
	{
		$tmpDom = new \DOMDocument('1.0', 'utf-8');
		$tmpDom->load($filePath);
		if ($tmpDom->documentElement)
		{
			foreach ($tmpDom->documentElement->childNodes as $node)
			{
				if ($node->nodeType !== XML_ELEMENT_NODE)
				{
					continue;
				}
				elseif ($node->nodeName == 'module')
				{
					foreach ($node->childNodes as $subNode)
					{
						if ($subNode->nodeType === XML_ELEMENT_NODE)
						{
							$this->mergeNodes($dom, $subNode, array('project', 'config', 'modulesinfos', $moduleName));
						}
					}
				}
				elseif ($node->nodeName == 'modules')
				{
					$this->mergeNodes($dom, $node, array('project', 'config'));
				}
				// TODO: remove this redundant case?
				elseif ($node->nodeName == 'project')
				{
					foreach ($node->childNodes as $subNode)
					{
						if ($subNode->nodeType === XML_ELEMENT_NODE)
						{
							$this->mergeNodes($dom, $subNode, array('project', 'config', 'modules', $moduleName));
						}
					}
				}
			}
		}
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param \DOMElement $node
	 * @param string[] $path
	 */
	protected function mergeNodes($dom, $node, $path)
	{
		$path[] = $node->nodeName . ($node->hasAttribute('name') ? ("[@name='" . $node->getAttribute('name')) . "']" : '');
		$element = $this->getOrCreateNode($dom, $path);
		
		$isContainer = false;
		foreach ($node->childNodes as $subNode)
		{
			if ($subNode->nodeType === XML_ELEMENT_NODE)
			{
				$isContainer = true;
				$this->mergeNodes($dom, $subNode, $path);
			}
		}
		
		if (!$isContainer)
		{
			$value = trim($node->textContent);
			$this->setNodeValue($element, $value);
		}
	}
	
	/**
	 * @param \DOMDocument $dom
	 * @param string[] $path
	 * @return \DOMElement
	 */
	protected function getOrCreateNode($dom, $path, $value = null)
	{
		$xpath = new \DOMXpath($dom);
		$fullPath = '/' . implode('/', $path);
		
		$elements = $xpath->query($fullPath);
		if ($elements->length > 2)
		{
			throw new \Exception('There are more than one node for path: ' . $fullPath);
		}
		elseif ($elements->length == 1)
		{
			$node = $elements->item(0);
		}
		else
		{
			$last = array_pop($path);
			if (strpos($last, "[@name='") !== false)
			{
				list ($tagName, $name) = explode("[@name='", str_replace("']", '', $last));
				$tagName = $tagName != '*' ? $tagName : 'entry';
				$node = $dom->createElement($tagName);
				$node->setAttribute('name', $name);
			}
			else
			{
				$node = $dom->createElement($last);
			}
			
			$parent = $this->getOrCreateNode($dom, $path);
			$parent->appendChild($node);
		}
		
		if ($value !== null)
		{
			$this->setNodeValue($node, $value);
		}
		return $node;
	}
	
	/**
	 * @param \DOMElement $node
	 * @param string $value
	 */
	protected function setNodeValue($node, $value)
	{
		foreach ($node->childNodes as $subNode)
		{
			$node->removeChild($subNode);
		}
		$node->appendChild($node->ownerDocument->createTextNode($value));
	}
	
	/**
	 * @param array $configDefineArray
	 * @return array
	 */
	protected function fixDefinesArray($configDefineArray)
	{
		foreach ($configDefineArray as $name => $value)
		{
			if (is_string($value))
			{
				// Match PROJECT_HOME . DIRECTORY_SEPARATOR . 'config'
				// Or CHANGE_CONFIG_DIR . 'toto'
				// But not Fred's Directory
				if (preg_match('/^(([A-Z][A-Z_0-9]+)|(\'[^\']*\'))(\s*\.\s*(([A-Z][A-Z_0-9]+)|(\'[^\']*\')))+$/', $value))
				{
					$configDefineArray[$name] = 'return ' . $value . ';';
				}
				elseif ($value === 'true')
				{
					$configDefineArray[$name] = true;
				}
				elseif ($value === 'false')
				{
					$configDefineArray[$name] = false;
				}
				elseif (is_numeric($value))
				{
					$configDefineArray[$name] = floatval($value);
				}
			}
		}
		return $configDefineArray;
	}
	
	/**
	 * @param array $defineArray
	 */
	protected function buildDevelopmentDefineFile($defineArray)
	{
		$content = "<?php // For IDE completion only //" . PHP_EOL;
		$content .= "throw new Exception('Do not include this file');" . PHP_EOL;
		foreach ($defineArray as $key => $value)
		{
			$defval = var_export($value, true);
			if (is_string($value))
			{
				if (strpos($value, 'return ') === 0 && substr($value, -1) === ';')
				{
					$defval = substr($value, 7, strlen($value) - 8);
				}
			}
			$content .= "define('" . $key . "', " . $defval . ");" . PHP_EOL;
		}
		$path = \Change\Stdlib\Path::compilationPath('Config', 'dev_defines.php');
		\Change\Stdlib\File::write($path, $content);
	}
	
	/**
	 * Do not use it directly. Prefer using \Change\Application\Configuration::addPersistentEntry().
	 * @param array $pathArray
	 * @param string $entryName
	 * @param string $value
	 * @return string The old value or false if the operation failed.
	 */
	public function addPersistentEntry($pathArray, $entryName, $value)
	{
		array_unshift($pathArray, 'project');
		$pathArray[] = "*[@name='" . $entryName . "']";
	
		$configProjectPath = \Change\Stdlib\Path::appPath('Config', 'project.xml');
		if (!is_readable($configProjectPath))
		{
			return false;
		}		
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = true;
		$dom->preserveWhiteSpace = false;
		$dom->load($configProjectPath);
		$dom->formatOutput = true;
		if ($dom->documentElement == null)
		{
			return false;
		}
		
		$node = $this->getOrCreateNode($dom, $pathArray);
		$oldValue = $node->textContent;
		if ($oldValue != $value)
		{
			$this->setNodeValue($node, $value);
			$dom->save($configProjectPath);
		}		
		return $oldValue;
	}
}