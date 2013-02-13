<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Func
 * @api
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
	 * @param \Change\Db\Query\Expressions\AbstractExpression[] $arguments
	 */
	public function __construct($functionName = null, $arguments = array())
	{
		$this->setFunctionName($functionName);
		$this->setArguments($arguments);
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getFunctionName()
	{
		return $this->functionName;
	}
	
	/**
	 * @api
	 * @param string $functionName
	 */
	public function setFunctionName($functionName)
	{
		$this->functionName = is_null($functionName) ? null : strval($functionName);
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Expressions\AbstractExpression[]
	 */
	public function getArguments()
	{
		return $this->arguments;
	}
	
	/**
	 * @api
	 * @throws \InvalidArgumentException
	 * @param \Change\Db\Query\Expressions\AbstractExpression[] $arguments
	 */
	public function setArguments($arguments = array())
	{
		if (!is_array($arguments))
		{
			throw new \InvalidArgumentException('Argument 1 must be a Array');
		}
		if (count($arguments))
		{
			$this->arguments = array_map(function(AbstractExpression $item) {return $item;}, $arguments);
		}
		else
		{
			$this->arguments = array();
		}
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\AbstractExpression $argument
	 * @return \Change\Db\Query\Expressions\Func
	 */
	public function addArgument(\Change\Db\Query\Expressions\AbstractExpression $argument)
	{
		$this->arguments[] = $argument;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$argsString = array_map(function (AbstractExpression $element) {
			return $element->toSQL92String();
		}, $this->getArguments());
		return $this->getFunctionName() . '(' . implode(', ', $argsString) . ')';
	}
}