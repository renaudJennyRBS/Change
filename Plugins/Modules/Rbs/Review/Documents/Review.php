<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Review\Documents;

use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Rbs\Review\Documents\Review
 */
class Review extends \Compilation\Rbs\Review\Documents\Review
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onDefaultSave(DocumentEvent $event)
	{
		$review = $event->getDocument();
		if ($review instanceof Review)
		{
			$targetLabel = $review->getTarget() ? $review->getTarget()->getLabel() : '';
			$key = 'm.rbs.review.admin.review_label_content';
			$replacements = array('targetLabel' => $targetLabel, 'pseudonym' => $review->getPseudonym());
			$review->setLabel($event->getApplicationServices()->getI18nManager()->trans($key, array('ucf'), $replacements));
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
		$email = null;
		/* @var $author \Rbs\User\Documents\User */
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
			'upvote' => $this->getUpvote(),
			'downvote' => $this->getDownvote(),
			//TODO: getLabel for target is not a good thing, find another way
			'target' => [ 'title' => $target->getLabel(), 'url' => $urlManager->getCanonicalByDocument($target) ],
			'author' => $author,
			'email' => $email
		];
	}
}
