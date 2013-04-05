<?php
namespace Change\Http\Web;

use Change\Http\ActionResolver;
use Change\Http\Event;
use Change\Http\Web\Actions\FindDisplayPage;
use Change\Http\Web\Actions\GeneratePathRule;
use Change\Http\Web\Actions\RedirectPathRule;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Resolver
 */
class Resolver extends ActionResolver
{
	/**
	 * @var boolean
	 */
	protected $singleWebsite = true;

	/**
	 * @param Event $event
	 * @return void
	 */
	public function resolve(Event $event)
	{
		$cfg = $event->getApplicationServices()->getApplication()->getConfiguration();
		$this->singleWebsite = $cfg->getEntry('Change/Http/Web/SingleWebsite', true);

		$this->populateWebsite($event);
		$pathRule = $this->findRule($event);
		if ($pathRule)
		{
			$this->populateEventByPathRule($event, $pathRule);
		}
	}

	/**
	 * @param Event $event
	 * @return boolean
	 */
	protected function populateWebsite($event)
	{
		$data = $this->getWebsiteDatas($event);
		if (count($data))
		{
			$request = $event->getRequest();
			$path = $request->getPath();
			$hostName = $request->getUri()->getHost();
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$mm = $event->getDocumentServices()->getModelManager();
			$dm = $event->getDocumentServices()->getDocumentManager();
			foreach ($data as $row)
			{
				if ($hostName === $row['hostName'])
				{
					$websitePathPart = $row['pathPart'];
					if ($this->isBasePath($path, $row['pathPart']))
					{
						$model = $mm->getModelByName($row['model']);
						$website = $dm->getDocumentInstance(intval($row['id']), $model);
						if ($website instanceof \Change\Website\Documents\Website)
						{
							$LCID = $row['LCID'];
							$i18nManager->setLCID($LCID);
							$request->setLCID($LCID);
							$event->setParam('website', $website);
							$event->getUrlManager()->setBasePath($websitePathPart);
							return true;
						}
					}
				}
			}

			if ($this->singleWebsite)
			{
				$row = 	$data[0];
				$model = $mm->getModelByName($row['model']);
				$website = $dm->getDocumentInstance(intval($row['id']), $model);
				if ($website instanceof \Change\Website\Documents\Website)
				{
					$websitePathPart = $row['pathPart'];
					$LCID = $row['LCID'];
					$i18nManager = $event->getApplicationServices()->getI18nManager();
					$i18nManager->setLCID($LCID);
					$request->setLCID($LCID);
					$event->setParam('website', $website);
					$event->getUrlManager()->setBasePath($websitePathPart);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param Event $event
	 * @return array
	 */
	protected function getWebsiteDatas($event)
	{
		$websiteModel =  $event->getDocumentServices()->getModelManager()->getModelByName('Change_Website_Website');
		if ($websiteModel)
		{
			$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->getDocumentColumn('id'), 'id'),
				$fb->alias($fb->getDocumentColumn('model'), 'model'),
				$fb->alias($fb->getDocumentColumn('LCID'), 'LCID'),
				$fb->alias($fb->getDocumentColumn('hostName'), 'hostName'),
				$fb->alias($fb->getDocumentColumn('pathPart'), 'pathPart'));
			$qb->from($fb->getDocumentI18nTable($websiteModel->getRootName()));
			$qb->innerJoin($fb->getDocumentTable($websiteModel->getRootName()), $fb->getDocumentColumn('id'));
			$qb->andWhere($fb->isNotNull($fb->getDocumentColumn('hostName')));
			return $qb->query()->getResults();
		}
		return array();
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
		if ($website instanceof \Change\Website\Documents\Website)
		{
			$path = $event->getRequest()->getPath();
			if ($this->isBasePath($path, $website->getPathPart()))
			{
				$relativePath = $this->getRelativePath($path, $website->getPathPart());
			}
			else
			{
				//Invalid website path part
				$location = $event->getUrlManager()->getByPathInfo($this->getRelativePath($path, null))->normalize()->toString();
				$pathRule = new PathRule($website->getId(), $website->getLCID(), $path);
				$pathRule->setConfig('Location', $location);
				$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_301);
				$pathRule->setRuleId(0);
				return $pathRule;
			}

			$pathRule = new PathRule($website->getId(), $website->getLCID(), $relativePath);
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