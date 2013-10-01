<?php
namespace Rbs\Seo\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Seo\Blocks\HeadMetas
 */
class HeadMetas extends Block
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
		$parameters->addParameterMeta('documentId');

		$document = $event->getParam('document');
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$parameters->setParameterValue('documentId', $document->getId());
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
		$ds = $event->getDocumentServices();
		$document = $ds->getDocumentManager()->getDocumentInstance($event->getBlockParameters()->getParameter('documentId'));
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$attributes['document'] = $document;
			$query = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Seo_DocumentSeo');
			$query->andPredicates($query->eq('target', $document));
			$documentSeo = $query->getFirstDocument();
			if ($documentSeo instanceof \Rbs\Seo\Documents\DocumentSeo && !$documentSeo->getCurrentLocalization()->isNew())
			{
				$attributes['title'] = $documentSeo->getCurrentLocalization()->getMetaTitle();
				$attributes['description'] = $documentSeo->getCurrentLocalization()->getMetaDescription();
				$attributes['keywords'] = $documentSeo->getCurrentLocalization()->getMetaKeywords();
			}

			if (!isset($attributes['title']))
			{
				if ($document instanceof \Change\Documents\Traits\Localized &&
					is_callable(array($document->getCurrentLocalization(), 'getTitle')))
				{
					$attributes['title'] = $document->getCurrentLocalization()->getTitle();
				}
				elseif (is_callable(array($document, 'getTitle')))
				{
					$attributes['title'] = $document->getTitle();
				}
			}
		}
		return 'head-metas.twig';
	}
}