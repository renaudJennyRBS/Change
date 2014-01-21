<?php
namespace Rbs\Website\Documents;

use Change\Presentation\Layout\Layout;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Website\Documents\Page
 */
abstract class Page extends \Compilation\Rbs\Website\Documents\Page implements \Change\Presentation\Interfaces\Page
{
	/**
	 * @see \Change\Presentation\Interfaces\Page::getIdentifier()
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->getId() . ',' . $this->getCurrentLCID();
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getContentLayout()
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return new Layout($this->getCurrentLocalization()->getEditableContent());
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getModificationDate()
	 * @return \DateTime
	 */
	public function getModificationDate()
	{
		return $this->getCurrentLocalization()->getModificationDate();
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getModificationDate()
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getCurrentLocalization()->getTitle();
	}

	/**
	 * @return \Change\Presentation\Interfaces\Template
	 */
	public function getTemplate()
	{
		return $this->getPageTemplate();
	}
}