<?php

namespace Change\Db\Query\Expressions;

class Parameter extends AbstractExpression
{
	/**
	 * @var array
	 */
	protected $parameter;

	/**
	 * @param \Change\Db\Query\Objects\Table $table
	 * @param string $columnName
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