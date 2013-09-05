<?php
namespace Rbs\Theme\Documents;

/**
 * @name \Rbs\Theme\Documents\MailTemplate
 */
class MailTemplate extends \Compilation\Rbs\Theme\Documents\MailTemplate implements \Change\Presentation\Interfaces\MailTemplate
{

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->getLabel();
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->getCurrentLocalization()->getContent();
	}

	/**
	 * @return string
	 */
	public function getSubject()
	{
		return $this->getCurrentLocalization()->getSubject();
	}
}
