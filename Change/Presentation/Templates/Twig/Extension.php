<?php
namespace Change\Presentation\Templates\Twig;

use Change\Application\ApplicationServices;

/**
 * Class Extension
 * @package Change\Presentation\Templates\Twig
 * @name \Change\Presentation\Templates\Twig\Extension
 */
class Extension  implements \Twig_ExtensionInterface
{
	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param ApplicationServices $applicationServices
	 */
	function __construct(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * Returns the name of the extension.
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'Change';
	}

	/**
	 * Initializes the runtime environment.
	 * This is where you can load some file that contains filter functions for instance.
	 * @param \Twig_Environment $environment The current Twig_Environment instance
	 */
	public function initRuntime(\Twig_Environment $environment)
	{

	}

	/**
	 * Returns the token parser instances to add to the existing list.
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		return array();
	}

	/**
	 * Returns the node visitor instances to add to the existing list.
	 * @return array An array of Twig_NodeVisitorInterface instances
	 */
	public function getNodeVisitors()
	{
		return array();
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		return array();
	}

	/**
	 * Returns a list of tests to add to the existing list.
	 * @return array An array of tests
	 */
	public function getTests()
	{
		return array();
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('i18n', array($this, 'i18n'), array('is_safe' => array('html'))),
			new \Twig_SimpleFunction('i18nAttr', array($this, 'i18nAttr'), array('is_safe' => array('html_attr'))),
		);
	}

	/**
	 * Returns a list of operators to add to the existing list.
	 * @return array An array of operators
	 */
	public function getOperators()
	{
		return array();
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		return array();
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param string $i18nKey
	 * @param string[] $formatters
	 * @param array $replacementArray
	 * @return string
	 */
	public function i18n($i18nKey, $formatters = array(), $replacementArray = null)
	{
		if (!is_array($replacementArray))
		{
			$replacementArray = array();
		}
		$formatters[] = 'html';
		return $this->getApplicationServices()->getI18nManager()->trans($i18nKey, $formatters, $replacementArray);
	}

	/**
	 * @param string $i18nKey
	 * @param string[] $formatters
	 * @param array $replacementArray
	 * @return string
	 */
	public function i18nAttr($i18nKey, $formatters = array(), $replacementArray = null)
	{
		if (!is_array($replacementArray))
		{
			$replacementArray = array();
		}
		$formatters[] = 'attr';
		return $this->getApplicationServices()->getI18nManager()->trans($i18nKey, $formatters, $replacementArray);
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

		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		$twig->addExtension(new \Change\Presentation\Templates\Twig\Extension($this->presentationServices->getApplicationServices()));
		return $twig->render(basename($pathName), $attributes);
	}
}