<?php

namespace Change\Injection\Extractor;

class ExtractedClass
{
	/**
	 * Body of the extracted class
	 * 
	 * @var string
	 */
	protected $body;
	
	/**
	 * Name of the extracted class
	 * 
	 * @var string
	 */
	protected $name;
	
	/**
	 * Namespace of the extracted class
	 * 
	 * @var string
	 */
	protected $namespace;
	
	/**
	 * Name of the extracted class
	 *
	 * @var string
	 */
	protected $extendedClassName;
	
	/**
	 * Abstract nature of the class
	 * 
	 * @var bool
	 */
	protected $abstract;
	
	/**
	 * Names of the implemented interfaces
	 *
	 * @var array
	 */
	protected $implementedInterfaceNames;

	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param string $body
	 */
	public function setBody($body)
	{
		$trimmedBody = trim($body);
		if (substr($trimmedBody, 0, 1) != '{' || substr($trimmedBody, -1, 1) != '}')
		{
			throw new \InvalidArgumentException('body should start with an open brace and end with a closing brace');
		}
		$this->body = $body;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getExtendedClassName()
	{
		return $this->extendedClassName;
	}

	/**
	 * @param string $extendedClassName
	 */
	public function setExtendedClassName($extendedClassName)
	{
		$this->extendedClassName = $extendedClassName;
	}

	/**
	 * @return boolean
	 */
	public function getAbstract()
	{
		return $this->abstract;
	}

	/**
	 * @param boolean $abstract
	 */
	public function setAbstract($abstract)
	{
		$this->abstract = $abstract;
	}

	/**
	 * @return array
	 */
	public function getImplementedInterfaceNames()
	{
		return $this->implementedInterfaceNames;
	}

	/**
	 * @param array $implementedInterfaceNames
	 */
	public function setImplementedInterfaceNames($implementedInterfaceNames)
	{
		$this->implementedInterfaceNames = $implementedInterfaceNames;
	}

	/**
	 * @return string
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}

	/**
	 * @param string $namespace
	 */
	public function setNamespace($namespace)
	{
		$this->namespace = $namespace;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		if (empty($this->body))
		{
			throw new \RuntimeException('this class has no body');
		}
		
		if (empty($this->name))
		{
			throw new \RuntimeException('this class has no name');
		}
		
		$classDeclarationParts = array();
		if ($this->abstract)
		{
			$classDeclarationParts[] = 'abstract';
		}
		$classDeclarationParts[] = 'class';
		$classDeclarationParts[] = $this->name;
		if ($this->extendedClassName)
		{
			$classDeclarationParts[] = 'extends';
			$classDeclarationParts[] = $this->extendedClassName;
		}
		if (count($this->implementedInterfaceNames))
		{
			$classDeclarationParts[] = 'implements';
			$classDeclarationParts[] = implode(', ', $this->implementedInterfaceNames);
		}
		return implode(' ', $classDeclarationParts) . PHP_EOL . $this->body;
	}
}