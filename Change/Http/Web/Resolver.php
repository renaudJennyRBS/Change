<?php
namespace Change\Http\Web;

use Change\Http\ActionResolver;
use Change\Http\Event;
use Change\Http\Web\Actions\FindDisplayPage;
use Change\Http\Web\Actions\GeneratePathRule;
use Change\Http\Web\Actions\RedirectPathRule;
use Change\Presentation\Interfaces\Website;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Resolver
 */
class Resolver extends ActionResolver
{

	/**
	 * @param Event $event
	 * @return void
	 */
	public function resolve(Event $event)
	{
		//TODO use event
		if (class_exists('\Change\Website\WebsiteResolver'))
		{
			$websiteResolver = new \Change\Website\WebsiteResolver();
			$websiteResolver->resolve($event);
		}

		$pathRule = $this->findRule($event);
		if ($pathRule)
		{
			$this->populateEventByPathRule($event, $pathRule);
		}
	}

	/**
	 * @param string $path
	 * @param string $websitePathPart
	 * @return boolean
	 */
	protected function isBasePath($path, $websitePathPart)
	{
		if ($websitePathPart)
		{
			if ($path)
			{
				if ($path[0] == '/') {$path = substr($path, 1);}
				if ($websitePathPart === $path || $websitePathPart .'/' === $path || strpos($path, $websitePathPart . '/') === 0)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * @param string $path
	 * @param string $websitePathPart
	 * @return string
	 */
	protected function getRelativePath($path, $websitePathPart)
	{
		if ($path && $path[0] == '/')
		{
			$path = substr($path, 1);
		}

		if ($websitePathPart)
		{
			$path = substr($path, strlen($websitePathPart));
			if ($path && $path[0] == '/')
			{
				$path = substr($path, 1);
			}
		}
		return $path;
	}

	/**
	 * @param Event $event
	 * @param PathRule $pathRule
	 */
	protected function populateEventByPathRule($event, $pathRule)
	{
		$event->setParam('pathRule', $pathRule);
		if ($pathRule->getHttpStatus() === HttpResponse::STATUS_CODE_200)
		{
			$action = function($event) {
				$action = new FindDisplayPage();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif ($pathRule->getRuleId() === null)
		{
			$action = function($event) {
				$action = new GeneratePathRule();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		else
		{
			$action = function($event) {
				$action = new RedirectPathRule();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
	}

	/**
	 * @param Event $event
	 * @return PathRule|null
	 */
	protected function findRule($event)
	{
		$website = $event->getParam('website');
		if ($website instanceof Website)
		{

			$path = $event->getRequest()->getPath();
			if ($this->isBasePath($path, $website->getRelativePath()))
			{
				$relativePath = $this->getRelativePath($path, $website->getRelativePath());
			}
			else
			{
				//Invalid website path part
				$location = $event->getUrlManager()->getByPathInfo($this->getRelativePath($path, null), $event->getRequest()->getQuery()->toArray())->normalize()->toString();
				$pathRule = new PathRule($website, $path);
				$pathRule->setConfig('Location', $location);
				$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_301);
				$pathRule->setRuleId(0);
				return $pathRule;
			}

			$pathRule = new PathRule($website, $relativePath);
			if (!$relativePath)
			{
				//Home
				$pathRule->setDocumentId($website->getId());
				$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_200);
				return $pathRule;
			}

			if ($this->findDbRule($event->getApplicationServices()->getDbProvider(), $pathRule))
			{
				return $pathRule;
			}
			if ($this->findDefaultRule($pathRule))
			{
				$this->validateDbRule($event->getApplicationServices()->getDbProvider(), $pathRule);
				return $pathRule;
			}
		}
		return null;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param PathRule $pathRule
	 * @return boolean
	 */
	protected function findDbRule($dbProvider, $pathRule)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->select($fb->alias($fb->column('rule_id'), 'ruleId'),
			$fb->alias($fb->column('document_id'), 'documentId'),
			$fb->alias($fb->column('section_id'), 'sectionId'),
			$fb->alias($fb->column('http_status'), 'httpStatus'),
			$fb->alias($fb->column('config_datas'), 'configDatas'));

		$qb->from($qb->getSqlMapping()->getPathRuleTable());

		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId', $qb)),
			$fb->eq($fb->column('lcid'), $fb->parameter('LCID', $qb)),
			$fb->eq($fb->column('path'), $fb->parameter('path', $qb))
		));

		$sq = $qb->query();
		$sq->bindParameter('websiteId', $pathRule->getWebsiteId());
		$sq->bindParameter('LCID', $pathRule->getLCID());
		$sq->bindParameter('path', $pathRule->getPath());
		$row = $sq->getFirstResult();
		if ($row)
		{
			$pathRule->setRuleId(intval($row['ruleId']));
			if (isset($row['documentId']))
			{
				$pathRule->setDocumentId(intval($row['documentId']));
			}
			if (isset($row['sectionId']))
			{
				$pathRule->setSectionId(intval($row['sectionId']));
			}
			$pathRule->setHttpStatus(intval($row['httpStatus']));
			$pathRule->setConfigDatas($row['configDatas']);
			return true;
		}
		return null;
	}

	/**
	 * @param PathRule $pathRule
	 * @return boolean
	 */
	protected function findDefaultRule($pathRule)
	{
		if (preg_match('/(?:,(\d{4,10}))?,(\d{4,10})(\.html|\/)$/', $pathRule->getPath(), $matches))
		{
			$pathRule->setDocumentId(intval($matches[2]));
			if ($matches[1] !== '')
			{
				$pathRule->setSectionId(intval($matches[1]));
			}
			$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_301);
			return true;
		}
		return false;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param PathRule $pathRule
	 */
	protected function validateDbRule($dbProvider, $pathRule)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->select($fb->alias($fb->column('rule_id'), 'ruleId'),
			$fb->alias($fb->column('path'), 'path'),
			$fb->alias($fb->column('section_id'), 'sectionId'),
			$fb->alias($fb->column('config_datas'), 'configDatas'));

		$qb->from($qb->getSqlMapping()->getPathRuleTable());

		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId', $qb)),
			$fb->eq($fb->column('lcid'), $fb->parameter('LCID', $qb)),
			$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId', $qb)),
			$fb->eq($fb->column('http_status'), $fb->integerParameter('httpStatus', $qb))
		));

		$sq = $qb->query();
		$sq->bindParameter('websiteId', $pathRule->getWebsiteId());
		$sq->bindParameter('LCID', $pathRule->getLCID());
		$sq->bindParameter('documentId', $pathRule->getDocumentId());
		$sq->bindParameter('httpStatus', HttpResponse::STATUS_CODE_200);

		$rows = $sq->getResults();
		if (count($rows))
		{
			foreach ($sq->getResults() as $row)
			{
				$sectionId = isset($row['sectionId']) ? intval($row['sectionId']) : null;
				if ($pathRule->getSectionId() === $sectionId)
				{
					$pathRule->setRuleId(intval($row['ruleId']));
					$pathRule->setSectionId($sectionId);
					$pathRule->setConfigDatas($row['configDatas']);
					$pathRule->setConfig('Location', $row['path']);
					break;
				}

				//Invalid pathRule sectionId
				if ($pathRule->getRuleId() === null)
				{
					$row = $rows[0];
					$pathRule->setRuleId(intval($row['ruleId']));
					$pathRule->setSectionId(isset($row['sectionId']) ? intval($row['sectionId']) : null);
					$pathRule->setConfigDatas($row['configDatas']);
					$pathRule->setConfig('Location', $row['path']);
				}
			}
		}
	}
}