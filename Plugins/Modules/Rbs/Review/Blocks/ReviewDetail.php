<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Review\Blocks\ReviewDetail
 */
class ReviewDetail extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('reviewId');
		$parameters->addParameterMeta('canEdit', true);
		$parameters->addParameterMeta('editionMode', false);

		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('reviewId') === null)
		{
			$target = $event->getParam('document');
			if ($target instanceof \Change\Documents\AbstractDocument)
			{
				$parameters->setParameterValue('reviewId', $target->getId());
			}
		}

		if ($parameters->getParameter('canEdit'))
		{
			$user = $event->getAuthenticationManager()->getCurrentUser();
			if ($user->authenticated())
			{
				$review = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('reviewId'));
				/* @var $review \Rbs\Review\Documents\Review */
				$parameters->setParameterValue('editionMode', $user->getId() === $review->getAuthorId());
			}
		}

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters(), getBlockResult(),
	 *        getPresentationServices(), getDocumentServices()
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$review = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('reviewId'));
		/* @var $review \Rbs\Review\Documents\Review */
		$urlManager = $event->getUrlManager();
		$attributes['review'] = $review->getInfoForTemplate($urlManager);
		if ($parameters->getParameter('editionMode'))
		{
			$attributes['editionMode'] = true;
			$attributes['review']['content'] = $review->getContent()->getRawText();
		}
		$attributes['displayVote'] = true;

		return 'review-detail.twig';
	}
}