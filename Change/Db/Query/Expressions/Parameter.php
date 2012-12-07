<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Parameter
 */
class Parameter extends AbstractExpression
{
	/**
	 * @var string
	 */
	protected $parameter;
	
	/**
	 * @param string $parameter
	 */
	public function __construct($parameter = null)
	{
		$this->parameter = $parameter;
	}
	
	/**
	 * @return string
	 */
	public function getParameter()
	{
		return $this->parameter;
	}
	
	/**
	 * @param string $parameter
	 */
	public function setParameter($parameter)
	{
		$this->parameter = $parameter;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return ':' . $this->getParameter();
	}
}