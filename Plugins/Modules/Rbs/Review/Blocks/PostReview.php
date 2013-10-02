<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Review\Blocks\PostReview
 */
class PostReview extends Block
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
		$parameters->addParameterMeta('targetId');
		$parameters->addParameterMeta('sectionId');
		$parameters->addParameterMeta('alreadyReviewed', false);
		$parameters->addParameterMeta('canEdit', true);
		$parameters->addParameterMeta('pseudonym');
		$parameters->addParameterMeta('content');
		$parameters->addParameterMeta('rating');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$documentManager = $event->getDocumentServices()->getDocumentManager();
			$targetFromParameter = $documentManager->getDocumentInstance($parameters->getParameter('targetId'));
			$target = $targetFromParameter !== null ? $targetFromParameter : $event->getParam('document');
			$sectionFromParameter = $documentManager->getDocumentInstance($parameters->getParameter('sectionId'));
			$section = $sectionFromParameter !== null ? $sectionFromParameter : $event->getParam('page')->getSection();
			if ($target instanceof \Change\Documents\AbstractDocument && $section instanceof \Change\Presentation\Interfaces\Section)
			{
				$userDoc = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($user->getId());
				/* @var $userDoc \Rbs\User\Documents\User */
				$parameters->setParameterValue('userId', $userDoc->getId());
				//find if user has already reviewed this target in this section
				$reviewModel = $documentManager->getModelManager()->getModelByName('Rbs_Review_Review');
				$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), $reviewModel);
				$dqb->andPredicates(
					$dqb->eq('target', $target),
					$dqb->eq('section', $section),
					$dqb->eq('authorId', $userDoc->getId())
				);
				$review = $dqb->getFirstDocument();
				if (!$review)
				{
					$parameters->setParameterValue('pseudonym', $userDoc->getPseudonym());
					$parameters->setParameterValue('targetId', $target->getId());
					$parameters->setParameterValue('sectionId', $section->getId());
				}
				else
				{
					/* @var $review \Rbs\Review\Documents\Review */
					$parameters->setParameterValue('alreadyReviewed', true);
					$parameters->setParameterValue('reviewId', $review->getId());
				}
			}
		}
		else
		{
			$parameters->setParameterValue('userId', false);
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
		$attributes['notConnected'] = !$parameters->getParameter('userId');
		if ($parameters->getParameter('alreadyReviewed'))
		{
			$review = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('reviewId'));
			/* @var $review \Rbs\Review\Documents\Review */
			$attributes['review'] = $review->getInfoForTemplate($event->getUrlManager());
			$attributes['notPublished'] = !$review->published();
			$attributes['hasCorrection'] = $review->hasCorrection();
			if ($review->hasCorrection())
			{
				$attributes['correction'] = [];
				$attributes['correction']['reviewStarRating'] = ceil($review->getCurrentCorrection()->getPropertyValue('rating')*(5/100));
				$newContent = $review->getCurrentCorrection()->getPropertyValue('content');
				/* @var $newContent \Change\Documents\RichtextProperty */
				$attributes['correction']['content'] = $newContent ? $newContent->getHtml() : null;
			}
			$attributes['validationClass'] = $review->published() ? ' panel-success' : ' panel-warning';
			$attributes['displayVote'] = false;
		}
		else
		{
			//for edition mode (will show the form and set the star rating engine)
			$attributes['editionMode'] = true;
			$attributes['reviewStarRating'] = 4;
		}
		return 'post-review.twig';
	}
}