<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Product;

/**
 * @name \Rbs\Catalog\Product\ProductSetPresentation
 */
class ProductSetPresentation extends ProductPresentation
{
	/**
	 * @var \Rbs\Catalog\Product\ProductPresentation[]|null
	 */
	protected $subProductPresentations;

	/**
	 * @param \Rbs\Catalog\Product\ProductPresentation[] $subProductPresentations
	 * @return $this
	 */
	public function setSubProductPresentations($subProductPresentations)
	{
		$this->subProductPresentations = $subProductPresentations;
		return $this;
	}

	/**
	 * @param array $options
	 * @return \Rbs\Catalog\Product\ProductPresentation[]
	 */
	public function getSubProductPresentations(array $options = array())
	{
		if ($this->subProductPresentations === null)
		{
			if (!isset($options['urlManager']))
			{
				$options['urlManager'] = $this->getUrlManager();
			}
			if (!isset($options['webStore']))
			{
				$options['webStore'] = $this->getWebStore();
			}
			if (!isset($options['billingArea']))
			{
				$options['billingArea'] = $this->getBillingArea();
			}
			if (!isset($options['zone']))
			{
				$options['zone'] = $this->getZone();
			}
			$this->subProductPresentations = $this->getCatalogManager()->getSubProductPresentations($this, $options);
		}
		return $this->subProductPresentations;
	}

	/**
	 * @param integer $quantity
	 * @return $this
	 */
	public function evaluate($quantity = 1)
	{
		parent::evaluate($quantity);
		foreach ($this->getSubProductPresentations() as $productPresentation)
		{
			$productPresentation->evaluate($quantity);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateSuffix()
	{
		return 'set';
	}
}