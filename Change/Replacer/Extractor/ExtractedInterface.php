<?php
namespace Change\Replacer\Extractor;

/**
 * @name \Change\Replacer\Extractor\ExtractedInterface
 */
class ExtractedInterface
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
	 * Name of the extracted interface
	 *
	 * @var string
	 */
	protected $extendedInterfaceName;

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
	public function getExtendedInterfaceName()
	{
		return $this->extendedInterfaceName;
	}

	/**
	 * @param string $extendedInterfaceName
	 */
	public function setExtendedInterfaceName($extendedInterfaceName)
	{
		$this->extendedInterfaceName = $extendedInterfaceName;
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
		$interfaceDeclaration = array();
		if ($this->abstract)
		{
			$interfaceDeclaration[] = 'abstract';
		}
		$interfaceDeclaration[] = 'class';
		$interfaceDeclaration[] = $this->name;
		if ($this->extendedInterfaceName)
		{
			$interfaceDeclaration[] = 'extends';
			$interfaceDeclaration[] = $this->extendedInterfaceName;
		}
		return implode(' ', $interfaceDeclaration) . $this->body;
	}
}