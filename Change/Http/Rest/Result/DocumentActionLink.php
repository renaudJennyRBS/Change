<?php
namespace Change\Http\Rest\Result;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Localizable;
use Change\Http\UrlManager;

/**
 * @name \Change\Http\Rest\Result\DocumentActionLink
 */
class DocumentActionLink extends Link
{
	/**
	 * @var string
	 */
	protected $action;

	/**
	 * @var string
	 */
	protected $modelName;

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @param string $modelName
	 * @return $this
	 */
	public function setModelName($modelName)
	{
		$this->modelName = $modelName;
		$this->setPathInfo($this->buildPathInfo());
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return $this->modelName;
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		$this->setPathInfo($this->buildPathInfo());
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $LCID
	 * @return $this
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
		$this->setPathInfo($this->buildPathInfo());
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @param string $action
	 * @return $this
	 */
	public function setAction($action)
	{
		$this->action = $action;
		$this->setPathInfo($this->buildPathInfo());
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param UrlManager $urlManager
	 * @param AbstractDocument $document
	 * @param string $action
	 * @return \Change\Http\Rest\Result\DocumentActionLink
	 */
	public function __construct(UrlManager $urlManager, AbstractDocument $document, $action = null)
	{
		$this->modelName = $document->getDocumentModelName();
		$this->id = $document->getId();
		$this->action = $action;
		if ($document instanceof Localizable)
		{
			/* @var $document Localizable|AbstractDocument */
			$this->LCID =  $document->isNew() ? $document->getRefLCID() : $document->getCurrentLCID();
		}
		parent::__construct($urlManager, $this->buildPathInfo(), $this->action);
	}

	protected function buildPathInfo()
	{
		$path = array('resources');
		if ($this->getModelName())
		{
			$path = array_merge($path, explode('_', $this->getModelName()));
		}
		if ($this->getId())
		{
			$path[] = $this->getId();
		}
		if ($this->getLCID())
		{
			$path[] = $this->getLCID();
		}
		if ($this->getAction())
		{
			$path[] = $this->getAction();
		}
		return implode('/', $path);
	}
}