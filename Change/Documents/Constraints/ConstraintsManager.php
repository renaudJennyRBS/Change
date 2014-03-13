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
 * @name \Change\Documents\Constraints\ConstraintsManager
 * @api
 */
class ConstraintsManager
{
	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager = null;

	/**
	 * @var array
	 */
	protected $defaultConstraint;

	public function __construct()
	{
		$this->defaultConstraint = array(
			'domain' => '\Change\Documents\Constraints\Domain',
			'url' => '\Change\Documents\Constraints\Url',
			'unique' => '\Change\Documents\Constraints\Unique',
			'enum' => '\Change\Documents\Constraints\Enum',
			'storageUri' => '\Change\Documents\Constraints\StorageUri',
			'publicationStatus' => '\Change\Documents\Constraints\PublicationStatus');
	}

	public function shutdown()
	{
		\Zend\Validator\AbstractValidator::setDefaultTranslatorTextDomain();
		\Zend\Validator\AbstractValidator::setDefaultTranslator();
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager(\Change\I18n\I18nManager $i18nManager)
	{
		$this->i18nManager = $i18nManager;
		$this->registerDefaultTranslator();
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	protected function registerDefaultTranslator()
	{
		if (\Zend\Validator\AbstractValidator::getDefaultTranslatorTextDomain() !== 'c.constraints')
		{
			\Zend\Validator\AbstractValidator::setDefaultTranslatorTextDomain('c.constraints');
			$t = Translator::factory(array());
			if ($t instanceof Translator)
			{
				$t->setI18nManager($this->getI18nManager());
				\Zend\Validator\AbstractValidator::setDefaultTranslator($t);
			}
		}
	}

	/**
	 * @param string $name
	 * @param array $params
	 * @throws \InvalidArgumentException
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function getByName($name, $params = array())
	{
		if ($this->hasDefaultConstraint($name))
		{
			$className = $this->defaultConstraint[$name];
			if (class_exists($className))
			{
				$constraint = new $className($params);
				if ($constraint instanceof \Zend\Validator\ValidatorInterface)
				{
					return $this->fixMessageTemplates($constraint);
				}
			}
		}
		else
		{
			$parts = explode('.', $name);
			$caller = array($this, array_shift($parts) . implode('', array_map('ucfirst', $parts)));
			if (is_callable($caller))
			{
				$constraint = call_user_func($caller, $params);
				if ($constraint instanceof \Zend\Validator\ValidatorInterface)
				{
					return $this->fixMessageTemplates($constraint);
				}
			}
		}
		throw new \InvalidArgumentException('Constraint '. $name . ' not found', 52002);
	}

	/**
	 * @param \Zend\Validator\ValidatorInterface $constraint
	 * @return \Zend\Validator\ValidatorInterface
	 */
	protected function fixMessageTemplates($constraint)
	{
		if ($constraint instanceof \Zend\Validator\AbstractValidator)
		{
			foreach ($constraint->getMessageTemplates() as $messageKey => $unused)
			{
				$constraint->setMessage(strtolower($messageKey), $messageKey);
			}
		}
		return $constraint;
	}
	
	/**
	 * @api 
	 * @param string $name
	 * @param string $class
	 */
	public function registerConstraint($name, $class)
	{
		$this->defaultConstraint[$name] = $class;
	}
	
	/**
	 * @api 
	 * @param string $name
	 * @return boolean
	 */
	public function hasDefaultConstraint($name)
	{
		return isset($this->defaultConstraint[$name]);
	}
	

	/**
	 * @param array $params <type => integer>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function required($params = array())
	{
		return new \Zend\Validator\NotEmpty($params);
	}
	
	/**
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function email($params = array())
	{	
		$params['hostname'] = self::hostname($params);
		return new \Zend\Validator\EmailAddress($params);
	}
	
	/**
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function emails($params = array())
	{
		$params['emailConstraint'] = $this->email($params);
		return new Emails($params);
	}	
	
	/**
	 * @param array $params <max => maxLength>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function maxSize($params = array())
	{		
		return new \Zend\Validator\StringLength($params);
	}	
	
	/**
	 * @param array $params <min => minLength>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function minSize($params = array())
	{		
		return new \Zend\Validator\StringLength($params);
	}
	
	/**
	 * @param array $params <pattern => pattern, [message => message]>
	 * @return \Zend\Validator\ValidatorInterface
	 */	
	public function matches($params = array())
	{
		$c = new \Zend\Validator\Regex($params);
		if (isset($params['message']) && is_string($params['message']))
		{
			$c->setMessage($params['message']);
		}
		else
		{
			$c->setMessage('notMatch');
		}
		return $c;
	}
	
	/**
	 * @param array $params <min => min>
	 * @return \Zend\Validator\ValidatorInterface
	 */	
	public function min($params = array())
	{
		return new Min($params);
	}

	/**
	 * @param array $params <max => max>
	 * @return \Zend\Validator\ValidatorInterface
	 */	
	public function max($params = array())
	{
		return new Max($params);
	}

	/**
	 * @param array $params <min => min, max => max, [inclusive => inclusive]>
	 * @return \Zend\Validator\ValidatorInterface
	 */	
	public function range($params = array())
	{
		return new \Zend\Validator\Between($params);
	}
	
	/**
	 * @param array $params<allow => \Zend\Validator\Hostname::ALLOW_*>
	 * @return \Zend\Validator\Hostname
	 */
	public function hostname($params = array())
	{
		return new \Zend\Validator\Hostname($params);
	}
	
	/**
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function integer($params = array())
	{
		return new \Zend\Validator\Digits($params);	
	}	
}