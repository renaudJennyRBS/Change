<?php
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\Emails
 */
class Emails extends \Zend\Validator\AbstractValidator
{
	const INVALID = 'emailsAddressInvalid';
	
	/**
	 * @var \Zend\Validator\AbstractValidator
	 */
	protected $emailConstraint;
	
	/**
	 * @param array $params <modelName => modelName, propertyName => propertyName, [documentId => documentId]>
	 */
	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::INVALID => self::INVALID);
		$this->emailConstraint = $params['emailConstraint'];
		unset($params['emailConstraint']);
		parent::__construct($params);
	}
	
	/**
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $emailErrors = array();
        $emailArray = array_map('trim', explode(',', $value));
        foreach ($emailArray as $email)
        {
        	if (!$this->emailConstraint->isValid($email))
        	{
        		$emailErrors[] = $email;
        	}
        }
        
        if (count($emailErrors))
        {
        	$this->setValue(implode(', ', $emailErrors));
        	$this->error(self::INVALID);
        	return false;
        }
		return true;
	}
}