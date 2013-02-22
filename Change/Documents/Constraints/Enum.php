<?php
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\Enum
 */
class Enum extends \Zend\Validator\AbstractValidator
{
	const NOTINLIST = 'notInList';
	
	/**
	 * @var string
	 */
	protected $fromList;

	/**
	 * @var string
	 */
	protected $values;

 	/**
	 * @param array $params <fromList => modelName>
	 */   
	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::NOTINLIST => self::NOTINLIST);
		parent::__construct($params);
	}
	
	/**
	 * @return string
	 */
	public function getFromList()
	{
		return $this->fromList;
	}

	/**
	 * @param string $fromList
	 */
	public function setFromList($fromList)
	{
		$this->fromList = $fromList;
	}

	/**
	 * @return string
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @param string $values
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}

	/**
	 * @param  mixed $value
	 * @throws \LogicException
	 * @return boolean
	 */
	public function isValid($value)
	{
		$values = $this->getValues();
		$checkVal = trim($value);
		if (is_string($values) && $values !== '')
		{
			foreach (explode(',', $values) as $enumValue)
			{
				if (trim($enumValue) === $checkVal)
				{
					return true;
				}
			}

			$this->error(self::NOTINLIST);
			return false;
		}

		$fromList = $this->getFromList();
		if (is_string($fromList))
		{
			//@TODO Implement this when Change/list module is ready.
			throw new \LogicException('Not implemented', 10001);
			$this->error(self::NOTINLIST);
			return false;
		}
		return true;
	}	
}