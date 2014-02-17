<?php
namespace Change\Http\OAuth;

/**
 * @name \Change\Http\OAuth\OAuthManager
 */
class OAuthManager implements \Zend\EventManager\EventsCapableInterface
{
	const EVENT_MANAGER_IDENTIFIER = 'OAuthManager';

	use \Change\Events\EventsCapableTrait;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager(\Change\Transaction\TransactionManager $transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}


	/**
	 * @return string
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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Change/Events/OAuthManager');
	}

	/**
	 * @api
	 * @param string $application
	 * @return \Change\Http\OAuth\Consumer|null
	 */
	public function getConsumerByApplication($application)
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('consumer_key'), $fb->column('consumer_secret'), $fb->column('token_access_validity'),
			$fb->column('token_request_validity'), $fb->column('timestamp_max_offset'), $fb->column('application_id'))
			->from($qb->getSqlMapping()->getOAuthApplicationTable())
			->where($fb->eq($fb->column('application'), $fb->parameter('application')));
		$sq = $qb->query();
		$sq->bindParameter('application', $application);

		$result = $sq->getFirstResult($sq->getRowsConverter()
			->addStrCol('consumer_key', 'consumer_secret', 'token_access_validity',
				'token_request_validity')->addIntCol('timestamp_max_offset', 'application_id'));
		if ($result)
		{
			$consumer = new Consumer($result['consumer_key'], $result['consumer_secret']);
			$consumer->setTokenAccessValidity($result['token_access_validity']);
			$consumer->setTokenRequestValidity($result['token_request_validity']);
			$consumer->setTimestampMaxOffset($result['timestamp_max_offset']);
			$consumer->setApplicationId($result['application_id'])->setApplicationName($application);
			return $consumer;
		}
		return null;
	}

	/**
	 * @api
	 * @param integer $applicationId
	 * @return \Change\Http\OAuth\Consumer|null
	 */
	public function getConsumerByApplicationId($applicationId)
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('consumer_key'), $fb->column('consumer_secret'), $fb->column('token_access_validity'),
			$fb->column('token_request_validity'), $fb->column('timestamp_max_offset'), $fb->column('application'))
			->from($qb->getSqlMapping()->getOAuthApplicationTable())
			->where($fb->eq($fb->column('application_id'), $fb->parameter('application_id')));
		$sq = $qb->query();
		$sq->bindParameter('application_id', $applicationId);

		$result = $sq->getFirstResult($sq->getRowsConverter()
			->addStrCol('consumer_key', 'consumer_secret', 'token_access_validity',
			'token_request_validity', 'timestamp_max_offset', 'application')->addIntCol('timestamp_max_offset'));
		if ($result)
		{
			$consumer = new Consumer($result['consumer_key'], $result['consumer_secret']);
			$consumer->setTokenAccessValidity($result['token_access_validity']);
			$consumer->setTokenRequestValidity($result['token_request_validity']);
			$consumer->setTimestampMaxOffset($result['timestamp_max_offset']);
			$consumer->setApplicationId(intval($applicationId))->setApplicationName($result['application']);
			return $consumer;
		}
		return null;
	}

	/**
	 * @param string $consumerKey
	 * @return \Change\Http\OAuth\Consumer|null
	 */
	public function getConsumerByKey($consumerKey)
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('consumer_secret'), $fb->column('token_access_validity'), $fb->column('token_request_validity'),
			$fb->column('timestamp_max_offset'), $fb->column('application_id'), $fb->column('application'))
			->from($fb->table($qb->getSqlMapping()->getOAuthApplicationTable()))
			->where($fb->eq($fb->column('consumer_key'), $fb->parameter('consumer_key')));
		$qs = $qb->query();
		$qs->bindParameter('consumer_key', $consumerKey);

		$result = $qs->getFirstResult($qs->getRowsConverter()->addStrCol('consumer_secret', 'token_access_validity',
			'token_request_validity', 'application')->addIntCol('timestamp_max_offset', 'application_id'));
		if ($result)
		{
			$consumer = new Consumer($consumerKey, $result['consumer_secret']);
			$consumer->setTokenAccessValidity($result['token_access_validity']);
			$consumer->setTokenRequestValidity($result['token_request_validity']);
			$consumer->setTimestampMaxOffset($result['timestamp_max_offset']);
			$consumer->setApplicationId($result['application_id'])->setApplicationName($result['application']);
			return $consumer;
		}
		return null;
	}

	/**
	 * @param string $token
	 * @return OAuthDbEntry|null
	 */
	public function getRequestToken($token)
	{
		$dbProvider = $this->getDbProvider();

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('token_id'), $fb->column('token_secret'), $fb->column('realm'), $fb->column('creation_date'),
			$fb->column('validity_date'), $fb->column('callback'), $fb->column('verifier'), $fb->column('authorized'),
			$fb->column('accessor_id'), $fb->column('application_id'))
			->from($fb->table($qb->getSqlMapping()->getOAuthTable()))
			->where($fb->logicAnd(
				$fb->eq($fb->column('token'), $fb->parameter('token')),
				$fb->eq($fb->column('token_type'), $fb->string(OAuthDbEntry::TYPE_REQUEST))
			));
		$qs = $qb->query();
		$qs->bindParameter('token', $token);

		$storedOAuthInfo = $qs->getFirstResult($qs->getRowsConverter()->addStrCol('token_secret',
			'realm', 'creation_date', 'callback', 'verifier', 'accessor_id')
			->addIntCol('token_id', 'application_id')->addDtCol('validity_date')->addBoolCol('authorized'));
		if ($storedOAuthInfo)
		{
			$storedOAuthInfo['token'] = $token;
			$storedOAuthInfo['token_type'] = OAuthDbEntry::TYPE_REQUEST;
			$storedOAuth = new OAuthDbEntry();
			$storedOAuth->importFromArray($storedOAuthInfo);
			$storedOAuth->setConsumer($this->getConsumerByApplicationId($storedOAuthInfo['application_id']));
			return $storedOAuth;
		}
		return null;
	}

	/**
	 * @param string $token
	 * @param string $consumerKey
	 * @return OAuthDbEntry|null
	 */
	public function getStoredOAuth($token, $consumerKey)
	{
		$dbProvider = $this->getDbProvider();

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('application_id'), $fb->column('consumer_secret'))
			->from($fb->table($qb->getSqlMapping()->getOAuthApplicationTable()))
			->where($fb->logicAnd(
				$fb->eq($fb->column('consumer_key'), $fb->parameter('consumer_key')),
				$fb->eq($fb->column('active'), $fb->booleanParameter('active'))
			));
		$qs = $qb->query();
		$qs->bindParameter('consumer_key', $consumerKey);
		$qs->bindParameter('active', true);

		$applicationInfo = $qs->getFirstResult($qs->getRowsConverter()->addIntCol('application_id')
			->addStrCol('consumer_secret'));
		$applicationId = $applicationInfo['application_id'];

		if (null !== $applicationId)
		{
			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('token_id'), $fb->column('token_secret'), $fb->column('realm'), $fb->column('device'),
				$fb->column('token_type'), $fb->column('creation_date'),
				$fb->column('validity_date'), $fb->column('callback'), $fb->column('verifier'), $fb->column('authorized'),
				$fb->column('accessor_id'), $fb->column('application_id'))
				->from($fb->table($qb->getSqlMapping()->getOAuthTable()))
				->where($fb->logicAnd(
					$fb->eq($fb->column('token'), $fb->parameter('token')),
					$fb->eq($fb->column('application_id'), $fb->parameter('application_id')),
					$fb->gt($fb->column('validity_date'), $fb->dateTimeParameter('validity_date'))));
			$qs = $qb->query();
			$qs->bindParameter('token', $token);
			$qs->bindParameter('application_id', $applicationId);
			$now = new \DateTime();
			$qs->bindParameter('validity_date', $now);

			$storedOAuthInfo = $qs->getFirstResult($qs->getRowsConverter()->addStrCol('token_id', 'token_secret',
				'realm', 'device', 'token_type', 'creation_date', 'callback', 'verifier', 'accessor_id')
				->addIntCol('token_id', 'application_id')->addDtCol('validity_date')->addBoolCol('authorized'));
			if ($storedOAuthInfo)
			{
				$storedOAuthInfo['token'] = $token;
				$storedOAuth = new OAuthDbEntry();
				$storedOAuth->importFromArray($storedOAuthInfo);
				$storedOAuth->setConsumer($this->getConsumerByApplicationId($storedOAuthInfo['application_id']));
				return $storedOAuth;
			}
		}

		return null;
	}

	/**
	 * @param string $timestamp
	 * @param \Change\Http\OAuth\Consumer $consumer
	 * @throws \RuntimeException
	 */
	public function checkTimestamp($timestamp, $consumer)
	{
		$delay = abs(time() - $timestamp);
		$timestampMaxOffset = $consumer->getTimestampMaxOffset();
		if ($delay > $timestampMaxOffset)
		{
			throw new \RuntimeException('Invalid Timestamp: ' . $delay, 72005);
		}
	}

	/**
	 * @param OAuthDbEntry $storedOAuth
	 * @throws \Exception
	 */
	public function insertToken($storedOAuth)
	{
		$dbProvider = $this->getDbProvider();

		try
		{
			$this->getTransactionManager()->begin();

			if (null === $storedOAuth->getCreationDate())
			{
				$storedOAuth->setCreationDate(new \DateTime());
			}

			if (null === $storedOAuth->getAuthorized())
			{
				$storedOAuth->setAuthorized(false);
			}

			if (null === $storedOAuth->getCallback() && OAuthDbEntry::TYPE_REQUEST === $storedOAuth->getType())
			{
				$storedOAuth->setCallback('oob');
			}

			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();

			$sq = $qb->select($fb->column('application_id'))
				->from($fb->table($qb->getSqlMapping()->getOAuthApplicationTable()))
				->where($fb->eq('consumer_key', $fb->parameter('consumer_key')))
				->query();
			$sq->bindParameter('consumer_key', $storedOAuth->getConsumerKey());

			$applicationId = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('application_id'));

			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$iq = $qb->insert($qb->getSqlMapping()->getOAuthTable())
				->addColumns($fb->column('token'), $fb->column('token_secret'),
				$fb->column('application_id'), $fb->column('realm'), $fb->column('device'),
				$fb->column('token_type'), $fb->column('creation_date'), $fb->column('validity_date'),
				$fb->column('callback'), $fb->column('verifier'), $fb->column('authorized'), $fb->column('accessor_id'))
				->addValues($fb->parameter('token'), $fb->parameter('token_secret'),
					$fb->integerParameter('application_id'), $fb->parameter('realm'), $fb->parameter('device'),
					$fb->parameter('token_type'), $fb->dateTimeParameter('creation_date'),
					$fb->dateTimeParameter('validity_date'),
					$fb->parameter('callback'), $fb->parameter('verifier'), $fb->booleanParameter('authorized'),
					$fb->integerParameter('accessor_id'))
				->insertQuery();

			$iq->bindParameter('token', $storedOAuth->getToken());
			$iq->bindParameter('token_secret', $storedOAuth->getTokenSecret());
			$iq->bindParameter('application_id', $applicationId);
			$iq->bindParameter('realm', $storedOAuth->getRealm());
			$iq->bindParameter('device', $storedOAuth->getDevice());
			$iq->bindParameter('token_type', $storedOAuth->getType());
			$iq->bindParameter('creation_date', $storedOAuth->getCreationDate());
			$iq->bindParameter('validity_date', $storedOAuth->getValidityDate());
			$iq->bindParameter('callback', $storedOAuth->getCallback());
			$iq->bindParameter('verifier', $storedOAuth->getVerifier());
			$iq->bindParameter('authorized', $storedOAuth->getAuthorized());
			$iq->bindParameter('accessor_id', $storedOAuth->getAccessorId());
			$iq->execute();

			$storedOAuth->setId(intval($dbProvider->getLastInsertId('change_oauth')));

			$this->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			throw $this->getTransactionManager()->rollBack($e);
		}
	}

	/**
	 * @param OAuthDbEntry $storedOAuth
	 * @throws \Exception
	 */
	public function updateToken($storedOAuth)
	{
		$dbProvider = $this->getDbProvider();

		try
		{
			$this->getTransactionManager()->begin();

			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->update($fb->table($qb->getSqlMapping()->getOAuthTable()))
				->assign($fb->column('validity_date'), $fb->dateTimeParameter('validity_date'))
				->assign($fb->column('verifier'), $fb->parameter('verifier'))
				->assign($fb->column('authorized'), $fb->booleanParameter('authorized'))
				->assign($fb->column('accessor_id'), $fb->integerParameter('accessor_id'))
				->assign($fb->column('device'), $fb->parameter('device'))
				->where($fb->eq($fb->column('token_id'), $fb->integerParameter('id')));
			$uq = $qb->updateQuery();

			$uq->bindParameter('validity_date', $storedOAuth->getValidityDate());
			$uq->bindParameter('verifier', $storedOAuth->getVerifier());
			$uq->bindParameter('authorized', $storedOAuth->getAuthorized());
			$uq->bindParameter('accessor_id', $storedOAuth->getAccessorId());
			$uq->bindParameter('device', $storedOAuth->getDevice());
			$uq->bindParameter('id', $storedOAuth->getId());
			$uq->execute();

			$this->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			throw $this->getTransactionManager()->rollBack($e);
		}
	}

	/**
	 * @return string
	 */
	public function generateConsumerKey()
	{
		$dbProvider = $this->getDbProvider();

		$qb = $dbProvider->getNewQueryBuilder('generateConsumerKey');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('application_id'))->from($qb->getSqlMapping()->getOAuthApplicationTable());
			$qb->where($fb->eq($fb->column('consumer_key'), $fb->parameter('consumer_key')));
		}
		$qs = $qb->query();
		$rowConverter = $qs->getRowsConverter()->addIntCol('application_id');

		do
		{
			$consumerKey = \Change\Stdlib\String::random(64);
			$qs->bindParameter('consumer_key', $consumerKey);
			$applicationId = $qs->getFirstResult($rowConverter);
		}
		while ($applicationId !== null);

		return $consumerKey;
	}

	/**
	 * @return string
	 */
	public function generateConsumerSecret()
	{
		return \Change\Stdlib\String::random(64);
	}

	public function generateTokenKey()
	{
		$dbProvider = $this->getDbProvider();

		$qb = $dbProvider->getNewQueryBuilder('generateTokenKey');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('token_id'))->from($qb->getSqlMapping()->getOAuthTable());
			$qb->where($fb->eq($fb->column('token'), $fb->parameter('token')));
		}
		$qs = $qb->query();
		$rowConverter = $qs->getRowsConverter()->addIntCol('token_id');

		do
		{
			$tokenKey = \Change\Stdlib\String::random(64);
			$qs->bindParameter('token', $tokenKey);
			$tokenId = $qs->getFirstResult($rowConverter);
		}
		while ($tokenId !== null);

		return $tokenKey;
	}

	/**
	 * @return string
	 */
	public function generateTokenSecret()
	{
		return \Change\Stdlib\String::random(64);
	}

	/**
	 * @param $event
	 * @param $data
	 * @return mixed
	 * @throws \RuntimeException
	 */
	public function getLoginFormHtml($event, $data)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['httpEvent' => $event, 'data' => $data]);
		$event = new \Change\Events\Event('loginFormHtml', $this, $args);
		$this->getEventManager()->trigger($event);
		$html = $event->getParam('html');
		if (\Change\Stdlib\String::isEmpty($html))
		{
			throw new \RuntimeException('Can not display OAuth login form');
		}
		return $html;
	}
}