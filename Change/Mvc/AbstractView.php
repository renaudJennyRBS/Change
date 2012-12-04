<?php
namespace Change\Mvc;

/**
 * @name \Change\Mvc\AbstractView
 */
abstract class AbstractView
{
	const ALERT = 'Alert';
	const ERROR = 'Error';
	const INPUT = 'Input';
	const NONE = null;
	const SUCCESS = 'Success';

	const STATUS_OK	= 'OK';
	const STATUS_ERROR = 'ERROR';
	
	/**
	 * @var \Change\Mvc\Context
	 */
	protected $context;
	
	/**
	 * @var string
	 */
	protected $moduleName;
	
	/**
	 * @var array
	 */
	protected $attributes = array();
	
	/**
	 * @var string
	 */
	protected $mimeContentType = null;

	/**
	 * @return \TemplateObject
	 */
	protected $engine = null;
	
	/**
	 * @return \Change\Mvc\Context
	 */
	public function getContext()
	{
		return $this->context;
	}
	
	/**
	 * @param \Change\Mvc\Context $context
	 * @return boolean
	 */
	public function initialize($context)
	{
		$this->context = $context;
		$this->moduleName = $context->getModuleName();
		return true;
	}
	
	/**
	 * Please do not override this method, but _execute() instead!
	 * @return string View name.
	 */
	public function execute()
	{
		$context = $this->getContext();
		$this->sendHttpHeaders();
		return $this->_execute($context, $context->getRequest());
	}

	/**
	 * PLEASE USE THIS METHOD for the action body instead of execute() (without
	 * the underscore): it is called by execute() and directly receives f_Context
	 * and Request objects.
	 * @param \Change\Mvc\Context $context
	 * @param \Change\Mvc\Request $request
	 */
	abstract protected function _execute($context, $request);

	/**
	 * @param string $mimeContentType
	 * @throws IllegalArgumentException
	 */
	public function setMimeContentType($mimeContentType)
	{
		if (empty($mimeContentType))
		{
			throw new \InvalidArgumentException('Invalid mimeContentType');
		}
		//TODO Old class Usage
		\RequestContext::getInstance()->setMimeContentType($mimeContentType);
		$this->mimeContentType = $mimeContentType;
	}

	/**
	 * @param string $templateName
	 * @param string $mimeType
	 */
	public function setTemplateName($templateName, $mimeType = 'html')
	{
		$moduleName = $this->moduleName;
		//TODO Old class Usage
		$templateLoader = \change_TemplateLoader::getNewInstance('template')->setExtension($mimeType);
		$this->engine = $templateLoader->load('modules', $moduleName, 'templates', $templateName);
		if ($this->engine === null)
		{
			$this->engine = $templateLoader->load('modules', 'generic', 'templates', $templateName);
			if ($this->engine === null) {throw new \Exception('Template not found');}
		}
	}

	/**
	 * @return \TemplateObject
	 */
	public function getEngine()
	{
		return $this->engine;
	}

	/**
	 * Clear all attributes.
	 */
	public function clearAttributes()
	{
		$this->attributes = array();
	}

	/**
	 * @param string $name
	 */
	public function hasAttribute($name)
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getAttribute($name)
	{
		if ($this->hasAttribute($name))
		{
			return $this->attributes[$name];
		}
		return null;
	}

	/**
	 * @param string $name
	 */
	public function removeAttribute($name)
	{
		if ($this->hasAttribute($name))
		{
			unset($this->attributes[$name]);
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}

	/**
	 * @param array $attributes
	 */
	public function setAttributes($attributes)
	{
		foreach ($attributes as $name => $value)
		{
			$this->setAttribute($name, $value);
		}
	}

	/**
	 * @return string[]
	 */
	public function getAttributeNames()
	{
		return array_keys($this->attributes);
	}

	/**
	 * @return NULL
	 */
	public function render()
	{
		$request = $this->getContext()->getRequest();
		$this->setAttribute('module', $this->moduleName);
		$this->setAttribute('action', $this->context->getActionName());
		$this->getEngine()->importAttributes($this->attributes);
		echo $this->getEngine()->execute();
		return null;
	}

	/**
	 * Send HTTP headers.
	 */
	protected function sendHttpHeaders()
	{
		$this->getContext()->getController()->addNoCacheHeader();
	}

	/**
	 * Sets the action status (STATUS_OK ou STATUS_ERROR).
	 *
	 * @param string $status The status to set to the response.
	 */
	protected final function setStatus($status)
	{
		$this->setAttribute('status', $status);
	}

	/**
	 * Returns the current lang.
	 *
	 * @return string
	 */
	public function getLang()
	{
		return \Change\I18n\I18nManager::getInstance()->getLang();
	}
}