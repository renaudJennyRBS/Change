<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Value
 */
class Value extends AbstractExpression
{
	/**
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * @var integer
	 */
	protected $scalarType;
	
	/**
	 * @return integer \Change\Db\ScalarType::*
	 */
	public function getScalarType()
	{
		return $this->scalarType;
	}

	/**
	 * @param integer $scalarType
	 */
	public function setScalarType($scalarType)
	{
		switch ($scalarType) 
		{
			case \Change\Db\ScalarType::BOOLEAN:
			case \Change\Db\ScalarType::DATETIME:
			case \Change\Db\ScalarType::DECIMAL:
			case \Change\Db\ScalarType::INTEGER:
			case \Change\Db\ScalarType::LOB:
			case \Change\Db\ScalarType::STRING:
			case \Change\Db\ScalarType::TEXT:
				$this->scalarType = $scalarType;
				break;
		}
		
	}

	/**
	 * @param mixed $value
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 */
	public function __construct($value = null, $scalarType = \Change\Db\ScalarType::STRING)
	{
		$this->value = $value;
		$this->setScalarType($scalarType);
	}
	
	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
	
	/**
	 * @return boolean
	 */
	public function isNull()
	{
		return ($this->value === null);
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->value === null)
		{
			return 'NULL';
		}
		return "'" . str_replace('\'', '\\\'', strval($this->value)) . "'";
	}
}