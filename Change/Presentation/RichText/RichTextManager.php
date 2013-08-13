<?php
namespace Change\Presentation\RichText;

use Change\Documents\DocumentServices;
use Change\Presentation\PresentationServices;
use Change\Presentation\RichText\Event;

/**
 * @name \Change\Presentation\RichText\RichTextManager
 */
class RichTextManager implements \Zend\EventManager\EventsCapableInterface
{

	use \Change\Events\EventsCapableTrait;

	const DEFAULT_IDENTIFIER = 'Presentation.RichText';
	const EVENT_GET_PARSER = 'GetParser';

	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var DocumentServices|null
	 */
	protected $documentServices;

	/**
	 * @param PresentationServices $presentationServices
	 */
	public function setPresentationServices(PresentationServices $presentationServices)
	{
		$this->presentationServices = $presentationServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($presentationServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return PresentationServices
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @param DocumentServices $documentServices
	 * @return $this
	 */
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($documentServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
		return $this;
	}

	/**
	 * @return DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::DEFAULT_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->presentationServices)
		{
			$config = $this->presentationServices->getApplicationServices()->getApplication()->getConfiguration();
			return $config->getEntry('Change/Events/RichTextManager', array());
		}
		return array();
	}

	/**
	 * @param \Change\Documents\RichtextProperty $richText
	 * @param string $profile 'Admin' or 'Website'
	 * @param array|null $context
	 * @return string
	 */
	public function render(\Change\Documents\RichtextProperty $richText, $profile, $context = null)
	{
		$eventManager = $this->getEventManager();
		$event = new Event(static::EVENT_GET_PARSER, $this);
		$event->setProfile($profile);
		$event->setEditor($richText->getEditor());
		$event->setDocumentServices($this->getDocumentServices());
		$event->setContext($context);
		$eventManager->trigger($event);

		if ($event->getParser())
		{
			$output = $event->getParser()->parse($richText->getRawText(), $context);
			// TODO Should we save this result in the RichtextProperty?
			$richText->setHtml($output);
			return $output;
		}

		return '';
	}

}