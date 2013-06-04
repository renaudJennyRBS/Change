<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\AbstractProduct
 */
class AbstractProduct extends \Compilation\Rbs\Catalog\Documents\AbstractProduct
{
	/**
	 * @return \Rbs\Media\Documents\Image|null
	 */
	public function getDefaultVisual()
	{
		return $this->getVisualByIndex(0);
	}

	/**
	 * @param \Rbs\Media\Documents\Image|null $defaultVisual
	 */
	public function setDefaultVisual($defaultVisual)
	{
		if ($defaultVisual)
		{
			$this->setVisualAtIndex($defaultVisual, 0);
		}
		else
		{
			$this->removeVisualByIndex(0);
		}
	}
}