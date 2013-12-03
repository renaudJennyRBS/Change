<?php
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\StorageUri
 */
class StorageUri extends \Zend\Validator\AbstractValidator
{
	const INVALID = 'storageUriInvalid';

	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::INVALID => self::INVALID);
		parent::__construct($params);
	}
	
	/**
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		if (!is_string($value) || empty($value))
		{
			$this->setValue('');
			$this->error(self::INVALID);
			return false;
		}
		if (!preg_match('/^change:\/\/([A-Za-z0-9_]+)\/(.+)$/', $value))
		{
			$this->setValue($value);
			$this->error(self::INVALID);
			return false;
		}
		return true;
	}
}