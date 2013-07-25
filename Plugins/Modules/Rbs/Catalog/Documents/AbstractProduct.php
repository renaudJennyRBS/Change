<?php
namespace Rbs\Catalog\Documents;

use Change\Documents\DocumentCollection;

/**
 * @name \Rbs\Catalog\Documents\AbstractProduct
 */
class AbstractProduct extends \Compilation\Rbs\Catalog\Documents\AbstractProduct
{
	/**
	 * @return \Rbs\Media\Documents\Image|null
	 */
	public function getFirstVisual()
	{
		$visuals = $this->getVisuals();
		return $visuals->count() ? $visuals[0] : null;
	}
}