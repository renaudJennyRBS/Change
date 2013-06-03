<?php
namespace Change\Replacer;

/**
 * @name \Change\Replacer\CodeExtractor
 */
class CodeExtractor
{
	/**
	 * @var array
	 */
	protected $extractionResult = array();
	
	/**
	 * 
	 * @param string $path
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function __construct($path)
	{
		$content = null;
		if (is_string($path))
		{
			\Zend\Stdlib\ErrorHandler::start();
			$content = file_get_contents($path);
			$error = \Zend\Stdlib\ErrorHandler::stop();
			if ($content === false || $error)
			{
				throw new \RuntimeException('Cannot open ' . $path . ' for reading', 90005, $error);
			}
		}
		else
		{
			throw new \InvalidArgumentException('Argument must be a string representing a valid filesystem path', 90006);
		}
		$this->extractionResult = $this->extract($content);
	}
	
	/**
	 * @return \Change\Replacer\Extractor\ExtractedNamespace[]
	 */
	public function getNamespaces()
	{
		return array_values($this->extractionResult);	
	}
	
	/**
	 * @param string $nsName
	 * @return \Change\Replacer\Extractor\ExtractedNamespace
	 */
	public function getNamespace($nsName)
	{
		return $this->extractionResult[$nsName];
	}
	
	/**
	 * Does the namespace exist in the parsed file
	 * 
	 * @param string $nsName
	 * @return bool
	 */
	public function hasNamespace($nsName)
	{
		return isset($this->extractionResult[$nsName]);
	}
	
	/**
	 * Parses the given source code to extract classes and interfaces 
	 * 
	 * @param string $source
	 * @return array
	 */
	protected function extract($source)
	{
		$currentNamespace = '';
		$currentClassContent = array();
		$currentClassName = null;
		$currentClassExtends = null;
		$currentClassImplements = array();
		$currentUses = array();
		$currentUse = '';
		$currentInterfaceName = '';
		$currentClassIsAbstract = false;
		$currentClassIsInterface = false;
		
		
		$inUse = false;
		$inBrace = 0;
		$inNamespace = false;
		$inClass = false;
		$inImplements = false;
		$inExtends = false;
		
		$classDeclaration = false;
		$currentClassComment = null;
		$tokens = token_get_all($source);
		$size = count($tokens);
		$result = array();
		for ($index = 0; $index < $size; $index++)
		{
			$token = $tokens[$index];
			if (!is_array($token))
			{
				switch ($token)
				{
					case '{' :
						$inNamespace = false;
						if ($classDeclaration && $inBrace == 0)
						{
							if ($inImplements)
							{
								$currentClassImplements[] = $currentInterfaceName;
								$currentInterfaceName = '';
							}
							$classDeclaration = false;
							$inImplements = false;
							$inExtends = false;
							$inClass = true;
						}
						$inBrace++;
						break;
					case '}' :
						$inBrace--;
						break;
					case ';' : 
						$inNamespace = false;
						if ($inUse)
						{
							$inUse = false;
							$currentUses[] = $currentUse;
							$currentUse = '';
						}
						break;
					case ',' :
						if ($inImplements)
						{
							$currentClassImplements[] = $currentInterfaceName;
							$currentInterfaceName = '';
						}
				}
				if ($inClass)
				{
					$currentClassContent[] = $token;
					if ($inClass && $inBrace == 0)
					{
						$inClass = false;
						if (!isset($result[$currentNamespace]))
						{
							$namespace = new \Change\Replacer\Extractor\ExtractedNamespace();
							$namespace->setName($currentNamespace);
							$result[$currentNamespace] = $namespace;
						}
						
						/* @var $namespace \Change\Replacer\Extractor\ExtractedNamespace */
						$namepace = $result[$currentNamespace];
						
						if ($currentClassIsInterface)
						{
							$interface = new \Change\Replacer\Extractor\ExtractedInterface();
							$interface->setBody(implode('', $currentClassContent));
							$interface->setExtendedInterfaceName($currentClassExtends);
							$interface->setName($currentClassName);
							$interface->setNamespace($namespace->getName());
							$namepace->addDeclaredInterface($interface);
						}
						else
						{
							$class = new \Change\Replacer\Extractor\ExtractedClass();
							$class->setBody(implode('', $currentClassContent));
							$class->setExtendedClassName($currentClassExtends);
							$class->setName($currentClassName);
							$class->setImplementedInterfaceNames($currentClassImplements);
							$class->setAbstract($currentClassIsAbstract);
							$class->setNamespace($namespace->getName());
							$namepace->addDeclaredClass($class);
						}
						
						foreach ($currentUses as $declaration)
						{
							$extractedUse = new \Change\Replacer\Extractor\ExtractedUse();
							$extractedUse->setDeclaration($declaration);
							$namepace->addDeclaredUse($extractedUse);
						}
						$currentUses = array();
 						$currentClassContent = array();
						$currentClassName = null;
						$currentClassExtends = null;
						$currentClassIsAbstract = false;
						$currentClassImplements = array();
						$currentClassIsInterface = false;
					}
				}
			}
			else
			{
				switch ($token[0])
				{
					case T_DOC_COMMENT :
						if (!$inClass)
						{
							$currentClassComment = $token[1];
						}
						break;
					case T_ABSTRACT :
						if (!$inClass)
						{
							$classDeclaration = true;
							$currentClassIsAbstract = true;
						}
						break;
					case T_INTERFACE :
						$currentClassIsInterface = true;
						$classDeclaration = true;
						$currentClassName = $tokens[$index + 2][1];
						$inBrace = 0;
						break;
					case T_IMPLEMENTS :
						$inExtends = false;
						$inImplements = true;
						$currentClassImplements = array();
						break;
					case T_CLASS :	
						$classDeclaration = true;
						$currentClassName = $tokens[$index + 2][1];
						$inBrace = 0;
						break;
					case T_EXTENDS :
						$inExtends = true;
						break;
					case T_NAMESPACE :
						$inNamespace = true;
						$currentNamespace = "";
						$currentUses = array();
						break;
					case T_STRING :
						if ($inNamespace)
						{
							$currentNamespace .= $token[1];
						}
						if ($inImplements)
						{
							$currentInterfaceName .= $token[1];
						}
						if ($inExtends)
						{
							$currentClassExtends .= $token[1];
						}
						if ($inUse)
						{
							$currentUse .= $token[1];
						}
						break;
					case T_USE;
						if (!$inClass)
						{
							$inUse = true;
							$currentUse = '';
						}
						break;
					case T_AS;
						if ($inUse)
						{
							$currentUse .= ' ' .  $token[1] . ' ';
						}
						break;
					case T_NS_SEPARATOR :
						if ($inNamespace)
						{
							$currentNamespace .= $token[1];
						}
						if ($inImplements)
						{
							$currentInterfaceName .= $token[1];
						}
						if ($inExtends)
						{
							$currentClassExtends .= $token[1];
						}
						if ($inUse)
						{
							$currentUse .= $token[1];
						}
						break;
				}
				if ($inClass)
				{
					$currentClassContent[] = $token[1];
				}
			}
		}
		return $result;
	}
}