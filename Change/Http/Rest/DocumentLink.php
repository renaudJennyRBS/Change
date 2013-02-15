<?php
namespace Change\Http\Rest;

/**
 * @name \Change\Http\Rest\DocumentLink
 */
class DocumentLink
{
	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $modelName;

	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function __construct(\Change\Documents\AbstractDocument $document = null)
	{
		if ($document)
		{
			$this->id = $document->getId();
			$this->modelName = $document->getDocumentModelName();
			if ($document instanceof \Change\Documents\Interfaces\Localizable)
			{
				$this->LCID = $document->getLCID();
			}
		}
	}

	public function getPathInfo()
	{
		$path = array_merge(array('resources'), explode('_', $this->modelName));
		$path[] = $this->id;
		if ($this->LCID)
		{
			$path[] = $this->LCID;
		}
		return implode('/', $path);
	}

	/**
	 * @param string $LCID
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
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
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return $this->modelName;
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @return array
	 */
	public function toSelfLinkArray(\Change\Http\UrlManager $urlManager)
	{
		return array('rel' => 'self', 'href' => $urlManager->getByPathInfo($this->getPathInfo())->toString());
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @return array
	 */
	public function toPropertyLinkArray(\Change\Http\UrlManager $urlManager)
	{
		$property = array('id' => $this->id, 'model' => $this->modelName);
		$property['link'] = $this->toSelfLinkArray($urlManager);
		return $property;
	}
}