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
	 * @return string
	 */
	public function getTitle()
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['review' => $this]);
		$documentEvent = new \Change\Documents\Events\Event('getTitle', $this, $eventArgs);
		$em->trigger($documentEvent);
		return (isset($eventArgs['title'])) ? $eventArgs['title'] : '';
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onDefaultGetTitle(DocumentEvent $event)
	{
		if ($event->getParam('title'))
		{
			return;
		}

		$review = $event->getDocument();
		if ($review instanceof Review)
		{
			$target = $review->getTarget();
			$targetTitle = ($target instanceof \Change\Documents\AbstractDocument) ? $target->getDocumentModel()->getPropertyValue($target, 'title') : '';
			$key = 'm.rbs.review.front.review_title_content';
			$replacements = ['TARGET' => $targetTitle, 'PSEUDONYM' => $review->getPseudonym()];
			$event->setParam('title', $event->getApplicationServices()->getI18nManager()->trans($key, ['ucf'], $replacements));
		}
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		// Do nothing.
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs([]);
		$documentEvent = new \Change\Documents\Events\Event('getLabel', $this, $eventArgs);
		$em->trigger($documentEvent);
		return (isset($eventArgs['label'])) ? $eventArgs['label'] : '';
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onDefaultGetLabel(DocumentEvent $event)
	{
		if ($event->getParam('label'))
		{
			return;
		}

		$review = $event->getDocument();
		if ($review instanceof Review)
		{
			$target = $review->getTarget();
			$targetLabel = ($target instanceof \Change\Documents\AbstractDocument) ? $target->getDocumentModel()->getPropertyValue($target, 'label') : '';
			$key = 'm.rbs.review.admin.review_label_content';
			$replacements = ['targetLabel' => $targetLabel, 'pseudonym' => $review->getPseudonym()];
			$event->setParam('label', $event->getApplicationServices()->getI18nManager()->trans($key, ['ucf'], $replacements));
		}
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		// Do nothing.
		return $this;
	}

	/**
	 * @@param array|null $options
	 * @return string
	 */
	public function getPseudonym($options = null)
	{
		$em = $this->getEventManager();
		$eventArgs = (is_array($options) && count($options)) ? $em->prepareArgs($options) : $em->prepareArgs([]);
		$documentEvent = new \Change\Documents\Events\Event('getPseudonym', $this, $eventArgs);
		$em->trigger($documentEvent);
		return (isset($eventArgs['pseudonym'])) ? $eventArgs['pseudonym'] : null;
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onGetPseudonym(DocumentEvent $event)
	{
		if ($event->getParam('pseudonym'))
		{
			return;
		}

		$review = $event->getDocument();
		if ($review instanceof Review)
		{
			$pseudonym = null;
			$author = $review->getAuthorIdInstance();
			if ($author instanceof \Rbs\User\Documents\User)
			{
				$author = new \Rbs\User\Events\AuthenticatedUser($author);
				$webProfile = $event->getApplicationServices()->getProfileManager()->loadProfile($author, 'Rbs_Website');
				$pseudonym = ($webProfile) ? $webProfile->getPropertyValue('pseudonym') : null;
			}

			if (!$pseudonym && !($event->getParam('ignoreGuestPseudonym')))
			{
				$pseudonym = $review->getGuestPseudonym();
			}
			$event->setParam('pseudonym', $pseudonym);
		}
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onDefaultSave(DocumentEvent $event)
	{
		$review = $event->getDocument();
		if ($review instanceof Review && !$review->getReviewDate())
		{
			$review->setReviewDate(new \DateTime());
		}
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
		$eventManager->attach(['getLabel'], [$this, 'onDefaultGetLabel'], 5);
		$eventManager->attach(['getTitle'], [$this, 'onDefaultGetTitle'], 5);
		$eventManager->attach(['getPseudonym'], [$this, 'onGetPseudonym'], 5);
	}

	/**
	 * @return array|\Change\Documents\AbstractDocument
	 */
	public function getPublicationSections()
	{
		return ($this->getSection()) ? [$this->getSection()] : [];
	}

	/**
	 * @param \Change\Presentation\Interfaces\Section[] $publicationSections
	 * @return $this
	 */
	public function setPublicationSections($publicationSections)
	{
		if (is_array($publicationSections) && count($publicationSections))
		{
			$this->setSection($publicationSections[0]);
		}
		return $this;
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onDefaultUpdateRestResult(DocumentEvent $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		$document = $event->getDocument();
		if ($document instanceof Review)
		{
			if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
			{
				$pseudonym = $document->getPseudonym();
				$restResult->setProperty('pseudonym', $pseudonym);
				$canEditPseudonym = $pseudonym ? !$document->getPseudonym(['ignoreGuestPseudonym' => true]) : true;
				$restResult->setProperty('canEditPseudonym', $canEditPseudonym);
				$richTextManager = $event->getApplicationServices()->getRichTextManager();
				$restResult->setProperty('renderedContent', $richTextManager->render($document->getContent(), 'Admin'));
			}
			elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
			{
				$restResult->setProperty('pseudonym', $document->getPseudonym());
			}
		}
	}
}
