<?php
namespace Rbs\Mail\Documents;

/**
 * @name \Rbs\Mail\Documents\Mail
 */
class Mail extends \Compilation\Rbs\Mail\Documents\Mail implements \Change\Presentation\Interfaces\Page
{
	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->getId() . ',' . $this->getCurrentLCID();
	}

	/**
	 * @return \Datetime|null
	 */
	public function getModificationDate()
	{
		return $this->getCurrentLocalization()->getModificationDate();
	}

	/**
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout()
	{
		return new \Change\Presentation\Layout\Layout($this->getCurrentLocalization()->getEditableContent());
	}

	/**
	 * @return string|null
	 */
	public function getTitle()
	{
		return $this->getCurrentLocalization()->getSubject();
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getSection()
	{
		//FIXME how to find the correct website in websites?
		return count($this->getWebsites()) ? $this->getWebsites()[0] : null;
	}
}
