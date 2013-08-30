<?php
namespace Change\Presentation\Interfaces;

/**
 * @name \Change\Presentation\Interfaces\MailTemplate
 */
interface MailTemplate
{
	/**
	 * @return \Change\Presentation\Interfaces\Theme
	 */
	public function getTheme();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getContent();

	/**
	 * @return string
	 */
	public function getSubject();
}