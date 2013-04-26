<?php
namespace Change\Presentation\Interfaces;

/**
 * @name \Change\Presentation\Interfaces\Section
 */
interface Section
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return \Change\Presentation\Interfaces\Website
	 */
	public function getWebsite();
}