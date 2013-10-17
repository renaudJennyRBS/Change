<?php
namespace Change\Plugins;

/**
* @name \Change\Plugins\Plugin
*/
class Plugin
{
	const TYPE_MODULE = 'module';

	const TYPE_THEME = 'theme';

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
	 * @param string $type
	 * @param string $vendor
	 * @param string $shortName
	 * @throws \RuntimeException
	 */
	function __construct($type, $vendor, $shortName)
	{
		if ($type !== static::TYPE_MODULE && $type !== static::TYPE_THEME)
		{
			throw new \RuntimeException('Argument 1 should be a valid type', 999999);
		}

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
	 * @api
	 * @param string $type
	 * @return $this
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @api
	 * @param string $vendor
	 * @return $this
	 */
	public function setVendor($vendor)
	{
		$this->vendor = strtolower($vendor);
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getVendor()
	{
		return $this->vendor;
	}

	/**
	 * @api
	 * @param string $shortName
	 * @return $this
	 */
	public function setShortName($shortName)
	{
		$this->shortName = strtolower($shortName);
		return $this;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getShortName()
	{
		return $this->shortName;
	}


	/**
	 * @api
	 * @return string
	 */
	public function getName()
	{
		return $this->getVendor() . '_' . $this->getShortName();
	}

	/**
	 * @api
	 * @param \DateTime|null $registrationDate
	 * @return $this
	 */
	public function setRegistrationDate($registrationDate)
	{
		$this->registrationDate = $registrationDate;
		return $this;
	}

	/**
	 * @api
	 * @return \DateTime|null
	 */
	public function getRegistrationDate()
	{
		return $this->registrationDate;
	}

	/**
	 * @api
	 * @param string $package
	 * @return $this
	 */
	public function setPackage($package)
	{
		$this->package = $package;
		return $this;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * @api
	 * @param boolean $activated
	 * @return $this
	 */
	public function setActivated($activated)
	{
		$this->activated = (bool)$activated;
		return $this;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function getActivated()
	{
		return $this->activated;
	}

	/**
	 * @api
	 * @param boolean $configured
	 * @return $this
	 */
	public function setConfigured($configured)
	{
		$this->configured = (bool)$configured;
		return $this;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function getConfigured()
	{
		return $this->configured;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isAvailable()
	{
		return $this->getActivated() && $this->getConfigured();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isTheme()
	{
		return $this->getType() === static::TYPE_THEME;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isModule()
	{
		return $this->getType() === static::TYPE_MODULE;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isProjectPath()
	{
		return $this->vendor === 'Project';
	}

	/**
	 * @api
	 * @return string
	 */
	public function getNamespace()
	{
		return ($this->isTheme() ? 'Theme\\' : '' )  . $this->getVendor() . '\\' . $this->getShortName();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = get_object_vars($this);
		$array['className'] = get_class($this);
		$array['namespace'] = $this->getNamespace() .'\\';
		return $array;
	}

	/**
	 * @api
	 * @param Plugin $plugin
	 * @return boolean
	 */
	public function eq(Plugin $plugin)
	{
		return $this === $plugin || ($this->type === $plugin->type && $this->vendor === $plugin->vendor && $this->shortName === $plugin->shortName);
	}

	/**
	 * @api
	 * @param \Change\Workspace $workspace
	 * @return string
	 */
	public function getAbsolutePath(\Change\Workspace $workspace)
	{
		if (!$this->isProjectPath())
		{
			if ($this->isModule())
			{
				return $workspace->pluginsModulesPath($this->vendor, $this->shortName);
			}
			return $workspace->pluginsThemesPath($this->vendor, $this->shortName);
		}
		if ($this->isModule())
		{
			return $workspace->projectModulesPath($this->shortName);
		}
		return $workspace->projectThemesPath($this->shortName);
	}

	/**
	 * @api
	 * @param \Change\Workspace $workspace
	 * @return array
	 */
	public function getDocumentDefinitionPaths($workspace)
	{
		$paths = array();
		if ($this->isModule())
		{
			$pattern = $workspace->composePath($this->getAbsolutePath($workspace), 'Documents', 'Assets', '*.xml');
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
	 * @api
	 * @param \Change\Workspace $workspace
	 * @return string|null
	 */
	public function getThemeAssetsPath($workspace)
	{
		if ($this->isModule())
		{
			$path = $workspace->composePath($this->getAbsolutePath($workspace), 'Assets', 'Theme');
		}
		else
		{
			$path = $workspace->composePath($this->getAbsolutePath($workspace), 'Assets');
		}
		return is_dir($path) ? $path : null ;
	}

	/**
	 * @api
	 * @param \Change\Workspace $workspace
	 * @return string|null
	 */
	public function getTwigAssetsPath($workspace)
	{
		$path = $workspace->composePath($this->getAbsolutePath($workspace), 'Assets', 'Twig');
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