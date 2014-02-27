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
 * @name \Change\Documents\Generators\InverseProperty
 */
class InverseProperty
{
	
	/**
	 * @var \Change\Documents\Generators\Model
	 */
	protected $model;
		
	/**
	 * @var \Change\Documents\Generators\Property
	 */
	protected $relatedProperty;
		
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 */
	public function __construct(\Change\Documents\Generators\Model $model, \Change\Documents\Generators\Property $property)
	{
		$this->model = $model;
		$this->relatedProperty = $property;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		$model = ($this->relatedProperty->getModel()->getReplace()) ? $this->relatedProperty->getModel()->getParent() : $this->relatedProperty->getModel();
		return $model->getVendor() . $model->getShortModuleName(). $model->getShortName().ucfirst($this->relatedProperty->getName());
	}
	
	/**
	 * @return string
	 */
	public function getRelatedType()
	{
		return $this->relatedProperty->getComputedType();
	}

	/**
	 * @return string
	 */
	public function getRelatedDocumentName()
	{
		$model = ($this->relatedProperty->getModel()->getReplace()) ? $this->relatedProperty->getModel()->getParent() : $this->relatedProperty->getModel();
		return $model->getName();
	}

	/**
	 * @return string
	 */
	public function getRelatedPropertyName()
	{
		return $this->relatedProperty->getName();
	}
}
