<?php
namespace Rbs\Review\Documents;

use Change\Presentation\PresentationServices;

/**
 * @name \Rbs\Review\Documents\Review
 */
class Review extends \Compilation\Rbs\Review\Documents\Review
{
	public function getLabel()
	{
		if (!parent::getLabel())
		{
			//TODO: do something right
			return ' ';
		}
		return parent::getLabel();
	}

	protected function onCreate()
	{
		if ($this->isPropertyModified('content'))
		{
			$this->transformMarkdownToHtml();
		}
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('content'))
		{
			$this->transformMarkdownToHtml();
		}
	}

	/**
	 * @return array|\Change\Documents\AbstractDocument
	 */
	public function getPublicationSections()
	{
		if ($this->getSection())
		{
			return array($this->getSection());
		}
		return array();
	}

	/**
	 * //TODO only markdown for now
	 */
	private function transformMarkdownToHtml()
	{
		$ps = new PresentationServices($this->getApplicationServices());
		//set a web profile
		$ps->getRichTextManager()->setDocumentServices($this->getDocumentServices())->render($this->getContent(), 'Admin');
	}
}
