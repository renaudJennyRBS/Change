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
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$fromList = $this->getFromList();
		//TODO Load Change_List_List by code and check if contains $valuevalue

		if (false)
		{
			$this->error(self::NOTINLIST);
			return false;
		}
		return true;
	}	
}