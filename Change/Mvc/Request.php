<?php
namespace Change\Mvc;

/**
 * @name \Change\Mvc\Request
 */
class Request
{
	/**
	 * @var integer
	 */
	const GET = 2;
	
	/**
	 * @var integer
	 */
	const POST = 4;
	
	/**
	 * @var integer
	 */
	const PUT = 8;
	
	/**
	 * @var integer
	 */
	const DELETE = 16;
	
	/**
	 * @var string
	 */	
	const DOCUMENT_ID = 'cmpref';

	/**
	 * @var array
	 */
	protected $errors = array();
	
	/**
	 * @var string
	 */
	protected $method = null;
	
	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @return void
	 */
	public function clearParameters()
	{
		$this->parameters = null;
		$this->parameters = array();
	}

	/**
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	public function getParameter($name, $default = null)
	{
		return (isset($this->parameters[$name])) ? $this->parameters[$name] : $default;
	}

	/**
	 * @return string[]
	 */
	public function getParameterNames()
	{
		return array_keys($this->parameters);
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasParameter($name)
	{
		return isset($this->parameters[$name]);
	}

	/**
	 * @param string $name
	 * @return mixed old value
	 */
	public function removeParameter($name)
	{
		if (isset($this->parameters[$name]))
		{
			$retval = $this->parameters[$name];
			unset($this->parameters[$name]);
			return $retval;
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	/**
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
	}
	
	/**
	 * @param string $name
	 * @return string|NULL
	 */
	public function getError($name)
	{
		return (isset($this->errors[$name])) ? $retval = $this->errors[$name] : null;
	}
	
	/**
	 * @return string[]
	 */
	public function getErrorNames()
	{
		return array_keys($this->errors);
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasError($name)
	{
		return isset($this->errors[$name]);
	}
	
	/**
	 * @return boolean
	 */
	public function hasErrors()
	{
		return (count($this->errors) > 0);
	}
	
	/**
	 * @param string $name
	 * @return string|NULL old value
	 */
	public function removeError($name)
	{
		if (isset($this->errors[$name]))
		{
			$retval = $this->errors[$name];
			unset($this->errors[$name]);
			return $retval;
		}
		return null;
	}
		
	/**
	 * @param string $name
	 * @param string $message
	 */
	public function setError($name, $message)
	{
		$this->errors[$name] = $message;
	}
	
	/**
	 * @param array $errors
	 */
	public function setErrors($errors)
	{
		$this->errors = array_merge($this->errors, $errors);
	}
	
	/**
	 * @return integer|NULL change_Request::[GET | POST | PUT | DELETE]
	 */
	public function getMethod()
	{
		return $this->method;
	}
	
	/**
	 * @param integer $method change_Request::[GET | POST | PUT | DELETE]
	 * @throws Exception
	 */
	public function setMethod($method)
	{
		if ($method == self::GET || $method == self::POST || $method == self::PUT || $method == self::DELETE)
		{
			$this->method = $method;
			return;
		}
		throw new \Exception('Invalid request method: ' . $method);
	}

	/**
	 * 
	 * @param change_Context $context
	 * @param array $parameters
	 */
	public function initialize($context, $parameters = null)
	{
		if (isset($_SERVER['REQUEST_METHOD']))
		{
			$this->setParameters($_GET);
			switch ($_SERVER['REQUEST_METHOD'])
			{
				case 'POST':
					$this->setMethod(self::POST);
					$this->setParameters($_POST);
					break;
				case 'PUT':
					$this->setMethod(self::PUT);
					break;
				case 'DELETE':
					$this->setMethod(self::DELETE);
					break;
				default:
					$this->setMethod(self::GET);
					break;
			}
		}
		else
		{
			$this->setMethod(self::GET);
			if (isset($_SERVER['argv']))
			{
				$this->setParameters($_SERVER['argv']);
			}
		}
		
		if (is_array($parameters))
		{
			$this->setParameters($parameters);
		}
	}
	
	/**
	 */
	public function shutdown()
	{
		$this->clearParameters();
	}

	/**
	 * Retrieve a module parameter.
	 *
	 * @param string $moduleName The module name.
	 * @param string $paramName The parameter name.
	 */
	public function getModuleParameter($moduleName, $paramName)
	{
		$moduleParams = $this->getModuleParameters($moduleName);
		if (is_array($moduleParams) && isset($moduleParams[$paramName]))
		{
			return $moduleParams[$paramName];
		}
		return null;
	}

   /**
	 * Set a module parameter.
	 * @param string $moduleName The module name.
	 * @param string $paramName The parameter name.
	 * @param mixed $paramValue
	 */
	public function setModuleParameter($moduleName, $paramName, $paramValue)
	{
		if (!isset($this->parameters[$moduleName."Param"]))
		{
			$this->parameters[$moduleName."Param"] = array($paramName => $paramValue);
		}
		else
		{
			$this->parameters[$moduleName."Param"][$paramName] = $paramValue;
		}
	}
	
	/**
	 * Indicates whether the request has the given module parameter or not.
	 *
	 * @param string $moduleName The module name.
	 * @param string $paramName The parameter name.
	 * @return boolean true if the module parameter exists, false otherwise.
	 */
	public function hasModuleParameter($moduleName, $paramName)
	{
		$moduleParams = $this->getModuleParameters($moduleName);
		return is_array($moduleParams) &&  isset($moduleParams[$paramName]);
	}
	
	/**
	 * Retrieve all the parameters defined for the given module.
	 * @param string $moduleName The module name.
	 * @return array|NULL
	 */
	public function getModuleParameters($moduleName)
	{
		return $this->getParameter($moduleName."Param");
	}
	
	/**
	 * Set a cookie.
	 * @param string $key Cookie key
	 * @param string $value Cookie value
	 * @param string $lifeTime Cookie life time in seconds
	 */
	public function setCookie($key, $value, $lifeTime = null)
	{
		if ($lifeTime === null)
		{
			$lifeTime = 60 * 60 * 24 * 15;
		}
		setcookie($key, $value, time() + $lifeTime, '/');
	}
	
	/**
	 * Test a cookie availability.
	 * @param string $key Cookie key
	 * @return boolean
	 */
	public function hasCookie($key)
	{
		if (isset($_COOKIE[$key]) && $_COOKIE[$key])
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Get a cookie value.
	 * @param string $key Cookie key
	 * @param string $defaultValue Cookie default value
	 * @return string
	 */
	public function getCookie($key, $defaultValue = '')
	{
		if ($this->hasCookie($key))
		{
			return $_COOKIE[$key];
		}
		return $defaultValue;
	}
	
	/**
	 * Remove a cookie.
	 * @param string $key Cookie key
	 */
	public function removeCookie($key)
	{
		setcookie($key, '', time() - 3600, '/');
	}
}