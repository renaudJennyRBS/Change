<?php

namespace Change\Injection;

/**
 * @name \Change\Injection\Service
 * @method \Change\Injection\Service getInstance()
 */
class Service extends \Change\AbstractSingleton
{	
	/**
	 * @return void
	 */
	public function compile($oldInfo = null)
	{
		$newInjectionInfos = array();
		if ($oldInfo === null)
		{
			$oldInfo = $this->loadInfos();
		}
		
		/*
		foreach (Framework::getConfigurationValue('injection/document', array()) as $originalModelName => $replacingModelName)
		{
			$docInject = new change_DocumentInjection($originalModelName, $replacingModelName);
			if (!$checkValidity || ($checkValidity && $docInject->isValid()))
			{
				$newInjectionInfos = array_merge($newInjectionInfos, $docInject->generate());
				$returnValue[$originalModelName] = $replacingModelName;
			}
		}

		*/
		$compiledFileNames = array();
		$compiledDir = \Change\Stdlib\Path::compilationPath('Injection');
		
		//TODO Old class Usage
		foreach (\Framework::getConfigurationValue('injection/class', array()) as $originalClassName => $classNames)
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
		$path = \Change\Stdlib\Path::compilationPath('Injection', 'info.ser');
		if (file_exists($path))
		{
			return unserialize(file_get_contents($path));
		}
		return array();
	}
	
	protected function saveInfos($infos)
	{
		$path = \Change\Stdlib\Path::compilationPath('Injection', 'info.ser');	
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