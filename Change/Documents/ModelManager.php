<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	protected $documentModels = [];

	/**
	 * @var \Compilation\Change\Documents\ModelsInfos
	 */
	protected $modelsInfos = null;

	/**
	 * @var \Change\Plugins\PluginManager
	 */
	protected $pluginManager  = null;

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
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/ModelManager');
	}

	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getFiltersDefinition', [$this, 'onDefaultGetFiltersDefinition'], 5);
		$eventManager->attach('getFilteredModelsNames', [$this, 'onDefaultGetFilteredModelsNames'], 5);
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
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->getApplication()->getWorkspace();
	}

	/**
	 * @param string $modelName
	 * @return \Change\Documents\AbstractModel|null
	 */
	public function getModelByName($modelName)
	{
		if (!array_key_exists($modelName, $this->documentModels))
		{
			$className = $this->buildModelClassName($modelName);
			if ($className)
			{
				/* @var $model \Change\Documents\AbstractModel */
				$model = new $className($this);
				if ($model->getReplacedBy())
				{
					$model = $this->getModelByName($model->getReplacedBy());
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
	 * @return \Compilation\Change\Documents\ModelsInfos
	 */
	protected function getModelsInfos()
	{
		if ($this->modelsInfos === null)
		{
			$this->modelsInfos = new \Compilation\Change\Documents\ModelsInfos();
		}
		return $this->modelsInfos;
	}

	/**
	 * @return string[]
	 */
	public function getModelsNames()
	{
		return $this->getModelsInfos()->getNames();
	}

	/**
	 * @api
	 * @param array $filters
	 * Available filters:
	 *  - onlyInstalled [true]|false
	 *  - publishable true|false
	 *  - activable true|false
	 *  - localized true|false
	 *  - editable true|false
	 *  - abstract true|false
	 *  - inline true|false
	 *  - stateless true|false
	 *  - correction true|false
	 *  - leaf true|false
	 *  - root true|false
	 *  - vendor <vendorName>
	 *  - module <shortModuleName>
	 *  - extends <modelName>
	 * @return string[]
	 */
	public function getFilteredModelsNames($filters)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['filters' => $filters]);
		$eventManager->trigger('getFilteredModelsNames', $this, $args);
		if (isset($args['modelNames']) && is_array($args['modelNames']))
		{
			return $args['modelNames'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetFilteredModelsNames(\Change\Events\Event $event)
	{
		if (is_array($event->getParam('modelNames')))
		{
			return;
		}

		$filters = $event->getParam('filters');
		if (!is_array($filters))
		{
			$filters = [];
		}
		if (!isset($filters['onlyInstalled']))
		{
			$filters['onlyInstalled'] = true;
		}
		$pluginManager = $event->getApplicationServices()->getPluginManager();

		$modelNames = [];
		foreach ($this->getModelsInfos()->getInfos() as $modelName => $modelInfos)
		{
			foreach ($filters as $filterName => $filterValue)
			{
				switch ($filterName)
				{
					case 'onlyInstalled':
						if ($filterValue)
						{
							list($vendor, $moduleName,) = explode('_', $modelName);
							$plugin = $pluginManager->getModule($vendor, $moduleName);
							if (!$plugin || !$plugin->getActivated())
							{
								continue 3;
							}
						}
						break;

					case 'vendor':
						list($vendor,,) = explode('_', $modelName);
						if ($vendor != $filterValue)
						{
							continue 3;
						}
						break;

					case 'module':
						list(, $moduleName,) = explode('_', $modelName);
						if ($moduleName != $filterValue)
						{
							continue 3;
						}
						break;

					case 'publishable':
						if ($modelInfos['pu'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'activable':
						if ($modelInfos['ac'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'localized':
						if ($modelInfos['lo'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'editable':
						if ($modelInfos['ed'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'abstract':
						if ($modelInfos['ab'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'inline':
						if ($modelInfos['in'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'stateless':
						if ($modelInfos['st'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'correction':
						if ($modelInfos['co'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'leaf':
						if ($modelInfos['le'] != $filterValue)
						{
							continue 3;
						}
						break;

					case 'root':
						if ($modelInfos['ro'] != $filterValue)
						{
							continue 3;
						}
						break;

					default:
						$event->getApplication()->getLogging()->warn(__METHOD__ . ' Unknown filter: ' . $filterName);
				}
			}
			$modelNames[] = $modelName;
		}
		$event->setParam('modelNames', $modelNames);
	}

	/**
	 * @return string[]
	 */
	public function getVendors()
	{
		$vendors = [];
		foreach ($this->getModelsNames() as $name)
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
		$smn = [];
		foreach ($this->getModelsNames() as $name)
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
		$sdn = [];
		foreach ($this->getModelsNames() as $name)
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
	protected function buildModelClassName($modelName)
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
		$docPath = implode(DIRECTORY_SEPARATOR, [$module->getAbsolutePath(), 'Documents', 'Assets', $normalizedShortModelName . '.xml']);
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
		$docPath = implode(DIRECTORY_SEPARATOR, [$module->getAbsolutePath(), 'Documents', $normalizedShortModelName . '.php']);
		if (file_exists($docPath))
		{
			throw new \RuntimeException('Final PHP Document file already exists at path ' . $docPath, 999999);
		}
		$attributes = ['vendor' => $normalizedVendorName, 'module' => $normalizedModuleName, 'name' => $normalizedShortModelName];
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
				'authorId', 'authorName', 'creationDate', 'modificationDate', 'refLCID', 'LCID'];
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
						if ($property->getDocumentType()) {
							$definition['config']['documentType'] = $property->getDocumentType();
						}
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
					elseif ($definition['name'] === 'LCID' || $definition['name'] === 'refLCID')
					{
						$possibleValues = [];
						foreach ($i18nManager->getSupportedLCIDs() as $LCID)
						{
							$possibleValues[] = ['value' => $LCID, "label"  => $LCID];
						}
						$definition['config']['possibleValues'] = $possibleValues;
					}
					elseif ($definition['name'] === 'model')
					{
						$possibleValues = [];
						$possibleValues[] = ['value' => $model->getName(),
							"label" => $i18nManager->trans($model->getLabelKey(), ['ucf'])];
						foreach ($model->getDescendantsNames() as $descendantName)
						{
							$descendantModel = $this->getModelByName($descendantName);
							if ($descendantModel) {
								$possibleValues[] = ['value' => $descendantModel->getName(),
									"label" => $i18nManager->trans($descendantModel->getLabelKey(), ['ucf'])];
							}
						}
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
			if (isset($parameters['propertyName']) && isset($parameters['operator']))
			{
				$property = $documentQuery->getModel()->getProperty($parameters['propertyName']);
				if ($property)
				{
					if ($property->getName() === 'LCID')
					{
						if (isset($parameters['value']))
						{
							$documentQuery->setLCID($parameters['value']);
							return $predicateBuilder->eq($property, $parameters['value']);
						}
						return null;
					}
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
							case 'isNull':
								return $predicateBuilder->isNull($property);
							case 'isNotNull':
								return $predicateBuilder->isNotNull($property);
						}
					}
					else
					{
						switch($parameters['operator'])
						{
							case 'isNull':
								return $predicateBuilder->isNull($property);
							case 'isNotNull':
								return $predicateBuilder->isNotNull($property);
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