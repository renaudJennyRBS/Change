<?php
namespace Rbs\Review\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Review\Blocks\PostReview
 */
class ReviewList extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('showAverageRating', true);
		$parameters->addParameterMeta('averageRatingPartsCount', 5);
		$parameters->addParameterMeta('reviewsPerPage', 10);
		$parameters->addParameterMeta('targetId');
		$parameters->addParameterMeta('sectionId');
		$parameters->addParameterMeta('websiteId');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber',
			intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));

		if ($parameters->getParameter('targetId') === null)
		{
			$target = $event->getParam('document');
			if ($target instanceof \Change\Documents\AbstractDocument)
			{
				$parameters->setParameterValue('targetId', $target->getId());
			}
		}

		/* @var $page \Rbs\Website\Documents\Page */
		$page = $event->getParam('page');
		$section = $page->getSection();
		if ($section instanceof \Rbs\Website\Documents\Section)
		{
			$parameters->setParameterValue('websiteId', $section->getWebsite()->getId());
			if ($parameters->getParameter('sectionId') === null)
			{
				$parameters->setParameterValue('sectionId', $section->getId());
			}
		}

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$target = $event->getApplicationServices()->getDocumentManager()
			->getDocumentInstance($parameters->getParameterValue('targetId'));
		$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Review_Review');
		//TODO add section of page to predicate?
		$dqb->andPredicates($dqb->published(), $dqb->eq('target', $target));
		//TODO order on upvote comment, but a formula between upvote and downvote will be better
		$dqb->addOrder('upvote', false);

		$urlManager = $event->getUrlManager();
		$rows = [];

		$totalCount = $dqb->getCountDocuments();
		$attributes['totalReviews'] = $totalCount;
		if ($totalCount)
		{
			$reviewsPerPage = $parameters->getParameter('reviewsPerPage');
			$pageCount = ceil($totalCount / $reviewsPerPage);
			$pageNumber = $parameters->getParameter('pageNumber');
			$pageNumber = !is_numeric($pageNumber) || $pageNumber < 1 || $pageNumber > $pageCount ? 1 : $pageNumber;

			$attributes['pageNumber'] = $pageNumber;
			$attributes['totalCount'] = $totalCount;
			$attributes['pageCount'] = $pageCount;

			/* @var $section \Rbs\Website\Documents\Section */
			$section = $event->getApplicationServices()->getDocumentManager()
				->getDocumentInstance($parameters->getParameterValue('sectionId'));

			$reviewFunctionalPageExist = false;

			if ($section instanceof \Rbs\Website\Documents\Section)
			{
				//search the function Rbs_Review_Review in section and his section path
				foreach ($section->getSectionPath() as $sectionFromPath)
				{
					$dqb2 = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Website_SectionPageFunction');
					$dqb2->andPredicates($dqb2->eq('section', $sectionFromPath), $dqb2->eq('functionCode', 'Rbs_Review_Review'));
					if ($dqb2->getCountDocuments())
					{
						$reviewFunctionalPageExist = true;
						break;
					}
				}
			}

			/* @var $product \Rbs\Catalog\Documents\Product */
			foreach ($dqb->getDocuments(($pageNumber - 1) * $reviewsPerPage, $reviewsPerPage) as $review)
			{
				/* @var $review \Rbs\Review\Documents\Review */

				$url = null;
				if ($reviewFunctionalPageExist)
				{
					$url = $urlManager->getCanonicalByDocument($review, $review->getSection()->getWebsite());
				}
				else
				{
					$url = $urlManager->getSelf()->setQuery(['pageNumber-' . $attributes['blockId'] => $pageNumber])
						->setFragment('review-' . $review->getId());
				}
				$infoForTemplate = $review->getInfoForTemplate($urlManager);
				$infoForTemplate['url'] = $url;
				$rows[] = $infoForTemplate;
			}
		}

		$attributes['rows'] = $rows;
		$attributes['displayVote'] = true;

		return 'review-list.twig';
	}
}