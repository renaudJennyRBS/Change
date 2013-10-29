<?php
namespace Rbs\Review\Documents;

use Change\Presentation\PresentationServices;

/**
 * @name \Rbs\Review\Documents\Review
 */
class Review extends \Compilation\Rbs\Review\Documents\Review
{
	protected function updateLabel()
	{
		$this->setLabel($this->getApplicationServices()->getI18nManager()->trans('m.rbs.review.documents.review.label-content', array('ucf'), array('targetLabel' => $this->getTarget()->getLabel(), 'pseudonym' => $this->getPseudonym())));
	}

	protected function onCreate()
	{
		if ($this->isPropertyModified('content'))
		{
			$this->transformMarkdownToHtml();
		}
		$this->updateLabel();
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('content'))
		{
			$this->transformMarkdownToHtml();
		}
		$this->updateLabel();
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


	/**
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return array
	 */
	public function getInfoForTemplate($urlManager)
	{
		$author = $this->getDocumentManager()->getDocumentInstance($this->getAuthorId());
		if ($author)
		{
			$email = $author->getEmail();
		}
		$target = $this->getTarget();
		/* @var $target \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable|\Change\Documents\Interfaces\Editable */
		return [
			'id' => $this->getId(),
			'pseudonym' => $this->getPseudonym(),
			'rating' => $this->getRating(),
			'reviewStarRating' => ceil($this->getRating()*(5/100)),
			'reviewDate' => $this->getReviewDate(),
			'content' => $this->getContent()->getHtml(),
			'promoted' => $this->getPromoted(),
			'url' => $urlManager->getCanonicalByDocument($this, $this->getSection()->getWebsite()),
			'upvote' => $this->getUpvote(),
			'downvote' => $this->getDownvote(),
			//TODO: getLabel for target is not a good thing, find another way
			'target' => [ 'title' => $target->getLabel(), 'url' => $urlManager->getCanonicalByDocument($target, $this->getSection()->getWebsite()) ],
			'author' => $author,
			'email' => $email
		];
	}
}
