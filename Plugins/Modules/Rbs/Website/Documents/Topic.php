<?php
namespace Rbs\Website\Documents;

/**
 * @name \Rbs\Website\Documents\Topic
 */
class Topic extends \Compilation\Rbs\Website\Documents\Topic
{
	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		return array($this);
	}
}