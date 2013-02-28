<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\DocumentLink
 */
class DocumentLink extends Link
{
	const MODE_LINK = 'link';
	const MODE_PROPERTY = 'property';

	/**
	 * @var string
	 */
	protected $mode;

	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @var string
	 */
	protected $LCID;


	/**
	 * @var array
	 */
	protected $properties;

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $action
	 */
	public function __construct(\Change\Http\UrlManager $urlManager, \Change\Documents\AbstractDocument $document, $action = self::MODE_LINK)
	{
		$this->document = $document;
		$this->mode = $action;
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$this->LCID =  $document->isNew() ? $document->getRefLCID() : $document->getLCID();
		}
		parent::__construct($urlManager, $this->buildPathInfo());
	}

	protected function buildPathInfo()
	{
		$path = array_merge(array('resources'), explode('_', $this->getModelName()));
		$path[] = $this->getId();
		if ($this->LCID)
		{
			$path[] = $this->LCID;
		}
		return implode('/', $path);
	}

	/**
	 * @param string $mode
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}

	/**
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * @param string $LCID
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
		$this->setPathInfo($this->buildPathInfo());
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->document->getId();
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return $this->document->getDocumentModelName();
	}

	/**
	 * @param array $properties
	 */
	public function setProperties($properties)
	{
		$this->properties = $properties;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param string|\Change\Documents\Property $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value = null)
	{
		if (is_string($name))
		{
			if ($value === null)
			{
				if (is_array($this->properties))
				{
					unset($this->properties[$name]);
				}
			}
			else
			{
				$this->properties[$name] = $value;
			}
		}
		elseif ($name instanceof \Change\Documents\Property)
		{
			if ($value === null)
			{
				$c = new \Change\Http\Rest\PropertyConverter($this->document, $name, $this->urlManager);
				$value = $c->getRestValue();
			}
			$this->setProperty($name->getName(), $value);
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$result = parent::toArray();
		if ($this->LCID)
		{
			$result['hreflang'] = $this->LCID;
		}

		if ($this->mode === static::MODE_PROPERTY)
		{
			$result = array('id' => $this->getId(), 'model' => $this->getModelName(), 'link' => $result);
			if (is_array($this->properties))
			{
				foreach ($this->properties as $name => $value)
				{
					$result[$name] = $this->convertToArray($value);
				}
			}
		}
		return $result;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function convertToArray($value)
	{
		if (is_array($value))
		{
			$result = array();
			foreach ($value as $k => $v)
			{
				$result[$k] = $this->convertToArray($v);
			}
			return $result;
		}
		elseif (is_object($value))
		{
			if (is_callable(array($value, 'toArray')))
			{
				return $value->toArray();
			}
			else
			{
				return get_object_vars($value);
			}
		}
		return $value;
	}
}