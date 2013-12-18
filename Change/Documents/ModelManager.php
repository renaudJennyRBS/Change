<?php
namespace Change\Documents;

use Change\Stdlib\File;

/**
 * @name \Change\Documents\ModelManager
 */
class ModelManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'ModelManager';


	/**
	 * @var \Change\Documents\AbstractModel[]
	 */
	protected $documentModels = array();

	/**
	 * @var string[]
	 */
	protected $modelsNames = null;

	/**
	 * @var \Change\Plugins\PluginManager
	 */
	protected $pluginManager  = null;

	/**
	 * @var \Change\Workspace
	 */
	protected $workspace  = null;

	/**
	 * @return string
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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Change/Events/ModelManager');
	}

	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getFiltersDefinition', [$this, 'onDefaultGetFiltersDefinition'], 5);
	}

	/**
	 * @param \Change\Plugins\PluginManager $pluginManager
	 * @return $this
	 */
	public function setPluginManager(\Change\Plugins\PluginManager $pluginManager)
	{
		$this->pluginManager = $pluginManager;
		return $this;
	}

	/**
	 * @return \Change\Plugins\PluginManager
	 */
	protected function getPluginManager()
	{
		return $this->pluginManager;
	}

	/**
	 * @param \Change\Workspace $workspace
	 * @return $this
	 */
	public function setWorkspace(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
		return $this;
	}

	/**
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->workspace;
	}



	/**
	 * @param string $modelName
	 * @return \Change\Documents\AbstractModel|null
	 */
	public function getModelByName($modelName)
	{
		if (!array_key_exists($modelName, $this->documentModels))
		{
			$className = $this->getModelClassName($modelName);
			if ($className)
			{
				/* @var $model \Change\Documents\AbstractModel */
				$model = new $className($this);
				if ($model->getReplacedBy())
				{
					$className = $this->getModelClassName($model->getReplacedBy());
					$model = new $className($this);
					$this->documentModels[$model->getReplacedBy()] = $model;
				}
				$this->documentModels[$modelName] = $model;
			}
			else
			{
				$this->documentModels[$modelName] = null;
			}
		}
		return $this->documentModels[$modelName];
	}

	/**
	 * @return string[]
	 */
	public function getModelsNames()
	{
		if ($this->modelsNames === null)
		{
			$this->modelsNames = new \Compilation\Change\Documents\ModelsNames();
		}
		return $this->modelsNames->getArrayCopy();
	}

	/**
	 * @return string[]
	 */
	public function getVendors()
	{
		if ($this->modelsNames === null)
		{
			$this->modelsNames = new \Compilation\Change\Documents\ModelsNames();
		}
		$vendors = array();
		foreach ($this->modelsNames as $name)
		{
			list($v,,) = explode('_', $name);
			$vendors[$v] = true;
		}
		return array_keys($vendors);
	}

	/**
	 * @param string $vendor
	 * @return string[]
	 */
	public function getShortModulesNames($vendor)
	{
		if ($this->modelsNames === null)
		{
			$this->modelsNames = new \Compilation\Change\Documents\ModelsNames();
		}
		$smn = array();
		foreach ($this->modelsNames as $name)
		{
			list($v,$m,) = explode('_', $name);
			if ($v === $vendor) {$smn[$m] = true;}
		}
		return array_keys($smn);
	}

	/**
	 * @param string $vendor
	 * @param string $shortModuleName
	 * @return string[]
	 */
	public function getShortDocumentsNames($vendor, $shortModuleName)
	{
		if ($this->modelsNames === null)
		{
			$this->modelsNames = new \Compilation\Change\Documents\ModelsNames();
		}

		$sdn = array();
		foreach ($this->modelsNames as $name)
		{
			list($v,$m,$d) = explode('_', $name);
			if ($v === $vendor && $m === $shortModuleName) {$sdn[$d] = true;}
		}
		return array_keys($sdn);
	}
	
	/**
	 * @param string $modelName
	 * @return NULL|string
	 */
	protected function getModelClassName($modelName)
	{
		$parts = explode('_', $modelName);
		if (count($parts) === 3)
		{
			list($vendor, $moduleName, $documentName) = $parts;
			$className = 'Compilation\\' . $vendor . '\\' . $moduleName .'\\Documents\\' . $documentName.'Model';
			if (class_exists($className))
			{
				return $className;
			}
		}
		return null;
	}

	/**
	 * @param string $vendorName
	 * @param string $moduleName
	 * @param string$shortModelName
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function initializeModel($vendorName, $moduleName, $shortModelName)
	{
		$pm = $this->getPluginManager();
		$module = $pm->getModule($vendorName, $moduleName);
		if ($module === null)
		{
			throw new \InvalidArgumentException('Module ' . $vendorName  . '_' . $moduleName . ' does not exist', 999999);
		}
		$normalizedShortModelName = $this->normalizeModelName($shortModelName);
		$docPath = implode(DIRECTORY_SEPARATOR, array($module->getAbsolutePath(), 'Documents', 'Assets', $normalizedShortModelName . '.xml'));
		if (file_exists($docPath))
		{
			throw new \RuntimeException('Model file already exists at path ' . $docPath, 999999);
		}
		File::write($docPath, File::read(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'Sample.xml'));
		return $docPath;
	}

	/**
	 * @param string $vendorName
	 * @param string $moduleName
	 * @param string $shortModelName
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function initializeFinalDocumentPhpClass($vendorName, $moduleName, $shortModelName)
	{
		$pm = $this->getPluginManager();
		$module = $pm->getModule($vendorName, $moduleName);
		if ($module === null)
		{
			throw new \InvalidArgumentException('Module ' . $vendorName  . '_' . $moduleName . ' does not exist', 999999);
		}
		$normalizedVendorName = $module->getVendor();
		$normalizedModuleName = $module->getShortName();
		$normalizedShortModelName = $this->normalizeModelName($shortModelName);
		$docPath = implode(DIRECTORY_SEPARATOR, array($module->getAbsolutePath(), 'Documents', $normalizedShortModelName . '.php'));
		if (file_exists($docPath))
		{
			throw new \RuntimeException('Final PHP Document file already exists at path ' . $docPath, 999999);
		}
		$attributes = array('vendor' => $normalizedVendorName, 'module' => $normalizedModuleName, 'name' => $normalizedShortModelName);
		$loader = new \Twig_Loader_Filesystem(__DIR__);
		$twig = new \Twig_Environment($loader);
		File::write($docPath, $twig->render('Assets/Sample.php.twig', $attributes));
		return $docPath;
	}

	/**
	 * @param $name
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function normalizeModelName($name)
	{
		$ucfName = ucfirst($name);
		if (!preg_match('/^[A-Z][a-zA-Z0-9]{1,24}$/', $ucfName))
		{
			throw new \InvalidArgumentException('Model name should match ^[A-Z][a-zA-Z0-9]{1,24}$', 999999);
		}
		return $ucfName;
	}

	/**
	 * @param AbstractModel $model
	 * @return array
	 */
	public function getFiltersDefinition(AbstractModel $model)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['model' => $model, 'filtersDefinition' => []]);
		$em->trigger('getFiltersDefinition', $this, $args);
		return isset($args['filtersDefinition']) && is_array($args['filtersDefinition']) ? array_values($args['filtersDefinition']) : [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetFiltersDefinition($event)
	{
		$model = $event->getParam('model');
		$filtersDefinition = $event->getParam('filtersDefinition');
		if ($model instanceof AbstractModel && is_array($filtersDefinition)){

			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$group = $i18nManager->trans('m.rbs.admin.admin.properties', ['ucf']);
			$systemGroup = $i18nManager->trans('m.rbs.admin.admin.system_properties', ['ucf']);
			$systemPropertiesName = ['id', 'model', 'documentVersion', 'publicationSections', 'publicationStatus', 'active',
				'authorId', 'authorName', 'creationDate', 'modificationDate'];
			foreach ($model->getProperties() as $property)
			{
				if ($property->getStateless() || isset($filtersDefinition[$property->getName()]))
				{
					continue;
				}
				$propertyType = $property->getType();
				$definition = null;
				switch ($propertyType) {
					case Property::TYPE_BOOLEAN:
					case Property::TYPE_STRING:
					case Property::TYPE_DATETIME:
					case Property::TYPE_INTEGER:
					case Property::TYPE_FLOAT:
					case Property::TYPE_DECIMAL:
						$definition = ['name' => $property->getName(), 'parameters' => ['propertyName' => $property->getName()]];
						break;
					case Property::TYPE_DOCUMENT:
					case Property::TYPE_DOCUMENTID:
					case Property::TYPE_DOCUMENTARRAY:
						$definition = ['name' => $property->getName(), 'parameters' => ['propertyName' => $property->getName()]];
						$definition['config']['documentType'] = $property->getDocumentType() ? $property->getDocumentType() : '';
						break;
				}

				if ($definition !== null)
				{
					if (!isset($definition['config']['listLabel']))
					{
						$label = $i18nManager->trans($property->getLabelKey(), ['ucf']);
						if ($label === $property->getLabelKey())
						{
							continue;
						}
						$definition['config']['listLabel'] = $label;
					}
					if ($definition['name'] === 'publicationStatus')
					{
						$possibleValues = [
									['value' => "DRAFT", "label"  => $i18nManager->trans('m.rbs.admin.adminjs.status_draft') ],
									['value' => "VALIDATION", "label"  => $i18nManager->trans('m.rbs.admin.adminjs.status_validation') ],
									['value' => "VALIDCONTENT", "label"  => $i18nManager->trans('m.rbs.admin.adminjs.status_validcontent') ],
									['value' => "PUBLISHABLE", "label"  => $i18nManager->trans('m.rbs.admin.adminjs.status_publishable') ],
									['value' => "UNPUBLISHABLE", "label"  => $i18nManager->trans('m.rbs.admin.adminjs.status_unpublishable') ],
									['value' => "FROZEN", "label"  => $i18nManager->trans('m.rbs.admin.adminjs.status_frozen') ],
									['value' => "FILED", "label"  => $i18nManager->trans('m.rbs.admin.adminjs.status_filed') ]
						];
						$definition['config']['possibleValues'] = $possibleValues;
					}
					if (!isset($definition['config']['group']))
					{
						if (in_array($definition['name'], $systemPropertiesName))
						{
							$definition['config']['group'] = $systemGroup;
						}
						else
						{
							$definition['config']['group'] = $group;
						}
					}

					if (!isset($definition['config']['label']))
					{
						$definition['config']['label'] = $definition['config']['listLabel'];
					}
					if (!isset($definition['config']['propertyType']))
					{
						$definition['config']['propertyType'] = $propertyType;
					}
					if ($property->getLocalized())
					{
						$definition['config']['localized']= true;
					}
					$filtersDefinition[] = $definition;
				}
			}

			$event->setParam('filtersDefinition', $filtersDefinition);
		}
	}

	/**
	 * @param \Change\Documents\Query\Query $documentQuery
	 * @param array $filter
	 * @return \Change\Documents\Query\Query
	 */
	public function applyDocumentFilter(\Change\Documents\Query\Query $documentQuery, $filter)
	{
		if (is_array($filter) && count($filter))
		{
			$predicateBuilder = $documentQuery->getPredicateBuilder();
			$restriction = $this->getGroupPredicate($documentQuery, $filter, $predicateBuilder);
			if ($restriction)
			{
				$documentQuery->andPredicates($restriction);
			}
		}
		return $documentQuery;
	}
	/**
	 * @param \Change\Documents\Query\Query $documentQuery
	 * @param array $filter
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @return \Change\Db\Query\Predicates\InterfacePredicate | null
	 */
	protected function getGroupPredicate($documentQuery, $filter, $predicateBuilder)
	{
		if (isset($filter['operator']) && in_array($filter['operator'], ['AND', 'OR'])) {

			if (isset($filter['filters']) && is_array($filter['filters'])) {
				$restrictions = [];
				foreach ($filter['filters'] as $subFilter)
				{
					$restriction = $this->getRestriction($documentQuery, $subFilter, $predicateBuilder);
					if ($restriction)
					{
						$restrictions[] = $restriction;
					}
				}
				if (count($restrictions))
				{
					if ($filter['operator'] === 'AND')
					{
						return $predicateBuilder->logicAnd($restrictions);
					}
					else
					{
						return $predicateBuilder->logicOr($restrictions);
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param \Change\Documents\Query\Query $documentQuery
	 * @param array $filter
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @return \Change\Db\Query\Predicates\InterfacePredicate | null
	 */
	protected function getRestriction($documentQuery, $filter, $predicateBuilder)
	{
		if (isset($filter['operator']) && isset($filter['filters'])) {
			return $this->getGroupPredicate($documentQuery, $filter, $predicateBuilder);
		}

		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'];
			if (isset($parameters['propertyName']) && isset($parameters['operator']) &&  array_key_exists('value', $parameters))
			{
				$property = $documentQuery->getModel()->getProperty($parameters['propertyName']);
				if ($property) {
					if (isset($parameters['value']))
					{
						switch($parameters['operator'])
						{
							case 'eq':
								return $predicateBuilder->eq($property, $parameters['value']);
							case 'neq':
								return $predicateBuilder->neq($property, $parameters['value']);
							case 'lte':
								return $predicateBuilder->lte($property, $parameters['value']);
							case 'lt':
								return $predicateBuilder->lt($property, $parameters['value']);
							case 'gte':
								return $predicateBuilder->gte($property, $parameters['value']);
							case 'gt':
								return $predicateBuilder->gt($property, $parameters['value']);
							case 'contains':
								return $predicateBuilder->like($property, $parameters['value'],
									\Change\Db\Query\Predicates\Like::ANYWHERE);
							case 'beginsWith':
								return $predicateBuilder->like($property, $parameters['value'],
									\Change\Db\Query\Predicates\Like::BEGIN);
							case 'endsWith':
								return $predicateBuilder->like($property, $parameters['value'],
									\Change\Db\Query\Predicates\Like::END);
						}
					}
				}
			}
		}

		$em = $this->getEventManager();
		$args = $em->prepareArgs(['documentQuery' => $documentQuery, 'filter' => $filter, 'predicateBuilder' => $predicateBuilder]);
		$em->trigger('getRestriction', $this, $args);
		if (isset($args['restriction']) && $args['restriction'] instanceof \Change\Db\Query\Predicates\InterfacePredicate)
		{
			return $args['restriction'];
		}
		return null;
	}
}