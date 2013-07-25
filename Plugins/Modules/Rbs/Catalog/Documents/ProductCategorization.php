<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\ProductCategorization
 */
class ProductCategorization extends \Compilation\Rbs\Catalog\Documents\ProductCategorization
{
	/**
	 * @return bool
	 */
	public function isHighlighted()
	{
		return $this->getPosition() < 0;
	}
}
