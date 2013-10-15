<?php
namespace Rbs\Seo\Services;

use Change\Events\EventsCapableTrait;

/**
 * @name \Rbs\Seo\Services\SeoManager
 */
class SeoManager implements \Zend\EventManager\EventsCapableInterface
{
	use EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'SeoManager';

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		$this->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->applicationServices === null)
		{
			$this->setApplicationServices($documentServices->getApplicationServices());
		}
	}

	/**
	 * @return \Change\Documents\DocumentServices
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
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->applicationServices)
		{
			$config = $this->applicationServices->getApplication()->getConfiguration();
			return $config->getEntry('Change/Events/SeoManager', array());
		}
		return array();
	}

	/**
	 * @param \Change\Presentation\Interfaces\Page $page
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array
	 */
	public function getMetas($page, $document)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'page' => $page,
			'document' => $document
		));
		$eventManager->trigger('getMetas', $this, $args);
		return isset($args['metas']) ? $args['metas'] : array();
	}

	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @param array $variables
	 * @return array
	 */
	public function getMetaSubstitutions($document, $variables)
	{
		$eventManager = $document->getEventManager();
		$event = new \Change\Documents\Events\Event('getMetaSubstitutions', $document, [ 'variables' => $variables ]);
		$eventManager->trigger($event);
		return ($event->getParam('substitutions')) ? $event->getParam('substitutions') : [];
	}

	/**
	 * @param string[] $functions
	 * @return array
	 */
	public function getMetaVariables($functions)
	{
		$this->getApplicationServices()->getLogging()->fatal(var_export($functions, true));
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'functions' => $functions,
			'documentServices' => $this->getDocumentServices()
		));
		$eventManager->trigger('getMetaVariables', null, $args);
		return isset($args['variables']) ? $args['variables'] : [];
	}

}