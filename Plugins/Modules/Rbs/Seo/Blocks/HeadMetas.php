<?php
namespace Rbs\Seo\Blocks;

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
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
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
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$genericServices = $event->getServices('genericServices');
		if ($genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getBlockParameters()->getParameter('documentId'));
			if ($document instanceof \Change\Documents\AbstractDocument)
			{
				$attributes['document'] = $document;
				$page = $event->getParam('page');
				$seoManager = $genericServices->getSeoManager();
				foreach ($seoManager->getMetas($page, $document) as $key => $meta)
				{
					$attributes[$key] = $meta;
				}
			}
			return 'head-metas.twig';
		}
		return null;
	}
}