<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;
use Change\Documents\Interfaces\Publishable;

/**
 * @name \Rbs\Website\Documents\StaticPage
 */
class StaticPage extends \Compilation\Rbs\Website\Documents\StaticPage
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);

		$eventManager->attach(array(Event::EVENT_CREATE, Event::EVENT_UPDATE), [$this, 'onValidateDisplayDocument'], 5);


		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 10);

		$callback = function (Event $event)
		{
			/* @var $page StaticPage */
			$page = $event->getDocument();
			if ($page->getSection())
			{
				$tm = $event->getApplicationServices()->getTreeManager();
				$parentNode = $tm->getNodeByDocument($page->getSection());
				if ($parentNode)
				{
					$tm->insertNode($parentNode, $page);
				}
			}
		};
		$eventManager->attach(Event::EVENT_CREATED, $callback);
		$callback = function (Event $event)
		{
			/* @var $page StaticPage */
			if (in_array('section', $event->getParam('modifiedPropertyNames', array())))
			{
				$page = $event->getDocument();
				$tm = $event->getApplicationServices()->getTreeManager();
				$tm->deleteDocumentNode($page);
				if ($page->getSection())
				{
					$parentNode = $tm->getNodeByDocument($page->getSection());
					if ($parentNode)
					{
						$tm->insertNode($parentNode, $page);
					}
				}
			}
		};
		$eventManager->attach(Event::EVENT_UPDATED, $callback);
		$eventManager->attach('fullTextContent', array($this, 'onDefaultFullTextContent'), 5);
	}

	/**
	 * @param Event $event
	 */
	public function onValidateDisplayDocument(Event $event)
	{
		/** @var $staticPage StaticPage */
		$staticPage = $event->getDocument();
		if ($staticPage->isPropertyModified('displayDocument') && ($displayDocument = $staticPage->getDisplayDocument()) !== null)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$propertiesErrors = [];
			if ($displayDocument instanceof Page || $displayDocument instanceof Section || !($displayDocument instanceof Publishable))
			{
				$propertiesErrors['displayDocument'][] = $i18nManager->trans('m.rbs.website.admin.displaydocument_invalid_type', ['ucf']);
			}
			else
			{
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery($staticPage->getDocumentModel());
				$query->andPredicates($query->neq('id', $staticPage->getId()), $query->eq('displayDocument', $displayDocument));
				/** @var $duplicates StaticPage[]|\Change\Documents\DocumentCollection */

				$duplicates = $query->getDocuments();
				if ($duplicates->count()) {
					$website = ($staticPage->getSection() ? $staticPage->getSection()->getWebsite() : null);
					foreach ($duplicates as $duplicateStaticPage)
					{
						$duplicateWebsite = ($duplicateStaticPage->getSection() ? $duplicateStaticPage->getSection()->getWebsite() : null);
						if ($website === $duplicateWebsite)
						{
							$propertiesErrors['displayDocument'][] = $i18nManager->trans('m.rbs.website.admin.displaydocument_duplicate', ['ucf']);
							break;
						}
					}
				}
			}

			if (count($propertiesErrors))
			{
				$errors = $event->getParam('propertiesErrors');
				if (is_array($errors))
				{
					$event->setParam('propertiesErrors', array_merge($propertiesErrors, $errors));

				}
				else
				{
					$event->setParam('propertiesErrors', $propertiesErrors);
				}
			}
		}
	}

	public function onDefaultFullTextContent(Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof StaticPage)
		{
			$fullTextContent = array();
			$layout = $document->getContentLayout();
			foreach ($layout->getBlocks() as $block)
			{
				$params = $block->getParameters();
				if (isset($params['content']) && isset($params['contentType']))
				{
					$richText = new \Change\Documents\RichtextProperty($params['content']);
					$richText->setEditor($params['contentType']);
					$fullTextContent[] = $richText->getRawText();
				}
			}
			if (count($fullTextContent))
			{
				$event->setParam('fullTextContent', $fullTextContent);
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDocumentDisplayPage(Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof \Change\Presentation\Interfaces\Page)
		{
			$event->setParam('page', $document);
			$event->stopPropagation();
		}
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		$section = $this->getSection();
		return $section ? array($section) : array();
	}

	/**
	 * @param \Change\Documents\AbstractDocument $publicationSections
	 * @return $this
	 */
	public function setPublicationSections($publicationSections)
	{
		return $this;
	}

	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $staticPage StaticPage */
		$staticPage = $event->getDocument();
		$website = null;
		$section = $staticPage->getSection();
		if ($section instanceof Topic) {
			$website = $section->getWebsite();
		}elseif ($section instanceof Website) {
			$website = $section;
		}

		$restResult = $event->getParam('restResult');

		if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$documentLink = $restResult;

			$um = $documentLink->getUrlManager();
			$vc = new \Change\Http\Rest\ValueConverter($um, $event->getApplicationServices()->getDocumentManager());
			$documentLink->setProperty('website', $vc->toRestValue($website, \Change\Documents\Property::TYPE_DOCUMENT));

			$extraColumn = $event->getParam('extraColumn');
			if (in_array('functions', $extraColumn))
			{
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Website_SectionPageFunction');
				$query->andPredicates(
					$query->eq('page', $documentLink->getDocument()),
					$query->eq('section',
						$event->getApplicationServices()->getTreeManager()->getNodeByDocument($staticPage)->getParentId())
				);
				$functions = array();
				$funcDocs = $query->getDocuments();
				foreach ($funcDocs as $func)
				{
					/* @var $func \Rbs\Website\Documents\SectionPageFunction */
					$functions[] = $func->getFunctionCode();
				}
				$documentLink->setProperty('functions', $functions);
			}
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$documentResult = $restResult;
			$section = $staticPage->getSection();

			$website = null;
			if ($section instanceof Topic) {
				$website = $section->getWebsite();
			}elseif ($section instanceof Website) {
				$website = $section;
			}
			$um = $documentResult->getUrlManager();
			$vc = new \Change\Http\Rest\ValueConverter($um, $event->getApplicationServices()->getDocumentManager());
			$documentResult->setProperty('website', $vc->toRestValue($website, \Change\Documents\Property::TYPE_DOCUMENT));
		}
	}
}