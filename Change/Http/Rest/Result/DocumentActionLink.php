<?php
namespace Change\Http\Rest\Result;

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
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $action
	 * @return \Change\Http\Rest\Result\DocumentActionLink
	 */
	public function __construct(\Change\Http\UrlManager $urlManager, \Change\Documents\AbstractDocument $document, $action)
	{
		$this->document = $document;
		$this->action = $action;
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$this->LCID =  $document->isNew() ? $document->getRefLCID() : $document->getLCID();
		}
		parent::__construct($urlManager, $this->buildPathInfo(), $this->action);
	}

	/**
	 * @return string
	 */
	protected function buildPathInfo()
	{
		$path = array('resourcesactions', $this->getAction(), $this->getId());
		if ($this->LCID)
		{
			$path[] = $this->LCID;
		}
		return implode('/', $path);
	}

	/**
	 * @param string $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
		$this->setPathInfo($this->buildPathInfo());
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
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
}