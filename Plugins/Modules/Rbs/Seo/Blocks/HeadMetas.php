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

		$page = $event->getParam('page');
		$document = $event->getParam('document');
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$parameters->setParameterValue('documentId', $document->getId());
		}
		elseif ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('documentId', $page->getId());
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
			$page = $event->getParam('page');
			$seoManager = new \Rbs\Seo\Services\SeoManager();
			$seoManager->setDocumentServices($event->getDocumentServices());
			$metas = $seoManager->getMetas($page, $document);
			$attributes['title'] = $metas['title'];
			$attributes['description'] = $metas['description'];
			$attributes['keywords'] = $metas['keywords'];
		}
		return 'head-metas.twig';
	}
}