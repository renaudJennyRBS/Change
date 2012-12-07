<?php

namespace Change\Db\Query\Expressions;

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
	 * @return multitype:
	 */
	public function getArguments()
	{
		return $this->arguments;
	}

	/**
	 * @param multitype: $arguments
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
		$argsString = array_map(function(\Change\Db\Query\Expressions\AbstractExpression $element){
			return $element->toSQL92String();
		}, $this->getArguments());
		return $this->getFunctionName() . '(' . implode(',', $argsString) . ')';
	}
}