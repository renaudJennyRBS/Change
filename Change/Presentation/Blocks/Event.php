<?php
namespace Change\Presentation\Blocks;

use Zend\EventManager\Event as ZendEvent;

/**
 * @name \Change\Presentation\Blocks\Event
 */
class Event extends ZendEvent
{
	/**
	 * @var \Change\Presentation\PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var \Change\Documents\DocumentServices|null
	 */
	protected $documentServices;

	/**
	 * @var \Change\Presentation\Layout\Block
	 */
	protected $blockLayout;

	/**
	 * @var Parameters
	 */
	protected $blockParameters;

	/**
	 * @var \Change\Http\Web\Result\BlockResult;
	 */
	protected $blockResult;

	/**
	 * @param \Change\Presentation\PresentationServices|null $presentationServices
	 */
	public function setPresentationServices(\Change\Presentation\PresentationServices $presentationServices)
	{
		$this->presentationServices = $presentationServices;
	}

	/**
	 * @api
	 * @return \Change\Presentation\PresentationServices|null
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices|null $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		if ($this->documentServices === null)
		{
			throw new \RuntimeException('documentServices is not set', 999999);
		}
		return $this->documentServices;
	}

	/**
	 * @param \Change\Presentation\Layout\Block $blockLayout
	 * @return $this
	 */
	public function setBlockLayout($blockLayout)
	{
		$this->blockLayout = $blockLayout;
		return $this;
	}

	/**
	 * @api
	 * @return \Change\Presentation\Layout\Block
	 */
	public function getBlockLayout()
	{
		return $this->blockLayout;
	}

	/**
	 * @api
	 * @param Parameters $blockParameters
	 * @return $this
	 */
	public function setBlockParameters($blockParameters)
	{
		$this->blockParameters = $blockParameters;
		return $this;
	}

	/**
	 * @api
	 * @return Parameters|null
	 */
	public function getBlockParameters()
	{
		return $this->blockParameters;
	}

	/**
	 * @api
	 * @param \Change\Http\Web\Result\BlockResult $blockResult
	 * @return $this
	 */
	public function setBlockResult($blockResult)
	{
		$this->blockResult = $blockResult;
		return $this;
	}

	/**
	 * @api
	 * @return \Change\Http\Web\Result\BlockResult|null
	 */
	public function getBlockResult()
	{
		return $this->blockResult;
	}


	/**
	 * @api
	 * @return \Change\Http\Request|null
	 */
	public function getHttpRequest()
	{
		return $this->getParam('httpRequest');
	}
}