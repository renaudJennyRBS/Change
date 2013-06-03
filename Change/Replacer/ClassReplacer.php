<?php
namespace Change\Replacer;

/**
 * @name \Change\Replacer\ClassReplacer
 */
class ClassReplacer
{
	const REPLACED_CLASS_SUFFIX = '_replaced';
	const REPLACING_CLASS_SUFFIX = '_replacing';

	/**
	 * @var array
	 */
	protected $originalClassInfo;

	/**
	 * @var array
	 */
	protected $replacingClassInfos;

	/**
	 * @var \Change\Replacer\Extractor\ExtractedClass
	 */
	protected $originalExtractedClass;

	/**
	 * index of the replacer in case of multiple replacements
	 *
	 * @var int
	 */
	protected $replacerIndex = 0;

	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;

	/**
	 * @param array $originalClassInfo
	 * @param array $replacingClassInfo
	 */
	public function __construct(array $oInfo, array $rInfos)
	{
		if (!isset($oInfo['name']) || $oInfo['name'][0] != '\\')
		{
			throw new \InvalidArgumentException('first argument of __construct must have at least the key "name" set to a fully-qualified class name', 90000);
		}
		$this->originalClassInfo = $oInfo;
		foreach ($rInfos as $info)
		{
			if (!isset($info['name']) || $info['name'][0] != '\\')
			{
				throw new \InvalidArgumentException('all entries of the second argument of __construct must have at least one key "name" set to a fully-qualified class name', 90001);
			}
		}
		$this->replacingClassInfos = $rInfos;
	}

	/**
	 * @param \Change\Workspace $workspace
	 */
	public function setWorkspace(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * @return \Change\Workspace
	 */
	public function getWorkspace()
	{
		return $this->workspace;
	}

	/**
	 * Process the original and extract its different classes and interfaces. On return, you get an array
	 * with keys corresponding to class names and an array containing the path/mtime of the file containing the
	 * class.
	 *
	 * @return array string
	 */
	protected function processOriginalFile()
	{
		$result = array();
		$fileInfo = new \SplFileInfo($this->originalClassInfo['path']);
		$originalExtractor = new \Change\Replacer\CodeExtractor($this->originalClassInfo['path']);

		// There should be only one namespace in the original file
		$namespaces = $originalExtractor->getNamespaces();
		if (count($namespaces) != 1)
		{
			throw new \RuntimeException('file at ' . $this->originalClassInfo['path'] . ' should only contain one namespace', 90002);
		}

		/* @var $namespace \Change\Replacer\Extractor\ExtractedNamespace */
		$namespace = $namespaces[0];

		// There should be only one class in the original file
		$classes = $namespace->getDeclaredClasses();
		if (count($classes) != 1)
		{
			throw new \RuntimeException('file at ' . $this->originalClassInfo['path'] . ' should only contain one class', 90003);
		}
		/* @var $class \Change\Replacer\Extractor\ExtractedClass */
		$this->originalExtractedClass = $classes[0];

		$usesArray = array();
		foreach ($namespace->getDeclaredUses() as $extractedUse)
		{
			/* @var $extractedUse \Change\Replacer\Extractor\ExtractedUse */
			$usesArray[] = $extractedUse->__toString();
		}
		$usesString = implode(PHP_EOL, $usesArray);

		if ($this->buildFullClassName($this->originalExtractedClass->getNamespace(), $this->originalExtractedClass->getName()) != $this->originalClassInfo['name'])
		{
			throw new \RuntimeException('file at ' . $this->originalClassInfo['path'] . ' should  contain class ' . $this->originalClassInfo['name'], 90004);
		}

		$class = clone $this->originalExtractedClass;
		$class->setName($this->originalExtractedClass->getName() . self::REPLACED_CLASS_SUFFIX . $this->replacerIndex);

		$fullClassName = $this->buildFullClassName($class->getNamespace(), $class->getName());
		$components = explode('\\', $fullClassName);
		$fileName = implode('_', $components);
		$path = $this->getWorkspace()->compilationPath('Replacer', $fileName . '.php');
		\Change\Stdlib\File::mkdir(dirname($path));

		$contentParts = array('<?php');
		if ($namespace->getName() != '')
		{
			$contentParts[] = 'namespace ' . $namespace->getName() . ';';
		}
		$contentParts[] = $usesString;
		$contentParts[] = $class->__toString();
		file_put_contents($path, implode(PHP_EOL, $contentParts));
		$result[$fullClassName] = array('path' => $path, 'mtime' => $fileInfo->getMTime());

		return $result;
	}

	/**
	 * @param string $namespace
	 * @param string $className
	 * @return string
	 */
	protected function buildFullClassName($namespace, $className)
	{
		if ($namespace != '')
		{
			return '\\' . $namespace . '\\' . $className;
		}
		return '\\' . $className;
	}

	/**
	 * Process the replacing file and extract its different classes and interfaces. On return, you get an array
	 * with keys corresponding to class names and an array containing the path/mtime of the file containing the
	 * class.
	 *
	 * @return array string
	 */
	protected function processReplacingFile()
	{
		$result = array();
		$extendNamespace = null;
		foreach ($this->replacingClassInfos as $replacingClassInfo)
		{
			$replacingExtractor = new \Change\Replacer\CodeExtractor($replacingClassInfo['path']);
			$fileInfo = new \SplFileInfo($replacingClassInfo['path']);
			$extendClassName = $this->originalClassInfo['name'] . self::REPLACED_CLASS_SUFFIX . ($this->replacerIndex++);

			// There should be only one namespace in the original file
			$namespaces = $replacingExtractor->getNamespaces();
			if (count($namespaces) != 1)
			{
				throw new \RuntimeException('file at ' . $replacingClassInfo['path'] . ' should only contain one namespace', 90002);
			}

			/* @var $namespace \Change\Replacer\Extractor\ExtractedNamespace */
			$namespace = $namespaces[0];

			// There should be only one class in the original file
			$classes = $namespace->getDeclaredClasses();
			if (count($classes) != 1)
			{
				throw new \RuntimeException('file at ' . $replacingClassInfo['path'] . ' should only contain one class', 90003);
			}
			/* @var $class \Change\Replacer\Extractor\ExtractedClass */
			$class = $classes[0];
			$class->setExtendedClassName($extendClassName);

			$usesArray = array();
			foreach ($namespace->getDeclaredUses() as $extractedUse)
			{
				/* @var $extractedUse \Change\Replacer\Extractor\ExtractedUse */
				$usesArray[] = $extractedUse->__toString();
			}
			$usesString = implode(PHP_EOL, $usesArray);

			/**
			 * We generate a first class, which is identical to the replacing class
			 * except for the name and that it extends the renamed version of the class it previously extended.
			 */

			$fullClassName = $this->buildFullClassName($class->getNamespace(), $class->getName());
			if ($fullClassName != $replacingClassInfo['name'])
			{
				throw new \RuntimeException('file at ' . $replacingClassInfo['path'] . ' should  contain class ' . $replacingClassInfo['name'], 90004);
			}
			// Append suffix to this class
			$fullClassName .=  self::REPLACING_CLASS_SUFFIX;
			$class->setName($class->getName() . self::REPLACING_CLASS_SUFFIX);

			$components = explode('\\', $fullClassName);
			$fileName = implode('_', $components);
			$path = $this->getWorkspace()->compilationPath('Replacer', $fileName . '.php');
			\Change\Stdlib\File::mkdir(dirname($path));

			$contentParts = array('<?php');
			if ($namespace->getName() != '')
			{
				$contentParts[] = 'namespace ' . $namespace->getName() . ';';
			}
			$contentParts[] = $usesString;
			$contentParts[] = $class->__toString();
			file_put_contents($path, implode(PHP_EOL, $contentParts));

			$result[$fullClassName] = array('path' => $path, 'mtime' => $fileInfo->getMTime());

			/**
			 * We generate a second class, which is completely empty with the name and namespace of the original file
			 * that extends the class generated above.
			 */
			$className = ($this->replacerIndex < count($this->replacingClassInfos)) ? $this->originalExtractedClass->getName() . self::REPLACED_CLASS_SUFFIX . $this->replacerIndex  : $this->originalExtractedClass->getName();

			$class->setExtendedClassName($fullClassName);
			$class->setName($className);
			$class->setBody('{}');

			$fullClassName = $this->buildFullClassName($this->originalExtractedClass->getNamespace(), $class->getName());

			$components = explode('\\', $fullClassName);
			$fileName = implode('_', $components);
			$path = $this->getWorkspace()->compilationPath('Replacer', $fileName . '.php');
			\Change\Stdlib\File::mkdir(dirname($path));

			$contentParts = array('<?php');
			if ($this->originalExtractedClass->getNamespace() != '')
			{
				$contentParts[] = 'namespace ' . $this->originalExtractedClass->getNamespace() . ';';
			}
			$contentParts[] = $class->__toString();
			file_put_contents($path, implode(PHP_EOL, $contentParts));
			$result[$fullClassName] = array('path' => $path, 'mtime' => $fileInfo->getMTime());
		}
		return $result;
	}

	/**
	 * If the informations passed on the classes involved in the replacer do not contain the path to the class and its mtime get them on the fly.
	 *
	 * @param array $info
	 */
	protected function completeInfo(array &$info)
	{
		if (!isset($info['path']))
		{
			$originalClassReflection = new \Zend\Code\Reflection\ClassReflection($info['name']);
			$info['path'] = $originalClassReflection->getFileName();
		}
		if (!isset($info['mtime']))
		{
			$fileInfo = new \SplFileInfo($info['path']);
			$info['mtime'] = $fileInfo->getMTime();
		}
	}

	/**
	 * This method performs the actual replacement generating the necessary files for replacement to work. It returns an array whose
	 * keys are the name of the generated classes and the values are arrays with :
	 *  - the key "path" containing the path of the file the original class was defined in
	 *  - the key "mtime" containing the modification time of the above file
	 *
	 * @return array
	 */
	public function compile()
	{
		$this->completeInfo($this->originalClassInfo);
		$result = array('source' => array($this->originalClassInfo['name'] => $this->originalClassInfo));
		foreach ($this->replacingClassInfos as &$info)
		{
			$this->completeInfo($info);
			$result['source'][$info['name']] = $info;
		}

		$result['compiled'] = $this->processOriginalFile();
		$result['compiled'] = array_merge($result['compiled'], $this->processReplacingFile());
		return $result;
	}
}