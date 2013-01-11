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
			'url' => '\Change\Documents\Constraints\Url');
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
	 * @param array $params <max => maxLength || parameter => maxLength>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function maxSize($params = array())
	{		
		if (isset($params['parameter'])) 
		{
			$params['max'] = intval($params['parameter']);
		}
		return new \Zend\Validator\StringLength($params);
	}	
	
	/**
	 * @param array $params <min => minLength || parameter => minLength>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function minSize($params = array())
	{		
		if (isset($params['parameter'])) 
		{
			$params['min'] = intval($params['parameter']);
		}
		return new \Zend\Validator\StringLength($params);
	}

	/**
	 * @param array $params <modelName => modelName, propertyName => propertyName, [documentId => documentId]>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public function unique($params = array())
	{
		return new Unique($params);	
	}
	
	/**
	 * @param array $params <pattern => pattern, [message => message] || parameter => pattern#message>
	 * @return \Zend\Validator\ValidatorInterface
	 */	
	public function matches($params = array())
	{
		if (isset($params['parameter']) && is_string($params['parameter']))
		{
			$pattern = $params['parameter'];
			if (($splitIndex = strpos($pattern, '#')) !== false)
			{
				$params['message'] = substr($pattern, $splitIndex + 1);
				$pattern = substr($pattern, 0, $splitIndex);
			}
			$params['pattern'] = '#' . $pattern . '#';
		}
		$c = new \Zend\Validator\Regex($params);
		if (isset($params['message']) && is_string($params['message']))
		{
			$c->setMessage($params['message']);
		}
		return $c;
	}
	
	/**
	 * @param array $params <min => min || parameter => min>
	 * @return \Zend\Validator\ValidatorInterface
	 */	
	public function min($params = array())
	{
		if (isset($params['parameter']))
		{
			$params['min'] = $params['parameter'];
		}
		return new Min($params);
	}

	/**
	 * @param array $params <max => max || parameter => max>
	 * @return \Zend\Validator\ValidatorInterface
	 */	
	public function max($params = array())
	{
		if (isset($params['parameter']))
		{
			$params['max'] = $params['parameter'];
		}
		return new Max($params);
	}

	/**
	 * @param array $params <min => min, max => max, [inclusive => inclusive] || parameter => min..max>
	 * @return \Zend\Validator\ValidatorInterface
	 */	
	public function range($params = array())
	{
		if (isset($params['parameter']))
		{
			list($min, $max) = explode('..', $params['parameter']);
			$params['min'] = $min;
			$params['max'] = $max;
			$params['inclusive'] = true;
		}
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