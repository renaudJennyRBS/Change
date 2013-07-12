<?php
namespace Rbs\Admin;
use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Zend\EventManager\EventManager;

/**
* @name \Rbs\Admin\Manager
*/
class Manager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

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
		if ($applicationServices)
		{
			$this->setApplicationServices($applicationServices);
		}
		if ($documentServices)
		{
			$this->setDocumentServices($documentServices);
		}
	}

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function setApplicationServices(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
		}
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
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($documentServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
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
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return 'Rbs_Admin';
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->documentServices)
		{
			$config = $this->documentServices->getApplicationServices()->getApplication()->getConfiguration();
			return $config->getEntry('Change/Events/Rbs/Admin', array());
		}
		return array();
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
		$formsMacroPath = $this->getApplicationServices()->getApplication()->getWorkspace()->pluginsModulesPath('Rbs', 'Admin', 'Assets');
		$loader->addPath($formsMacroPath, 'Admin');
		// TODO: register macros from other plugins.
		$formsMacroPath = $this->getApplicationServices()->getApplication()->getWorkspace()->pluginsModulesPath('Rbs', 'Price', 'Admin', 'Assets');
		$loader->addPath($formsMacroPath, 'Price');

		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		$twig->addExtension(new \Change\Presentation\Templates\Twig\Extension($this->getApplicationServices()));
		return $twig->render(basename($pathName), $attributes);
	}
}