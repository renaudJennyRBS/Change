<?php
namespace Change\Db\Query;

/**
 * @name \Change\Db\Query\ResultsConverter
 */
class ResultsConverter
{
	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;
	
	/**
	 * @var array
	 */
	protected $fieldsTypes;
	
	
	public function __construct(\Change\Db\DbProvider $dbProvider, array $fieldsTypes)
	{
		$this->dbProvider = $dbProvider;
		$this->fieldsTypes = $fieldsTypes;
	}
	
	
	/**
	 * @param array $results
	 * @return array
	 */
	public function convertRows($results)
	{
		if (is_array($results))
		{
			$convertedRows = array();
			foreach ($results as $index => $row)
			{
				$convertedRows[$index] = $this->convertRow($row);
			}
			return $convertedRows;
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
			$convertedRow = array();
			foreach ($row as $name => $dbValue)
			{
				$convertedRow[$name] = (isset($scalarTypes[$name])) ?  $this->getValue($dbValue, $scalarTypes[$name]) : $dbValue;
			}
			return $convertedRow;
		}
		return null;
	}
	
	/**
	 * @param mixed $dbValue
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public function getValue($dbValue, $scalarType = \Change\Db\ScalarType::STRING)
	{
		return $this->dbProvider->dbToPhp($dbValue, $scalarType);
	}	
}