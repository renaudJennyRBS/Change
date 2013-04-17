<?php
namespace Change\Presentation\Interfaces;

/**
 * @package Change\Presentation\Interfaces
 * @name \Change\Presentation\Interfaces\Website
 */
interface Website
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getLCID();

	/**
	 * @return string
	 */
	public function getHostName();

	/**
	 * @return string
	 */
	public function getScriptName();

	/**
	 * Returned string do not start and end with '/' char
	 * @return string|null
	 */
	public function getRelativePath();
}