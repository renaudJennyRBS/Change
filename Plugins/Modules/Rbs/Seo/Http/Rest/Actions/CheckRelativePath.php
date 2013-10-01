<?php
namespace Rbs\Seo\Http\Rest\Actions;

/**
 * @name \Rbs\Seo\Http\Rest\Actions\CheckRelativePath
 */
class CheckRelativePath
{
	public function execute(\Change\Http\Event $event)
	{
		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('CheckRelativePath.execute');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'),
				$fb->column('section_id'), $fb->column('document_id'), $fb->column('http_status'), $fb->column('query')
			);
			$qb->from($qb->getSqlMapping()->getPathRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
				$fb->eq($fb->column('lcid'), $fb->parameter('LCID')),
				$fb->eq($fb->column('relative_path'), $fb->parameter('relativePath'))
			));
		}

		$sq = $qb->query();
		$sq->bindParameter('websiteId', $event->getParam('websiteId'))
			->bindParameter('LCID', $event->getParam('LCID'))
			->bindParameter('relativePath', $event->getParam('relativePath'));

		$pathRule = null;
		$row = $sq->getFirstResult();
		if ($row)
		{
			$pathRule = new \Change\Http\Web\PathRule();
			$pathRule->setRuleId(intval($row['rule_id']))
				->setRelativePath($row['relative_path'])
				->setQuery($row['query'])
				->setWebsiteId($event->getParam('websiteId'))
				->setLCID($event->getParam('LCID'))
				->setDocumentId($row['document_id'])
				->setSectionId($event->getParam('relativePath'))
				->setHttpStatus($row['query']);
		}
		$result = new \Rbs\Seo\Http\Rest\Result\PathRuleResult($pathRule);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}