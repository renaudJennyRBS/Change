<?php
namespace Change\Website\Documents;

/**
 * @name \Change\Website\Documents\Website
 */
class Website extends \Compilation\Change\Website\Documents\Website implements \Change\Presentation\Interfaces\Website
{
	/**
	 * @return string
	 */
	public function getRelativePath()
	{
		return  $this->getPathPart();
	}
}