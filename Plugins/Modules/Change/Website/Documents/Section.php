<?php
namespace Change\Website\Documents;

/**
 * @name \Change\Website\Documents\Section
 */
class Section extends \Compilation\Change\Website\Documents\Section
{
	/**
	 * @return string
	 */
	public function getPathSuffix()
	{
		return '/';
	}
}