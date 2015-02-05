<?php
namespace Rbs\Social;

/**
 * @name \Rbs\Social\SocialManager
 */
class SocialManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'SocialManager';

	const VARIABLE_REGEXP = '/\{([a-z][A-Za-z0-9.]*)\}/';

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Social/Events/SocialManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getSocialCount', array($this, 'onDefaultGetSocialCount'), 5);
		$eventManager->attach('addSocialCount', array($this, 'onDefaultAddSocialCount'), 5);
	}

	/**
	 * @param integer $websiteId
	 * @param integer $documentId
	 * @return array
	 */
	public function getSocialCount($websiteId, $documentId)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'websiteId' => $websiteId,
			'documentId' => $documentId
		));
		$eventManager->trigger('getSocialCount', $this, $args);
		return isset($args['data']) ? $args['data'] : [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetSocialCount($event)
	{
		$websiteId = $event->getParam('websiteId');
		$documentId = $event->getParam('documentId');
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$website = $documentManager->getDocumentInstance($websiteId);
		$document = $event->getParam($documentId);

		if ($document instanceof \Change\Documents\AbstractDocument && $website instanceof \Change\Presentation\Interfaces\Website)
		{
			$socialData = $this->getSocialData($websiteId, $documentId, $event->getApplicationServices()->getDbProvider());
			$socialData['data'] = json_decode($socialData['data'], true);
			$event->setParam('data', $socialData);
		}
	}

	/**
	 * @param integer $websiteId
	 * @param integer $documentId
	 * @param string $socialType
	 */
	public function addSocialCount($websiteId, $documentId, $socialType)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'websiteId' => $websiteId,
			'documentId' => $documentId,
			'socialType' => $socialType
		));
		$eventManager->trigger('addSocialCount', $this, $args);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultAddSocialCount($event)
	{
		$websiteId = $event->getParam('websiteId');
		$documentId = $event->getParam('documentId');
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$website = $documentManager->getDocumentInstance($websiteId);
		$document = $documentManager->getDocumentInstance($documentId);
		$socialType = $event->getParam('socialType');

		if ($website instanceof \Change\Presentation\Interfaces\Website && $document instanceof \Change\Documents\AbstractDocument && is_string($socialType))
		{
			$dbProvider = $event->getApplicationServices()->getDbProvider();

			$socialData = $this->getSocialData($websiteId, $documentId, $dbProvider);

			if ($socialData === null)
			{
				//insert
				$this->insertSocialData($websiteId, $documentId, $this->getNewSocialCounts($socialType), $event);
			}
			else
			{
				$socialCounts = json_decode($socialData['data'], true);
				$newSocialCounts = $this->getNewSocialCounts($socialType, $socialCounts);
				$count = array_sum($newSocialCounts);
				//update
				$this->updateSocialData($websiteId, $documentId, $newSocialCounts, $count, $event);
			}
		}
	}

	/**
	 * @param integer $websiteId
	 * @param integer $documentId
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return array
	 */
	protected function getSocialData($websiteId, $documentId, $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('count'), $fb->column('last_date'), $fb->column('data'));
		$qb->from($fb->table('rbs_social_count'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
			$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId'))
		));
		$sq = $qb->query();

		$sq->bindParameter('websiteId', $websiteId);
		$sq->bindParameter('documentId', $documentId);

		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('count')->addDtCol('last_date')->addTxtCol('data'));
	}

	/**
	 * @param integer $documentId
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return array
	 */
	public function getFormattedSocialData($documentId, $dbProvider, $documentManager)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('website_id'), $fb->column('count'), $fb->column('last_date'), $fb->column('data'));
		$qb->from($fb->table('rbs_social_count'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId'))
		));
		$sq = $qb->query();

		$sq->bindParameter('documentId', $documentId);

		$results = $sq->getResults($sq->getRowsConverter()
			->addIntCol('website_id')->addIntCol('count')->addDtCol('last_date')->addTxtCol('data')
		);

		return $this->formatSocialData($results, $documentManager);
	}

	/**
	 * @param array $socialData
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return array
	 */
	protected function formatSocialData($socialData, $documentManager)
	{
		$formattedSocialData = [];

		foreach ($socialData as $social)
		{
			/** @var \Rbs\Website\Documents\Website $website */
			$website = $documentManager->getDocumentInstance($social['website_id'], 'Rbs_Website_Website');
			$totalCount = $social['count'];
			$data = json_decode($social['data'], true);
			$formattedData = [];
			foreach ($data as $socialType => $count)
			{
				$percent = ($count / $totalCount) * 100;
				$formattedData[] = ['name' => $this->getFullSocialType($socialType), 'count' => $count, 'percent' => $percent];
			}
			$formattedSocialData[] = [
				'website' => $website ? $website->getLabel() : '-',
				'count' => $totalCount,
				'lastDate' => $social['last_date'],
				'data' => $formattedData
			];
		}

		return $formattedSocialData;
	}

	/**
	 * @param integer $websiteId
	 * @param integer $documentId
	 * @param array $socialData
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 * @return array
	 */
	protected function insertSocialData($websiteId, $documentId, $socialData, $event)
	{
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->insert($fb->table('rbs_social_count'));
			$qb->addColumns($fb->column('website_id'), $fb->column('document_id'), $fb->column('count'), $fb->column('last_date'), $fb->column('data'));
			$qb->addValues($fb->parameter('websiteId'), $fb->parameter('documentId'), $fb->parameter('count'), $fb->dateTimeParameter('lastDate'), $fb->parameter('data'));
			$iq = $qb->insertQuery();

			$iq->bindParameter('websiteId', $websiteId);
			$iq->bindParameter('documentId', $documentId);
			$iq->bindParameter('count', 1);
			$iq->bindParameter('data', json_encode($socialData));
			$iq->bindParameter('lastDate', new \DateTime());
			$iq->execute();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param integer $websiteId
	 * @param integer $documentId
	 * @param array $socialData
	 * @param integer $count
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 * @return array
	 */
	protected function updateSocialData($websiteId, $documentId, $socialData, $count, $event)
	{
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->update($fb->table('rbs_social_count'));
			$qb->assign($fb->column('count'), $fb->integerParameter('count'))
				->assign($fb->column('data'), $fb->parameter('data'))
				->assign($fb->column('last_date'), $fb->dateTimeParameter('lastDate'));
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('website_id'), $fb->integerParameter('websiteId')),
				$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId'))
			));
			$uq = $qb->updateQuery();

			$uq->bindParameter('websiteId', $websiteId);
			$uq->bindParameter('documentId', $documentId);
			$uq->bindParameter('count', $count);
			$uq->bindParameter('data', json_encode($socialData));
			$uq->bindParameter('lastDate', new \DateTime());
			$uq->execute();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param string $socialType
	 * @param array $socialCounts
	 * @return array
	 */
	protected function getNewSocialCounts($socialType, $socialCounts = ['facebook' => 0, 'twitter' => 0, 'gplus' => 0, 'pinterest' => 0, 'instagram' => 0])
	{
		switch ($socialType)
		{
			case 'facebookLike':
				$socialCounts['facebook']++;
				break;
			case 'facebookUnlike':
				$socialCounts['facebook']--;
				break;
			case 'twitterTweet':
				$socialCounts['twitter']++;
				break;
			case 'gplusOn':
				$socialCounts['gplus']++;
				break;
			case 'gplusOff':
				$socialCounts['gplus']--;
				break;
			case 'pinterestPinIt':
				$socialCounts['pinterest']++;
				break;
		}

		return $socialCounts;
	}

	/**
	 * @param string $shortSocialType
	 * @return string
	 */
	protected function getFullSocialType($shortSocialType)
	{
		switch ($shortSocialType)
		{
			case 'facebook':
				return 'Facebook';
			case 'twitter':
				return 'Twitter';
			case 'gplus':
				return 'Google +';
			case 'pinterest':
				return 'Pinterest';
			case 'instagram':
				return 'Instagram';
			default:
				return 'error: unknown short social type: ' . $shortSocialType;
		}
	}
}