<?php
namespace Change\Injection;

/**
 * @name \Change\Injection\Injection
 */
class Injection
{
	
	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;
	
	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;
	
	/**
	 * @param \Change\Configuration\Configuration $configuration
	 * @param \Change\Workspace $workspace
	 */
	public function __construct(\Change\Configuration\Configuration $configuration, \Change\Workspace $workspace)
	{
		$this->configuration = $configuration;
		$this->workspace = $workspace;
	}

	/**
	 * @param array $oldInfo
	 * @return void
	 */
	public function compile($oldInfo = null)
	{
		$newInjectionInfos = array();
		if ($oldInfo === null)
		{
			$oldInfo = $this->loadInfos();
		}

		$compiledFileNames = array();
		$compiledDir = $this->workspace->compilationPath('Injection');
		$injectionArray = $this->configuration->getEntry('injection/class');
		foreach ($injectionArray as $originalClassName => $classNames)
		{
			$originalClassInfo = $this->buildClassInfo($originalClassName, $oldInfo);

			$replacingClassInfos = array();
			foreach (explode(',', $classNames) as $className)
			{
				$className = trim($className);
				if (empty($className)) {continue;}
				$replacingClassInfos[] = $this->buildClassInfo($className, $oldInfo);
			}

			if (count($replacingClassInfos) === 0) {continue;}
			$injection = new ClassInjection($originalClassInfo, $replacingClassInfos);
			$injection->setWorkspace($this->workspace);
			$result = $injection->compile();
			foreach ($result['compiled'] as $infos)
			{
				$compiledFileNames[] = basename($infos['path']);
				$compiledDir = dirname($infos['path']);
			}
			$newInjectionInfos = array_merge($newInjectionInfos, $result['source']);
		}

		$dir = new \DirectoryIterator($compiledDir);
		foreach ($dir as $fileInfo)
		{
			/* @var $fileInfo \SplFileInfo */
			if (!$fileInfo->isDot() && !in_array($fileInfo->getFilename(), $compiledFileNames))
			{
				unlink($fileInfo->getPathname());
			}
		}

		if (count($newInjectionInfos))
		{
			$this->saveInfos($newInjectionInfos);
		}
	}

	/**
	 * @param string $className
	 * @param array $oldInfo
	 * @return string
	 */
	private function buildClassInfo($className, $oldInfo)
	{
		if ($className[0] !== '\\')
		{
			$className = '\\' . $className;
		}
		$result = array('name' => $className);
		if (isset($oldInfo[$className]['path']))
		{
			$result['path'] = $oldInfo[$className]['path'];
		}
		return $result;
	}

	/**
	 * Get the array containing all the injection related informations
	 *
	 * @return array
	 */
	protected function loadInfos()
	{
		$path = $this->workspace->compilationPath('Injection', 'info.ser');
		if (file_exists($path))
		{
			return unserialize(file_get_contents($path));
		}
		return array();
	}

	/**
	 * Save injection to file
	 *
	 * @param array $infos
	 */
	protected function saveInfos($infos)
	{
		$path = $this->workspace->compilationPath('Injection', 'info.ser');
		\Change\Stdlib\File::mkdir(dirname($path));
		file_put_contents($path, serialize($infos));
	}

	/**
	 * This method will update the injection only if needed.
	 */
	public function update()
	{
		// Check if injection is up to date
		$injectionInfos = $this->loadInfos();
		foreach ($injectionInfos as $className => $value)
		{
			if (isset($value['mtime']))
			{
				$fileInfo = new \SplFileInfo($value['path']);
				if ($fileInfo->getMTime() != $value['mtime'])
				{
					$this->compile();
					return;
				}
			}
		}
	}
}