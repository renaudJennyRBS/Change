<?php
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\ConstraintsManager
 * @api
 */
class ConstraintsManager
{
	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
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
			'enum' => '\Change\Documents\Constraints\Enum');
	}

	protected function registerDefaultTranslator()
	{
		if (\Zend\Validator\AbstractValidator::getDefaultTranslatorTextDomain() !== 'c.constraints')
		{
			\Zend\Validator\AbstractValidator::setDefaultTranslatorTextDomain('c.constraints');
			$t = Translator::factory(array());
			$t->setI18nManager($this->getApplicationServices()->getI18nManager());
			\Zend\Validator\AbstractValidator::setDefaultTranslator($t);
		}
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		$this->registerDefaultTranslator();
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
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
					return $constraint;
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
					return $constraint;
				}
			}
		}
		throw new \InvalidArgumentException('Constraint '. $name . ' not found', 52002);
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