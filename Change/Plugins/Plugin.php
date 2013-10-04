<?php
namespace Change\Plugins;

/**
* @name \Change\Plugins\Plugin
*/
class Plugin
{
	const TYPE_MODULE = 'Modules';

	const TYPE_THEME = 'Themes';

	/**
	 * @var string
	 */
	protected $basePath;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $vendor;

	/**
	 * @var string
	 */
	protected $shortName;

	/**
	 * @var string
	 */
	protected $package;

	/**
	 * @var \DateTime|null
	 */
	protected $registrationDate;

	/**
	 * @var boolean
	 */
	protected $configured = false;

	/**
	 * @var boolean
	 */
	protected $activated = false;

	/**
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @param string $basePath
	 * @param string $type
	 * @param string $vendor
	 * @param string $shortName
	 */
	function __construct($basePath, $type, $vendor, $shortName)
	{
		$this->basePath = $basePath;
		$this->type = $type;
		$this->vendor = $vendor;
		$this->shortName = $shortName;
	}

	/**
	 * @param array $configuration
	 * @return $this
	 */
	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getConfiguration()
	{
		return (is_array($this->configuration)) ? $this->configuration : array();
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setConfigurationEntry($name, $value)
	{
		if (is_string($name) && $name !== '')
		{
			if (is_array($this->configuration))
			{
				if ($value === null)
				{
					unset($this->configuration[$name]);
				}
				else
				{
					$this->configuration[$name] = $value;
				}
			}
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getConfigurationEntry($name)
	{
		if (is_string($name) && $name !== '' && isset($this->configuration[$name]))
		{
			return $this->configuration[$name];
		}
		return null;
	}

	/**
	 * @param string $basePath
	 * @return $this
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return $this->basePath;
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $vendor
	 * @return $this
	 */
	public function setVendor($vendor)
	{
		$this->vendor = strtolower($vendor);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getVendor()
	{
		return $this->vendor;
	}

	/**
	 * @param string $shortName
	 * @return $this
	 */
	public function setShortName($shortName)
	{
		$this->shortName = strtolower($shortName);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getShortName()
	{
		return $this->shortName;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->getVendor() . '_' . $this->getShortName();
	}

	/**
	 * @param \DateTime|null $registrationDate
	 * @return $this
	 */
	public function setRegistrationDate($registrationDate)
	{
		$this->registrationDate = $registrationDate;
		return $this;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getRegistrationDate()
	{
		return $this->registrationDate;
	}

	/**
	 * @param string $package
	 * @return $this
	 */
	public function setPackage($package)
	{
		$this->package = $package;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * @param boolean $activated
	 * @return $this
	 */
	public function setActivated($activated)
	{
		$this->activated = (bool)$activated;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getActivated()
	{
		return $this->activated;
	}

	/**
	 * @param boolean $configured
	 * @return $this
	 */
	public function setConfigured($configured)
	{
		$this->configured = (bool)$configured;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getConfigured()
	{
		return $this->configured;
	}

	/**
	 * @return boolean
	 */
	public function isAvailable()
	{
		return $this->getActivated() && $this->getConfigured();
	}

	/**
	 * @return boolean
	 */
	public function isTheme()
	{
		return $this->getType() === static::TYPE_THEME;
	}

	/**
	 * @return boolean
	 */
	public function isModule()
	{
		return $this->getType() === static::TYPE_MODULE;
	}

	/**
	 * @return string
	 */
	public function getNamespace()
	{
		return ($this->type === Plugin::TYPE_THEME ? 'Theme\\' : '' )  . $this->getVendor() . '\\' . $this->getShortName();
	}

	/**
	 * @return array
	 */
	protected function getAutoloadNamespaces()
	{
		return array($this->getNamespace() . '\\' => $this->basePath);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = get_object_vars($this);
		$array['className'] = get_class($this);
		$array['namespaces'] = $this->getAutoloadNamespaces();
		return $array;
	}

	/**
	 * @param Plugin $plugin
	 * @return boolean
	 */
	public function eq(Plugin $plugin)
	{
		return $this === $plugin || ($this->type === $plugin->type && $this->vendor === $plugin->vendor && $this->shortName === $plugin->shortName);
	}

	/**
	 * @return array
	 */
	public function getDocumentDefinitionPaths()
	{
		$paths = array();
		if ($this->type === static::TYPE_MODULE)
		{
			$pattern = implode(DIRECTORY_SEPARATOR, array($this->basePath, 'Documents', 'Assets', '*.xml'));
			$result = \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT);
			foreach ($result as $definitionPath)
			{
				$documentName = basename($definitionPath, '.xml');
				$paths[$documentName] = $definitionPath;
			}
		}
		return $paths;
	}

	/**
	 * @return string|null
	 */
	public function getThemeAssetsPath()
	{
		if ($this->type === static::TYPE_MODULE)
		{
			$path = implode(DIRECTORY_SEPARATOR, array($this->basePath, 'Assets', 'Theme'));
		}
		else
		{
			$path = implode(DIRECTORY_SEPARATOR, array($this->basePath, 'Assets'));
		}
		return is_dir($path) ? $path : null ;
	}

	/**
	 * @return string|null
	 */
	public function getTwigAssetsPath()
	{
		$path = implode(DIRECTORY_SEPARATOR, array($this->basePath, 'Assets', 'Twig'));
		return is_dir($path) ? $path : null ;
	}

	function __toString()
	{
		if ($this->getPackage())
		{
			return 'Package: ' . $this->getPackage() . ', '. ($this->getType() === static::TYPE_THEME ? 'Theme: ' : 'Module: ') . $this->getName();
		}
		return ($this->getType() === static::TYPE_THEME ? 'Theme: ' : 'Module: ') . $this->getName();
	}
}