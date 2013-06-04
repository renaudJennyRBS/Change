<?php
namespace Change\Catalog\Documents;

/**
 * @name \Change\Catalog\Documents\AbstractProduct
 */
class AbstractProduct extends \Compilation\Change\Catalog\Documents\AbstractProduct
{
	/**
	 * @return \Change\Media\Documents\Image|null
	 */
	public function getDefaultVisual()
	{
		return $this->getVisualByIndex(0);
	}

	/**
	 * @param \Change\Media\Documents\Image|null $defaultVisual
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