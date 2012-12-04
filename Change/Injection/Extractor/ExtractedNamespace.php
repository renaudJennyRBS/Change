<?php
namespace Change\Injection\Extractor;

/**
 * @name \Change\Injection\Extractor\ExtractedNamespace
 */
class ExtractedNamespace
{
	/**
	 * Extracted namespace name
	 * 
	 * @var string
	 */
	protected $name;
	
	/**
	 * Extracted use declarations
	 * 
	 * @var ExtractedUse[]
	 */
	protected $declaredUses = array();
	
	/**
	 * Extracted use declarations
	 *
	 * @var ExtractedClass[]
	 */
	protected $declaredClasses = array();
	
	/**
	 * Extracted use declarations
	 *
	 * @var ExtractedInterface[]
	 */
	protected $declaredInterfaces = array();

	
	/**
	 * @param \Change\Injection\Extractor\ExtractedUse $declaredUse
	 */
	public function addDeclaredUse($declaredUse)
	{
		$this->declaredUses[] = $declaredUse;
	}

	/**
	 * @param \Change\Injection\Extractor\ExtractedClass $declaredClass
	 */
	public function addDeclaredClass($declaredClass)
	{
		$this->declaredClasses[$declaredClass->getName()] = $declaredClass;
	}

	/**
	 * @param \Change\Injection\Extractor\ExtractedInterface $declaredInterface
	 */
	public function addDeclaredInterface($declaredInterface)
	{
		$this->declaredInterfaces[$declaredInterface->getName()] = $declaredInterface;
	}
	
	/**
	 * @return \Change\Injection\Extractor\ExtractedUse[]
	 */
	public function getDeclaredUses()
	{
		return $this->declaredUses;
	}

	/**
	 * @return \Change\Injection\Extractor\ExtractedClass[]
	 */
	public function getDeclaredClasses()
	{
		return array_values($this->declaredClasses);
	}
	
	/**
	 * @return \Change\Injection\Extractor\ExtractedClass
	 */
	public function getDeclaredClass($name)
	{
		return $this->declaredClasses[$name];
	}

	/**
	 * @return \Change\Injection\Extractor\ExtractedInterface[]
	 */
	public function getDeclaredInterfaces()
	{
		return array_values($this->declaredInterfaces);
	}
	
	/**
	 * @return \Change\Injection\Extractor\ExtractedInterface
	 */
	public function getDeclaredInterface($name)
	{
		return $this->declaredInterfaces[$name];
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
}