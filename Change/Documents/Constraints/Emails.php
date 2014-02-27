<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @param array $params <property => property, document => document>
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