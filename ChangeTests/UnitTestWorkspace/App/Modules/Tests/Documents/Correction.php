<?php
namespace Project\Tests\Documents;

/**
 * @name \Project\Tests\Documents\Correction
 */
class Correction extends \Compilation\Project\Tests\Documents\Correction
{
	/**
	 * @var \Change\Presentation\Interfaces\Section[]
	 */
	protected $publicationSections = array();

	/**
	 * @param \Change\Presentation\Interfaces\Section[] $publicationSections
	 */
	public function setPublicationSections($publicationSections)
	{
		$this->publicationSections = $publicationSections;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		return $this->publicationSections;
	}
}