<?php
namespace Change\Presentation\Interfaces;

/**
 * @name \Change\Presentation\Interfaces\Section
 */
interface Section
{
	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return \Change\Presentation\Interfaces\Website
	 */
	public function getWebsite();

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getSectionPath();
}