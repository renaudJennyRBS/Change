<?php
namespace Rbs\Admin;
use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Zend\EventManager\EventManager;

/**
* @name \Rbs\Admin\Manager
*/
class Manager
{
	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var EventManager
	 */
	protected $eventManager;

	/**
	 * @var string
	 */
	protected $cachePath;

	/**
	 * @param ApplicationServices $applicationServices
	 * @param DocumentServices $documentServices
	 */
	function __construct($applicationServices, $documentServices)
	{
		$this->applicationServices = $applicationServices;
		$this->documentServices = $documentServices;
	}

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices($documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->applicationServices->getApplication();
	}

	/**
	 * Retrieve the event manager
	 * @api
	 * @return \Zend\EventManager\EventManagerInterface
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$identifiers = array('Change.Admin');
			$eventManager = new EventManager($identifiers);
			$eventManager->setSharedManager($this->getApplication()->getSharedEventManager());
			$eventManager->setEventClass('\\Change\\Admin\\Event');
			$this->eventManager = $eventManager;

			$this->attachEvents();
		}
		return $this->eventManager;
	}

	/**
	 * Attach specific admin event
	 */
	protected function attachEvents()
	{
		$classNames = $this->getApplication()->getConfiguration()->getEntry('Rbs/Admin/Listeners');
		$this->registerListenerAggregateClassNames($classNames);
	}

	/**
	 * @param string[] $classNames
	 */
	public function registerListenerAggregateClassNames($classNames)
	{
		if (is_array($classNames) && count($classNames))
		{
			$eventManager = $this->getEventManager();
			foreach ($classNames as $className)
			{
				if (class_exists($className))
				{
					$listener = new $className();
					if ($listener instanceof \Zend\EventManager\ListenerAggregateInterface)
					{
						$listener->attach($eventManager);
					}
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function getResources()
	{
		$params = new \ArrayObject(array('header' => array(), 'body' => array()));
		$event = new Event(Event::EVENT_RESOURCES, $this, $params);
		$this->getEventManager()->trigger($event);
		return $params->getArrayCopy();
	}

	/**
	 * @return string
	 */
	protected function getCachePath()
	{
		if ($this->cachePath === null)
		{
			$this->cachePath = $this->getApplicationServices()->getApplication()->getWorkspace()->cachePath('Admin', 'Templates', 'Compiled');
			\Change\Stdlib\File::mkdir($this->cachePath);
		}
		return $this->cachePath;
	}

	/**
	 * @param string $pathName
	 * @param array $attributes
	 * @return string
	 */
	public function renderTemplateFile($pathName, array $attributes)
	{
		$loader = new \Twig_Loader_Filesystem(dirname($pathName));

		// Include Twig macros for forms.
		// Use it with: {% import "@Admin/forms.twig" as forms %}
		$formsMacroPath = $this->getApplicationServices()->getApplication()->getWorkspace()->pluginsModulesPath('Change', 'Admin', 'Assets');
		$loader->addPath($formsMacroPath, 'Admin');
		// TODO: register macros from other plugins.
		$formsMacroPath = $this->getApplicationServices()->getApplication()->getWorkspace()->pluginsModulesPath('Change', 'Catalog', 'Admin', 'Assets');
		$loader->addPath($formsMacroPath, 'Catalog');

		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		$twig->addExtension(new \Change\Presentation\Templates\Twig\Extension($this->getApplicationServices()));
		return $twig->render(basename($pathName), $attributes);
	}
}