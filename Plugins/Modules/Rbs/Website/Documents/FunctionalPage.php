<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Website\Documents\FunctionalPage
 */
class FunctionalPage extends \Compilation\Rbs\Website\Documents\FunctionalPage
{
	/**
	 * @var \Rbs\Website\Documents\Section
	 */
	protected $section;

	/**
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getSection()
	{
		return $this->section;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return $this
	 */
	public function setSection($section)
	{
		$this->section = $section;
		return $this;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach([Event::EVENT_CREATE, Event::EVENT_CREATE_LOCALIZED, Event::EVENT_UPDATE],
			array($this, 'onInitSupportedFunctions'), 5);

		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 10);
	}

	/**
	 * @param Event $event
	 */
	public function onDocumentDisplayPage(Event $event)
	{
		$functionalPage = $event->getDocument();
		if ($functionalPage instanceof FunctionalPage)
		{
			$pathRule = $event->getParam("pathRule");
			if ($pathRule instanceof \Change\Http\Web\PathRule)
			{
				if ($pathRule->getWebsiteId() ==  $functionalPage->getWebsiteId())
				{
					$functionalPage->setSection($functionalPage->getWebsite());
					if ($pathRule->getSectionId())
					{
						$section = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($pathRule->getSectionId());
						if ($section instanceof Section)
						{
							$functionalPage->setSection($section);
						}
					}
					$event->setParam('page', $functionalPage);
					$event->stopPropagation();
				}
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onInitSupportedFunctions(Event $event)
	{
		$page = $event->getDocument();
		if ($page instanceof FunctionalPage)
		{
			if (!$page->isPropertyModified('supportedFunctionsCode') && $page->isPropertyModified('editableContent'))
			{
				$blocksName = [];
				foreach ($page->getContentLayout()->getBlocks() as $block)
				{
					$blocksName[] = $block->getName();
				}
				if (count($blocksName))
				{
					$supportedFunctions = [];
					$functions = $event->getApplicationServices()->getPageManager()->getFunctions();
					foreach ($blocksName as $blockName)
					{
						foreach ($functions as $function)
						{
							if (isset($function['block']))
							{
								if (is_array($function['block']) && in_array($blockName, $function['block']))
								{
									$supportedFunctions[$function['code']] = true;
								}
								elseif (is_string($function['block']) && $function['block'] == $blockName)
								{
									$supportedFunctions[$function['code']] = true;
								}
							}
						}
					}
					$sf = $page->getSupportedFunctionsCode();
					if (is_array($sf))
					{
						$sf[$page->getCurrentLCID()] = array_keys($supportedFunctions);
					}
					else
					{
						$sf = [$page->getCurrentLCID() => array_keys($supportedFunctions)];
					}
					$page->setSupportedFunctionsCode($sf);
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function getAllSupportedFunctionsCode()
	{
		$allSupportedFunctionsCode = [];
		$array = $this->getSupportedFunctionsCode();
		if (is_array($array))
		{
			foreach ($array as $data)
			{
				if (is_array($data))
				{
					$allSupportedFunctionsCode = array_merge($allSupportedFunctionsCode, $data);
				}
				elseif (is_string($data))
				{
					$allSupportedFunctionsCode[] = $data;
				}
			}
			$allSupportedFunctionsCode = array_values(array_unique($allSupportedFunctionsCode));
		}
		return $allSupportedFunctionsCode;
	}

	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$documentLink = $restResult;
			$extraColumn = $event->getParam('extraColumn');
			if (in_array('allSupportedFunctionsCode', $extraColumn))
			{
				/** @var $document FunctionalPage */
				$document = $documentLink->getDocument();
				$documentLink->setProperty('allSupportedFunctionsCode', $document->getAllSupportedFunctionsCode());
			}
		}
	}
}
