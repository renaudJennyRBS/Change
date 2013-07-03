<?php
namespace Change\Presentation\Interfaces;

/**
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
	 * @return integer
	 */
	public function getPort();

	/**
	 * @return string
	 */
	public function getScriptName();

	/**
	 * Returned string do not start and end with '/' char
	 * @return string|null
	 */
	public function getRelativePath();

	/**
	 * @return string
	 */
	public function getBaseurl();

	/**
	 * @param string $LCID
	 * @return \Change\Http\Web\UrlManager
	 */
	public function getUrlManager($LCID);
}