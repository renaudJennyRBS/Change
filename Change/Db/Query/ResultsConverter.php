<?php
namespace Change\Db\Query;

use Change\Db\ScalarType;

/**
 * @api
 * @name \Change\Db\Query\ResultsConverter
 */
class ResultsConverter
{
	/**
	 * @var \Callable
	 */
	protected $converter;

	/**
	 * @var array
	 */
	protected $fieldsTypes;

	/**
	 * @var bool
	 */
	protected $singleColumn = false;

	/**
	 * @param Callable|\Change\Db\DbProvider $converter
	 * @param array $fieldsTypes
	 * @throws \InvalidArgumentException
	 * @return \Change\Db\Query\ResultsConverter
	 */
	public function __construct($converter, array $fieldsTypes = array())
	{
		if ($converter instanceof \Change\Db\DbProvider)
		{
			$this->converter = function ($dbValue, $dbType) use ($converter)
			{
				return $converter->dbToPhp($dbValue, $dbType);
			};
		}
		elseif (is_callable($converter))
		{
			$this->converter = $converter;
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 must be a valid Closure or DbProvider', 999999);
		}
		$this->setFieldsTypes($fieldsTypes);
	}

	/**
	 * Add String Column
	 * @api
	 * @param string $name1
	 * @param string $_ [optional]
	 * @return $this
	 */
	public function addStrCol($name1, $_ = null)
	{
		foreach (func_get_args() as $name)
		{
			if (is_string($name) && $name)
			{
				$this->fieldsTypes[$name] = ScalarType::STRING;
				$this->singleColumn = (count($this->fieldsTypes) === 1) ? $name : false;
			}
		}
		return $this;
	}

	/**
	 * Add DateTime Column
	 * @api
	 * @param string $name1
	 * @param string $_ [optional]
	 * @return $this
	 */
	public function addDtCol($name1, $_ = null)
	{
		foreach (func_get_args() as $name)
		{
			if (is_string($name) && $name)
			{
				$this->fieldsTypes[$name] = ScalarType::DATETIME;
				$this->singleColumn = (count($this->fieldsTypes) === 1) ? $name : false;
			}
		}
		return $this;
	}

	/**
	 * Add Integer Column
	 * @api
	 * @param string $name1
	 * @param string $_ [optional]
	 * @return $this
	 */
	public function addIntCol($name1, $_ = null)
	{
		foreach (func_get_args() as $name)
		{
			if (is_string($name) && $name)
			{
				$this->fieldsTypes[$name] = ScalarType::INTEGER;
				$this->singleColumn = (count($this->fieldsTypes) === 1) ? $name : false;
			}
		}
		return $this;
	}

	/**
	 * Add Decimal Column
	 * @api
	 * @param string $name1
	 * @param string $_ [optional]
	 * @return $this
	 */
	public function addNumCol($name1, $_ = null)
	{
		foreach (func_get_args() as $name)
		{
			if (is_string($name) && $name)
			{
				$this->fieldsTypes[$name] = ScalarType::DECIMAL;
				$this->singleColumn = (count($this->fieldsTypes) === 1) ? $name : false;
			}
		}
		return $this;
	}

	/**
	 * Add Boolean Column
	 * @api
	 * @param string $name1
	 * @param string $_ [optional]
	 * @return $this
	 */
	public function addBoolCol($name1, $_ = null)
	{
		foreach (func_get_args() as $name)
		{
			if (is_string($name) && $name)
			{
				$this->fieldsTypes[$name] = ScalarType::BOOLEAN;
				$this->singleColumn = (count($this->fieldsTypes) === 1) ? $name : false;
			}
		}
		return $this;
	}

	/**
	 * Add Boolean Column
	 * @api
	 * @param string $name1
	 * @param string $_ [optional]
	 * @return $this
	 */
	public function addTxtCol($name1, $_ = null)
	{
		foreach (func_get_args() as $name)
		{
			if (is_string($name) && $name)
			{
				$this->fieldsTypes[$name] = ScalarType::TEXT;
				$this->singleColumn = (count($this->fieldsTypes) === 1) ? $name : false;
			}
		}
		return $this;
	}

	/**
	 * Add Boolean Column
	 * @api
	 * @param string $name1
	 * @param string $_ [optional]
	 * @return $this
	 */
	public function addLobCol($name1, $_ = null)
	{
		foreach (func_get_args() as $name)
		{
			if (is_string($name) && $name)
			{
				$this->fieldsTypes[$name] = ScalarType::LOB;
				$this->singleColumn = (count($this->fieldsTypes) === 1) ? $name : false;
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @param array $fieldsTypes
	 * @return $this
	 */
	public function setFieldsTypes(array $fieldsTypes)
	{
		if (\Zend\Stdlib\ArrayUtils::isHashTable($fieldsTypes))
		{
			$this->fieldsTypes = $fieldsTypes;
			if (count($this->fieldsTypes) === 1)
			{
				reset($this->fieldsTypes);
				$this->singleColumn(key($this->fieldsTypes));
			}
		}
		else
		{
			$this->fieldsTypes = array();
			$this->singleColumn = false;
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFieldsTypes()
	{
		return $this->fieldsTypes;
	}

	/**
	 * @api
	 * @param string|boolean $name
	 * @return $this
	 */
	public function singleColumn($name)
	{
		if (is_string($name) && isset($this->fieldsTypes[$name]))
		{
			$this->singleColumn = $name;
		}
		else
		{
			$this->singleColumn = false;
		}
		return $this;
	}

	function __invoke(array $data)
	{
		if (count($this->fieldsTypes))
		{
			if (\Zend\Stdlib\ArrayUtils::isList($data))
			{
				return array_map(array($this, 'normalizeRow'), $data);
			}
			else
			{
				return $this->normalizeRow($data);
			}
		}
		return $data;
	}

	/**
	 * @param array $row
	 * @return array|mixed|null
	 */
	protected function normalizeRow($row)
	{
		$converter = $this->converter;
		$scTypes = $this->fieldsTypes;
		if ($this->singleColumn)
		{
			if (isset($row[$this->singleColumn]))
			{
				return $converter($row[$this->singleColumn], $scTypes[$this->singleColumn]);
			}
			return null;
		}
		else
		{
			$cRow = array();
			foreach ($scTypes as $name => $dbType)
			{
				$cRow[$name] = isset($row[$name]) ? $converter($row[$name], $dbType) : null;
			}
			return $cRow;
		}
	}

	/**
	 * @param array $results
	 * @return array
	 */
	public function convertRows($results)
	{
		if (is_array($results))
		{
			if (is_array($this->fieldsTypes))
			{
				$convertedRows = array();
				foreach ($results as $index => $row)
				{
					$convertedRows[$index] = $this->convertRow($row);
				}
				return $convertedRows;
			}
			return $results;
		}
		return null;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function convertRow($row)
	{
		if (is_array($row))
		{
			$scalarTypes = $this->fieldsTypes;
			if (is_array($scalarTypes))
			{
				$convertedRow = array();
				foreach ($row as $name => $dbValue)
				{
					$convertedRow[$name] = (isset($scalarTypes[$name])) ? $this->getValue($dbValue,
						$scalarTypes[$name]) : $dbValue;
				}
				return $convertedRow;
			}
			return $row;
		}
		return null;
	}

	/**
	 * @param mixed $dbValue
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public function getValue($dbValue, $scalarType = ScalarType::STRING)
	{
		$converter = $this->converter;
		return $converter($dbValue, $scalarType);
	}
}