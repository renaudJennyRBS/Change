<?php
namespace Change\Http\Web\Actions;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Http\Web\PathRule;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\GeneratePathRule
 */
class GeneratePathRule
{
	/**
	 * Use Required Event Params: pathRule
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		/* @var $pathRule PathRule */
		$pathRule = $event->getParam('pathRule');
		if (!($pathRule instanceof PathRule))
		{
			throw new \RuntimeException('Invalid Parameter: pathRule', 71000);
		}
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($pathRule->getDocumentId());
		if ($document)
		{
			$tmpPathRule = clone($pathRule);
			$tmpPathRule->setHttpStatus(HttpResponse::STATUS_CODE_200);
			$eventManager = $document->getEventManager();
			$eventManager->attach(DocumentEvent::EVENT_PATH_RULE, array($this, "onGeneratePathRule"), 5);
			$e = new DocumentEvent(DocumentEvent::EVENT_PATH_RULE, $document, array('inputPathRule' => $tmpPathRule));
			$result = $eventManager->trigger($e, function ($return)
			{
				return ($return instanceof PathRule);
			});

			$finalPathRule = ($result->stopped()) ? $result->last() : $e->getParam('pathRule');

			if ($finalPathRule instanceof PathRule)
			{
				$this->insertPathRule($event->getApplicationServices(), $finalPathRule);
			}
			else
			{
				$finalPathRule = $tmpPathRule;
				$callBack = array($document, 'getPathSuffix');
				$suffix = (is_callable($callBack)) ? call_user_func($callBack) : '.html';
				$sectionId = $pathRule->getSectionId();
				$finalPathRule->setPath($document->getDocumentModelName() . ($sectionId ? ','. $sectionId : '')  . ',' . $document->getId() . $suffix);
			}

			if ($pathRule->getPath() !== $finalPathRule->getPath())
			{
				$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_301);
				$pathRule->setConfig('Location', $finalPathRule->getPath());
				$action = new RedirectPathRule();
				$action->execute($event);
			}
			else
			{
				$event->setParam('pathRule', $finalPathRule);
				$action = new FindDisplayPage();
				$action->execute($event);
			}
		}
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onGeneratePathRule($event)
	{
		if ($event instanceof DocumentEvent)
		{
			$inputPathRule = $event->getParam('inputPathRule');
			if ($inputPathRule instanceof PathRule)
			{
				//TODO update $inputPathRule->setPath()
				//TODO Store final PathRule in event param 'pathRule'
				//$event->setParam('pathRule', null);
			}
		}
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param PathRule $pathRule
	 */
	protected function insertPathRule($applicationServices, $pathRule)
	{
		$sb = $applicationServices->getDbProvider()->getNewStatementBuilder();
		$fb = $sb->getFragmentBuilder();
		$sb->insert($sb->getSqlMapping()->getPathRuleTable());
		$sb->addColumns($fb->column('website_id'),
			$fb->column('lcid'),
			$fb->column('path'),
			$fb->column('document_id'),
			$fb->column('section_id'),
			$fb->column('http_status'),
			$fb->column('config_datas')
		);
		$sb->addValues($fb->integerParameter('websiteId', $sb),
			$fb->parameter('LCID', $sb),
			$fb->parameter('path', $sb),
			$fb->integerParameter('documentId', $sb),
			$fb->integerParameter('sectionId', $sb),
			$fb->integerParameter('httpStatus', $sb),
			$fb->parameter('configDatas', $sb)
		);

		$iq = $sb->insertQuery();
		$iq->bindParameter('websiteId', $pathRule->getWebsiteId());
		$iq->bindParameter('LCID', $pathRule->getLCID());
		$iq->bindParameter('path', $pathRule->getPath());
		$iq->bindParameter('documentId', $pathRule->getDocumentId());
		$iq->bindParameter('sectionId', $pathRule->getSectionId());
		$iq->bindParameter('httpStatus', $pathRule->getHttpStatus());
		$iq->bindParameter('configDatas', count($pathRule->getConfigDatas()) ? json_encode($pathRule->getConfigDatas()) : null );
		$iq->execute();

		$pathRule->setRuleId($iq->getDbProvider()->getLastInsertId($sb->getSqlMapping()->getPathRuleTable()));
	}
}
