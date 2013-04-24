<?php
namespace Change\Presentation\Interfaces;

/**
 * @name \Change\Presentation\Interfaces\ThemeResource
 */
interface ThemeResource
{
	/**
	 * @return boolean
	 */
	public function isValid();

	/**
	 * @return \Datetime
	 */
	public function getModificationDate();

	/**
	 * @return string
	 */
	public function getContent();

	/**
	 * @return string
	 */
	public function getContentType();

}