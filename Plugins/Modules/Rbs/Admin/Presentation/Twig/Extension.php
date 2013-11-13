<?php
namespace Rbs\Admin\Presentation\Twig;

/**
 * @name \Rbs\Admin\Presentation\Twig\Extension
 */
class Extension implements \Twig_ExtensionInterface
{
	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Documents\ModelManager
	 */
	protected $modelManager;

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @param \Change\Documents\ModelManager $modelManager
	 */
	function __construct(\Change\I18n\I18nManager $i18nManager = null, \Change\Documents\ModelManager $modelManager = null)
	{
		$this->i18nManager = $i18nManager;
		$this->modelManager = $modelManager;
	}

	/**
	 * Returns the name of the extension.
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'Rbs_Admin';
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
			new \Twig_SimpleFunction('createLinks', array($this, 'createLinks'), array('is_safe' => array('html'))),
			new \Twig_SimpleFunction('propertyKey', array($this, 'propertyKey')),
			new \Twig_SimpleFunction('modelKey', array($this, 'modelKey'))

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
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return $this
	 */
	public function setModelManager($modelManager)
	{
		$this->modelManager = $modelManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\ModelManager
	 */
	protected function getModelManager()
	{
		return $this->modelManager;
	}

	/**
	 * @param $modelName
	 * @return string
	 */
	public function createLinks($modelName)
	{
		$modelManager = $this->getModelManager();
		$model = $modelManager->getModelByName($modelName);
		if (!$model)
		{
			return null;
		}

		$models = array($model);
		foreach ($model->getDescendantsNames() as $descendantsName)
		{
			$models[] = $modelManager->getModelByName($descendantsName);
		}
		$links = array();
		$i18nManager = $this->getI18nManager();

		/* @var $lm \Change\Documents\AbstractModel */
		foreach ($models as $lm)
		{
			if (!$lm->isAbstract())
			{
				$titleKey = strtolower(implode('.',
					array('m', $lm->getVendorName(), $lm->getShortModuleName(), 'admin.js', $lm->getShortName() . '-create')));
				$link =
					'<a href ng-href="(= \'' . $lm->getName() . '\' | documentURL:\'new\' =)">' . $i18nManager->trans($titleKey,
						array('html', 'ucf')) . '</a>';
				$links[] = $link;
			}
		}
		return implode(' ', $links);
	}

	/**
	 * Get the property translation key for label
	 * @param null|string $modelName
	 * @param null|string $propertyName
	 * @return null|string
	 */
	public function propertyKey($modelName = null, $propertyName = null, $suffix = null)
	{
		$mm = $this->getModelManager();
		if ($modelName)
		{
			$model = $mm->getModelByName($modelName);
			if ($model && $model->hasProperty($propertyName))
			{
				$key = $model->getPropertyLabelKey($propertyName);
				return $suffix ? $key . '-' . $suffix : $key;
			}
		}
		return $propertyName;
	}

	/**
	 * @param null|string $modelName
	 * @param null|string $suffix
	 * @return null|string
	 */
	public function modelKey($modelName = null, $suffix = null)
	{
		$mm = $this->getModelManager();
		if ($modelName)
		{
			$model = $mm->getModelByName($modelName);
			if ($model)
			{
				$key = $model->getLabelKey();
				return $suffix ? $key . '-' . $suffix : $key;
			}
		}
		return $modelName;
	}
}