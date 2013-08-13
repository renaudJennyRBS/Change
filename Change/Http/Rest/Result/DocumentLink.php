<?php
namespace Change\Http\Rest\Result;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Activable;
use Change\Documents\Interfaces\Correction;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\RestfulDocumentInterface;
use Change\Http\UrlManager;

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
	public function __construct(\Change\Http\UrlManager $urlManager, \Change\Documents\AbstractDocument $document, $action = self::MODE_LINK, $extraColumns = array())
	{
		$this->document = $document;
		$this->mode = $action;
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$this->LCID =  $document->isNew() ? $document->getRefLCID() : $document->getLCID();
		}
		parent::__construct($urlManager, $this->buildPathInfo());
		if ($action == self::MODE_PROPERTY)
		{
			$document->populateRestDocumentLink($this, $extraColumns);
		}
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
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getProperty($name, $defaultValue = null)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : $defaultValue;
	}

	/**
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocument()
	{
		return $this->document;
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

	/**
	 * @param AbstractDocument $document
	 * @param UrlManager $urlManager
	 * @param array $extraColumn
	 * @return $this
	 */
	protected function addResourceItemInfos($extraColumn = array())
	{
		$dm = $this->document->getDocumentManager();
		$eventManager = $this->document->getEventManager();
		if ($this->getLCID())
		{
			$dm->pushLCID($this->getLCID());
		}

		$model =  $this->document->getDocumentModel();

		$this->setProperty($model->getProperty('creationDate'));
		$this->setProperty($model->getProperty('modificationDate'));

		if ($this->document instanceof Editable)
		{
			$this->setProperty($model->getProperty('label'));
			$this->setProperty($model->getProperty('documentVersion'));
		}

		if ($this->document instanceof Publishable)
		{
			$this->setProperty($model->getProperty('publicationStatus'));
		}
		elseif ($this->document instanceof Activable)
		{
			$this->setProperty($model->getProperty('active'));
		}

		if ($this->document instanceof Localizable)
		{
			$this->setProperty($model->getProperty('refLCID'));
			$this->setProperty($model->getProperty('LCID'));
		}

		if ($this->document instanceof Correction)
		{
			/* @var $document AbstractDocument|Correction */
			if ( $this->document->hasCorrection())
			{
				$l = new DocumentActionLink($this->urlManager, $this->document, 'correction');
				$this->setProperty('actions', array($l));
			}
		}

		if (is_array($extraColumn) && count($extraColumn))
		{
			foreach ($extraColumn as $propertyName)
			{
				$property = $model->getProperty($propertyName);
				if ($property)
				{
					$this->setProperty($property);
				}
			}
		}

		if ($this->document instanceof RestfulDocumentInterface)
		{
			$this->document->populateRestDocumentLink($this, $extraColumn);
		}

		$documentEvent = new \Change\Documents\Events\Event('updateRestResult', $this->document,
			array('restResult' => $this, 'extraColumn' => $extraColumn, 'urlManager' => $this->urlManager));
		$eventManager->trigger($documentEvent);

		if ($this->getLCID())
		{
			$dm->popLCID();
		}
		return $this;
	}
}