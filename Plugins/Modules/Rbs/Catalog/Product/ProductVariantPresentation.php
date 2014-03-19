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
 * @name \Rbs\Catalog\Product\VariantProductPresentation
 */
class ProductVariantPresentation extends ProductPresentation
{
	/**
	 * @var array
	 */
	protected $variantsConfiguration = null;

	/**
	 * @return array
	 */
	public function getVariantsConfiguration()
	{
		if ($this->variantsConfiguration === null)
		{
			$this->variantsConfiguration = $this->catalogManager->getVariantsConfiguration($this->productId, true);
		}
		return $this->variantsConfiguration;
	}

	/**
	 * @return string
	 */
	public function getTemplateSuffix()
	{
		return 'variant';
	}
}