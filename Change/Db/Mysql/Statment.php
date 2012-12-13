<?php
namespace Change\Db\Mysql;

/**
 * @name \Change\Db\Mysql\Statment
 */
class Statment extends \Change\Db\AbstractStatment
{
	/**
	 * @var \PDOStatement
	 */
	private $stmt;
	
	/**
	 * @var \Change\Db\Mysql\Provider
	 */
	private $provider;
	
	/**
	 * @var string
	 */
	private $errorMessage;
	

	/**
	 * @throws \Exception
	 * @return \PDOStatement
	 */
	public function getPDOStatment()
	{
		if ($this->stmt === null)
		{
			$pdo = $this->provider->getDriver();
			$this->stmt = $pdo->prepare($this->sql);
			if ($this->stmt === false)
			{
				$errorCode = $pdo->errorCode();
				$this->errorMessage = "Driver ERROR Code (" . $errorCode . ") : " . var_export($pdo->errorInfo(), true);
				throw new \Exception($this->errorMessage);
			}
		}
		elseif ($this->stmt === false)
		{
			$this->errorMessage = "Statment already closed.";
			throw new \Exception($this->errorMessage);
		}
		return $this->stmt;
	}
	
	/**
	 * @param \Change\Db\Mysql\Provider $provider
	 * @param string $sql
	 * @param \Change\Db\StatmentParameter[] $parameters
	 */
	public function __construct($provider, $sql, $parameters = null)
	{
		$this->provider = $provider;
		parent::__construct($sql, $parameters);
	}
	
	/**
	 * @param \Change\Db\StatmentParameter $parameter
	 * @return \Change\Db\Mysql\Statment
	 */
	public function addParameter(\Change\Db\StatmentParameter $parameter)
	{
		$this->getPDOStatment()->bindValue($parameter->getName(), $parameter->getValue(), $this->getStatmentType($parameter->getType()));
		return $this;
	}
	
	/**
	 * @return void
	 */
	public function close()
	{
		if ($this->stmt !== false && $this->stmt !== null)
		{
			$this->stmt->closeCursor();
		}
		$this->provider = null;
		$this->stmt = false;
	}
	
	/**
	 * @param string $parameterName
	 * @param mixed $value
	 * @param string $type
	 */
	public function bindValue($parameterName, $value, $type = null)
	{
		$this->getPDOStatment()->bindValue($parameterName, $value, $this->getStatmentType($type));
	}
	
	/**
	 * @param string $parameterName
	 * @param PropertyInfo $propertyInfo
	 * @param mixed $value
	 * @return string
	 */
	public function bindPropertyValue(\PropertyInfo $propertyInfo, $value)
	{
		$name = ':p' . $propertyInfo->getName();
		switch ($propertyInfo->getType())
		{
			case \Change\Documents\Property::TYPE_DATETIME:
				if (empty($value))
				{
					$this->bindValue($name, null, \Change\Db\StatmentParameter::NIL);
				}
				else if (is_long($value))
				{
					$this->bindValue($name, date("Y-m-d H:i:s", $value), \Change\Db\StatmentParameter::STR);
				}
				else
				{
					$this->bindValue($name, $value, \Change\Db\StatmentParameter::STR);
				}
				break;
			case \Change\Documents\Property::TYPE_BOOLEAN :
				$this->bindValue($name, $value ? 1 : 0, \Change\Db\StatmentParameter::INT);
				break;
			case \Change\Documents\Property::TYPE_INTEGER :
				if ($value === null)
				{
					$this->bindValue($name, null, \Change\Db\StatmentParameter::NIL);
				}
				else
				{
					$this->bindValue($name, $value, \Change\Db\StatmentParameter::INT);
				}
				break;
			default :
				if ($value === null)
				{
					$this->bindValue($name, $value, \Change\Db\StatmentParameter::NIL);
				}
				else
				{
					$this->bindValue($name, strval($value), \Change\Db\StatmentParameter::STR);
				}
				break;
		}
		return $name;
	}
	
	/**
	 * @param \Change\Db\StatmentParameter[] $parameters
	 * @return boolean
	 */
	public function execute($parameters = null)
	{
		if (is_array($parameters))
		{
			foreach ($parameters as $parameter)
			{
				if ($parameter instanceof \Change\Db\StatmentParameter)
				{
					$this->addParameter($parameter);
				}
			}
		}
		$stmt = $this->getPDOStatment();
		if (!$stmt->execute() && $this->stmt->errorCode() != '00000')
		{
			$errorCode = $this->stmt->errorCode();
			$this->errorMessage = "Driver ERROR Code (" . $errorCode . ") : " . var_export($this->stmt->errorInfo(), true);
			return false;
		}
		return true;
	}
	
	/**
	 * @return string|null
	 */
	public function getErrorMessage()
	{
		return $this->errorMessage;
	}
	
	/**
	 * @param string $mode \Change\Db\AbstractStatment::FETCH_*
	 * @return array|false
	 */
	public function fetch($mode)
	{
		return $this->stmt->fetch($this->getStatmentFetchMode($mode));
	}
	
	/**
	 * @param string $mode \Change\Db\AbstractStatment::FETCH_*
	 * @return array
	 */
	public function fetchAll($mode)
	{
		return $this->stmt->fetchAll($this->getStatmentFetchMode($mode));
	}
	
	/**
	 * 
	 * @param integer $columnNumber
	 * @return string|false
	 */
	public function fetchColumn($columnNumber = 0)
	{
		return $this->stmt->fetchColumn($columnNumber);
	}
	
	public function closeCursor()
	{
		$this->close();
	}
	
	/**
	 * @return integer
	 */	
	public function rowCount()
	{
		return $this->stmt->rowCount();
	}
	
	/**
	 * @param string|integer $mode
	 * @return integer
	 */
	private function getStatmentType($type)
	{
		switch ($type)
		{
			case \PDO::PARAM_INT:
			case \Change\Db\StatmentParameter::INT :
				return \PDO::PARAM_INT;
				
			case \PDO::PARAM_NULL:
			case \Change\Db\StatmentParameter::NIL :
				return \PDO::PARAM_NULL;
				
			case \PDO::PARAM_STR:
			case \Change\Db\StatmentParameter::STR :
			case \Change\Db\StatmentParameter::DATE :
			case \Change\Db\StatmentParameter::LOB :
			case \Change\Db\StatmentParameter::FLOAT :
				return \PDO::PARAM_STR;
		}
		return \PDO::PARAM_STR;
	}
	
	/**
	 * @param string|integer $mode
	 * @return integer
	 */
	private function getStatmentFetchMode($mode)
	{
		switch ($mode)
		{
			case \PDO::FETCH_NUM:
			case \Change\Db\AbstractStatment::FETCH_NUM :
				return \PDO::FETCH_NUM;
				
			case \PDO::FETCH_COLUMN:	
			case \Change\Db\AbstractStatment::FETCH_COLUMN :
				return \PDO::FETCH_COLUMN;
		}
		return \PDO::FETCH_ASSOC;
	}
	
	public function __destruct()
	{
		$this->close();
	}
}