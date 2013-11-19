<?php
namespace Rbs\Review\Documents;

use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Rbs\Review\Documents\Review
 */
class Review extends \Compilation\Rbs\Review\Documents\Review
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
	}

	public function onDefaultSave(DocumentEvent $event)
	{
		$review = $event->getDocument();
		if ($review instanceof Review)
		{
			if ($review->isPropertyModified('content'))
			{
				$event->getApplicationServices()->getRichTextManager()->render($review->getContent(), 'Admin');
			}
			$targetLabel = $review->getTarget() ? $review->getTarget()->getLabel() : '';
			$review->setLabel($event->getApplicationServices()->getI18nManager()->trans('m.rbs.review.admin.review_label_content', array('ucf'), array('targetLabel' => $targetLabel, 'pseudonym' => $review->getPseudonym())));
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
