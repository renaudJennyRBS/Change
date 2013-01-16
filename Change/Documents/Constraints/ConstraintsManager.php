<?php
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\ConstraintsManager
 */
class ConstraintsManager
{	
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
	/**
	 * @var array
	 */
	protected $defaultConstraint;
	
	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		
		if (\Zend\Validator\AbstractValidator::getDefaultTranslatorTextDomain() !== 'c.constraints')
		{
			\Zend\Validator\AbstractValidator::setDefaultTranslatorTextDomain('c.constraints');
			$t = Translator::factory(array());
			$t->setI18nManager($this->applicationServices->getI18nManager());
			\Zend\Validator\AbstractValidator::setDefaultTranslator($t);
		}
		
		$this->defaultConstraint = array(
			'domain' => '\Change\Documents\Constraints\Domain',
			'emails' => '\Change\Documents\Constraints\Emails',
			'url' => '\Change\Documents\Constraints\Url',
			'unique' => '\Change\Documents\Constraints\Unique',
			'enum' => '\Change\Documents\Constraints\Enum');
	}
	
	/**
	 * @param string $name
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function getByName($name, $params = array())
	{

		if (method_exists($this, $name))
		{
			return call_user_func(array($this, $name), $params);
		}
		elseif (isset($this->defaultConstraint[$name]))
		{
			$className = $this->defaultConstraint[$name];
			return new $className($params);
		}
		throw new \InvalidArgumentException('Constraint '. $name . ' not found');
	}
	
	
	public function registerConstraint($name, $class)
	{
		$this->defaultConstraint[$name] = $class;
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