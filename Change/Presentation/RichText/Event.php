<?php
namespace Change\Presentation\RichText;

use Change\Documents\DocumentServices;
use Zend\EventManager\Event as ZendEvent;
use Change\Presentation\PresentationServices;

/**
 * @name \Change\Presentation\RichText\Event
 */
class Event extends ZendEvent
{
	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var DocumentServices|null
	 */
	protected $documentServices;

	/**
	 * @var ParserInterface
	 */
	protected $parser;

	/**
	 * @var string
	 */
	protected $profile;

	/**
	 * @var string
	 */
	protected $editor;

	/**
	 * @param string $editor
	 * @return $this
	 */
	public function setEditor($editor)
	{
		$this->editor = $editor;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEditor()
	{
		return $this->editor;
	}

	/**
	 * @var array
	 */
	protected $context;

	/**
	 * @param array $context
	 * @return $this
	 */
	public function setContext($context)
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @param string $profile
	 * @return $this
	 */
	public function setProfile($profile)
	{
		$this->profile = $profile;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getProfile()
	{
		return $this->profile;
	}


	/**
	 * @param PresentationServices|null $presentationServices
	 * @return $this
	 */
	public function setPresentationServices(PresentationServices $presentationServices)
	{
		$this->presentationServices = $presentationServices;
		return $this;
	}

	/**
	 * @api
	 * @return PresentationServices|null
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @param DocumentServices|null $documentServices
	 * @return $this
	 */
	public function setDocumentServices(DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
		return $this;
	}

	/**
	 * @api
	 * @return DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @param ParserInterface $parser
	 * @return $this
	 */
	public function setParser(ParserInterface $parser)
	{
		$this->parser = $parser;
		return $this;
	}

	/**
	 * @return ParserInterface
	 */
	public function getParser()
	{
		return $this->parser;
	}
}