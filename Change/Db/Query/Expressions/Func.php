<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Func
 */
class Func extends AbstractExpression
{
	/**
	 * @var string
	 */
	protected $functionName;
	
	/**
	 * @var array
	 */
	protected $arguments;
	
	/**
	 * @param string $functionName
	 * @param array $arguments
	 */
	public function __construct($functionName = null, $arguments = null)
	{
		$this->functionName = $functionName;
		$this->arguments = $arguments;
	}
	
	/**
	 * @return string
	 */
	public function getFunctionName()
	{
		return $this->functionName;
	}
	
	/**
	 * @param string $functionName
	 */
	public function setFunctionName($functionName)
	{
		$this->functionName = $functionName;
	}
	
	/**
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}
	
	/**
	 * @param array $arguments
	 */
	public function setArguments($arguments)
	{
		$this->arguments = $arguments;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$argsString = array_map(function (\Change\Db\Query\Expressions\AbstractExpression $element) {
			return $element->toSQL92String();
		}, $this->getArguments());
		return $this->getFunctionName() . '(' . implode(',', $argsString) . ')';
	}
}