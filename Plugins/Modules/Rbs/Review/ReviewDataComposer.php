<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Review;

/**
 * @name \Rbs\Review\ReviewDataComposer
 */
class ReviewDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Review\Documents\Review
	 */
	protected $review;

	/**
	 * @var \Change\Documents\Correction
	 */
	protected $correction;

	/**
	 * @var boolean
	 */
	protected $useCorrection;

	/**
	 * @var \Rbs\Media\Avatar\AvatarManager
	 */
	protected $avatarManager;

	/**
	 * @var \Rbs\Review\ReviewManager
	 */
	protected $reviewManager;

	/**
	 * @var \Change\User\ProfileManager
	 */
	protected $profileManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	function __construct(\Change\Events\Event $event)
	{
		$this->review = $event->getParam('review');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		if ($this->hasDataSet('edition'))
		{
			$this->useCorrection = true;
			if ($this->review->hasCorrection())
			{
				$this->correction = $this->review->getCurrentCorrection();
			}
		}
		else
		{
			$this->useCorrection = false;
		}

		/** @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');
		$this->avatarManager = $genericServices->getAvatarManager();
		$this->reviewManager = $genericServices->getReviewManager();
		$this->profileManager = $event->getApplicationServices()->getProfileManager();

	}

	/**
	 * @param string $propertyName
	 * @return mixed|null
	 */
	protected function getPropertyValue($propertyName)
	{
		if ($this->correction && $this->correction->getPropertyValue($propertyName) !== null)
		{
			return $this->correction->getPropertyValue($propertyName);
		}
		else
		{
			return $this->review->getDocumentModel()->getPropertyValue($this->review, $propertyName);
		}
	}

	protected function generateDataSets()
	{
		$review = $this->review;

		$this->dataSets = [
			'common' => [
				'id' => $review->getId(),
				'title' => $review->getTitle(),
				'rating' => $this->getPropertyValue('rating'),
				'published' => $this->correction ? false : $review->published(),
				'content' => $this->formatRichText($this->getPropertyValue('content')),
				'reviewDate' => $this->formatDate($review->getReviewDate()),
				'promoted' => $review->getPromoted()
			],
			'votes' => $this->reviewManager->getVotesData($review->getId(), $this->getVoteContext())
		];

		if ($this->useCorrection)
		{
			$this->dataSets['common']['hasCorrection'] = $review->hasCorrection();
		}

		$this->generateAuthorDataSet();
		$this->generateTargetDataSet();

		if ($this->hasDataSet('edition'))
		{
			$this->generateEditionDataSet();
		}
	}

	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	protected function generateAuthorDataSet()
	{
		$review = $this->review;
		$this->dataSets['author'] = [
			'pseudonym' => $review->getPseudonym()
		];

		$author = $this->documentManager->getDocumentInstance($review->getAuthorId());
		if ($author instanceof \Rbs\User\Documents\User)
		{
			$this->dataSets['author']['id'] = $review->getAuthorId();
			$sizes = isset($this->data['avatarSizes']) ? $this->data['avatarSizes'] : [];
			if (is_array($sizes) && count($sizes))
			{
				$this->dataSets['author']['avatar'] = [];
				$this->avatarManager->setUrlManager($this->websiteUrlManager);
				foreach ($sizes as $size)
				{
					$params = [
						'size' => $size,
						'defaultImg' =>  isset($this->data['avatarDefaultImg']) ? $this->data['avatarDefaultImg'] : null,
						'rating' =>  isset($this->data['avatarRating']) ? $this->data['avatarRating'] : null,
						'secure' =>  isset($this->data['avatarSecure']) ? $this->data['avatarSecure'] : null
					];
					$url = $this->avatarManager->getAvatarUrl($size, $author->getEmail(), $author, $params);
					$this->dataSets['author']['avatar'][$size] = $url;
				}
			}
		}
	}

	protected function generateTargetDataSet()
	{
		$target = $this->review->getTarget();
		if ($target)
		{
			$this->dataSets['target'] = [
				'id' => $target->getId(),
				'modelName' => $target->getDocumentModelName(),
				'title' => $target->getDocumentModel()->getPropertyValue($target, 'title'),
				'url' => $this->websiteUrlManager->getCanonicalByDocument($target)->normalize()->toString()
			];
		}
	}

	protected function generateEditionDataSet()
	{
		$content = $this->getPropertyValue('content');
		$this->dataSets['edition'] = [
			'content' => [
				'editor' => $content->getEditor(),
				'raw' => $content->getRawText()
			]
		];
	}

	/**
	 * @return array
	 */
	protected function getVoteContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats, 'dataSetNames' => $this->dataSetNames,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => $this->data];
	}
}