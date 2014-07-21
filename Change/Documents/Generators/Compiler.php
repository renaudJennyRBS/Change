<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\Compiler
 */
class Compiler
{
	/**
	 * \Change\Documents\Generators\Model[]
	 */
	protected $models = [];
	
	/**
	 * @var array
	 */
	protected $rootModelNames = [];
	
	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param \Change\Application $application
	 * @param \Change\Services\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Application $application, \Change\Services\ApplicationServices $applicationServices)
	{
		$this->application = $application;
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @param string $vendor
	 * @param string $moduleName
	 * @param string $documentName
	 * @param string $definitionPath
	 * @throws \RuntimeException
	 * @return Model
	 */
	public function loadDocument($vendor, $moduleName, $documentName, $definitionPath)
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		if (is_readable($definitionPath) && $doc->load($definitionPath))
		{
			$model = new Model($vendor, $moduleName, $documentName);
			$model->setXmlDocument($doc, $this);
			$this->addModel($model);
		}
		else
		{
			throw new \RuntimeException('Unable to load document definition : ' . $definitionPath, 54001);
		}
		return $model;
	}
	
	/**
	 * @throws \Exception
	 */
	public function buildTree()
	{
		$this->initReplacedBy();

		$this->buildParent();
	}

	/**
	 * @throws \RuntimeException
	 */
	public function initReplacedBy()
	{
		foreach ($this->models as $model)
		{
			/* @var $model Model */
			$modelName = $model->getName();
			$extendName = $model->getExtends();
			if ($extendName)
			{
				if ($model->getReplace())
				{
					$replacedModel = $this->getModelByName($extendName);
					if (!$replacedModel)
					{
						throw new \RuntimeException('Document ' . $modelName . ' extend unknown ' . $extendName, 54002);
					}
					$oldReplacedBy = $replacedModel->replacedBy();
					if ($oldReplacedBy && $oldReplacedBy != $modelName)
					{
						throw new \RuntimeException('Model ' . $extendName . ' must be replaced by ' . $modelName . ' but already replaced by ' . $oldReplacedBy, 54005);
					}
					$replacedModel->replacedBy($modelName);
				}
			}
		}
	}

	/**
	 * @throws \RuntimeException
	 */
	public function buildParent()
	{
		$models = $this->models;
		foreach ($models as $model)
		{
			/* @var $model Model */
			if ($model->getInline())
			{
				continue;
			}
			$modelName = $model->getName();
			$extendName = $model->getExtends();
			if ($extendName)
			{
				$extModel = $this->getModelByName($extendName);
				if ($extModel === null)
				{
					throw new \RuntimeException('Document ' . $modelName . ' extend unknown ' . $model->getExtends(), 54002);
				}
				if ($extModel->replacedBy() && $extModel->replacedBy() != $modelName)
				{
					$extModel = $this->getModelByName($extModel->replacedBy());
				}
				$model->setParent($extModel);
			}
			else
			{
				$this->rootModelNames[] = $modelName;
			}
		}
	}

	/**
	 * 
	 */
	public function validateInheritance()
	{
		foreach ($this->rootModelNames as $modelName)
		{
			$model = $this->getModelByName($modelName);
			$this->validateInheritanceByModelName($model);
		}
	}

	/**
	 * @param Model $model
	 * @throws \RuntimeException
	 */
	public function validateInheritanceByModelName($model)
	{
		$model->validateInheritance();
		if (!$model->rootStateless() && !$model->getInline())
		{
			//Add Inverse Properties
			foreach ($model->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Generators\Property */
				if (!$property->getStateless() && $property->hasRelation())
				{
					$documentType = $property->getDocumentType();
					if ($documentType)
					{
						$targetModel = $this->getModelByName($documentType);
						if (!$targetModel)
						{
							throw new \RuntimeException('Inverse Property on unknown Model ' . $documentType . ' (' . $model->getName() . '::'
								. $property->getName() . ')', 54006);
						}

						$inverseProperty = new InverseProperty($targetModel, $property);
						$targetModel->addInverseProperty($inverseProperty);
					}
				}
			}
		}

		$children = $this->getChildren($model);
		foreach ($children as $child)
		{
			$this->validateInheritanceByModelName($child);
		}
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function cleanModelName($name)
	{
		return $name;
	}
	
	/**
	 * @param string $fullName
	 * @return Model|null
	 */
	public function getModelByName($fullName)
	{
		$name = $this->cleanModelName($fullName);
		return isset($this->models[$name]) ? $this->models[$name] : null;
	}

	/**
	 * @param Model $model
	 * @throws \RuntimeException
	 */
	public function addModel(Model $model)
	{
		$key = $this->cleanModelName($model->getName());
		if (isset($this->models[$key]))
		{
			throw new \RuntimeException('Duplicate model name: ' . $key);
		}
		$this->models[$key] = $model;
	}
	
	/**
	 * @param Model $model
	 * @return Model
	 */	
	public function getAncestors($model)
	{
		$result = [];
		while (($model = $model->getParent()) !== null)
		{
			$modelName = $model->getName();
			$result[$modelName] = $model;
		}
		return array_reverse($result, true);
	}
	
	/**
	 * @param Model $model
	 * @return Model[]
	 */
	public function getChildren($model)
	{
		$result = [];
		/** @var $child Model */
		foreach ($this->models as $child)
		{
			if ($child->getParent() === $model)
			{
				$result[$child->getName()] = $child;
			}
		}
		return $result;
	}

	/**
	 * @param Model $model
	 * @param boolean $excludeInjected
	 * @return Model[]
	 */
	public function getDescendants($model, $excludeInjected = false)
	{
		$result = array();
		foreach ($this->getChildren($model) as $name => $cm)
		{
			/* @var $cm Model */
			if ($excludeInjected && $cm->getReplace())
			{
				continue;
			}
			$result[$name] = $cm;

			$dm = $this->getDescendants($cm, $excludeInjected);
			if (count($dm))
			{
				$result = array_merge($result, $dm);
			}
		}
		return $result;
	}
	
	/**
	 * @return Model[]
	 */
	public function getModels()
	{
		return $this->models;
	}

	/**
	 * @return Model[]
	 */
	public function getRootModelNames()
	{
		return $this->rootModelNames;
	}

	public function saveModelsPHPCode()
	{
		$compilationPath = $this->application->getWorkspace()->compilationPath();

		$generator = new ModelsNamesClass();
		$generator->savePHPCode($this, $this->models, $compilationPath);

		$generator = new TreeNamesClass();
		$generator->savePHPCode($this, $this->models, $compilationPath);

		foreach ($this->models as $model)
		{
			/* @var $model Model */
			$generator = new ModelClass();
			$generator->savePHPCode($this, $model, $compilationPath);
			if ($model->getInline())
			{
				$generator = new BaseInlineClass();
				$generator->savePHPCode($this, $model, $compilationPath);

				if ($model->rootLocalized())
				{
					$generator = new InlineLocalizedClass();
					$generator->savePHPCode($this, $model, $compilationPath);
				}
			}
			else
			{

				$generator = new BaseDocumentClass();
				$generator->savePHPCode($this, $model, $compilationPath);

				if ($model->rootLocalized())
				{
					$generator = new DocumentLocalizedClass();
					$generator->savePHPCode($this, $model, $compilationPath);
				}
			}
		}

		$generator = new SchemaClass();
		$generator->savePHPCode($this, $this->applicationServices->getDbProvider(), $compilationPath);
	}
	
	public function generate()
	{
		$nbModels = 0;
		$plugins = $this->applicationServices->getPluginManager()->getModules();
		foreach($plugins as $plugin)
		{
			$vendor = $plugin->getVendor();
			$moduleName = $plugin->getShortName();
			foreach ($plugin->getDocumentDefinitionPaths() as $documentName => $definitionPath)
			{
				$this->loadDocument($vendor, $moduleName, $documentName, $definitionPath);
				$nbModels++;
			}
		}
		
		$this->buildTree();
		$this->validateInheritance();
		if (is_array($this->models) && count($this->models))
		{
			$this->saveModelsPHPCode();
		}
	}
}