<?php
namespace Change\Website\Documents;

/**
 * @name \Change\Website\Documents\Topic
 */
class Topic extends \Compilation\Change\Website\Documents\Topic
{
	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		return array($this);
	}
}