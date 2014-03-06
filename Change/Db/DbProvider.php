<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db;

use Change\Db\Query\AbstractQuery;
use Change\Db\Query\Builder;
use Change\Db\Query\StatementBuilder;
use Change\Events\EventsCapableTrait;
use Change\Logging\Logging;

/**
 * @name \Change\Db\DbProvider
 * @api
 */
abstract class DbProvider
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_SQL_FRAGMENT_STRING = 'SQLFragmentString';

	const EVENT_SQL_FRAGMENT = 'SQLFragment';

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var \ArrayObject
	 */
	protected $connectionInfos;

	/**
	 * @var array
	 */
	protected $timers;


	/**
	 * @var \Change\Db\SqlMapping
	 */
	protected $sqlMapping;

	/**
	 * @var AbstractQuery
	 */
	protected $builderQueries;

	/**
	 * @var AbstractQuery
	 */
	protected $statementBuilderQueries;

	/**
	 * @var string[]
	 */
	protected $listenerAggregateClassNames;

	/**
	 * @var boolean
	 */
	protected $readOnly = false;

	/**
	 * @var boolean
	 */
	protected $checkTransactionBeforeWriting = true;

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return array('Db');
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		$classNames = array();
		foreach ($this->getEventManagerIdentifier() as $identifier)
		{
			$entry = $this->getApplication()->getConfiguredListenerClassNames('Change/Events/' . str_replace('.', '/', $identifier));
			if (is_array($entry))
			{
				foreach ($entry as $className)
				{
					if (is_string($className))
					{
						$classNames[] = $className;
					}
				}
			}
		}
		return array_unique($classNames);
	}

	/**
	 * @return string
	 */
	public abstract function getType();


	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;

		if ($this->connectionInfos === null)
		{
			$section = $application->getConfiguration()->getEntry('Change/Db/use', 'default');
			$connectionInfos = $application->getConfiguration()->getEntry('Change/Db/' . $section, array());
			if (is_array($connectionInfos))
			{
				$this->setConnectionInfos(new \ArrayObject($connectionInfos));
			}
			else
			{
				$this->setConnectionInfos(new \ArrayObject());
			}
		}
	}

	public function __construct()
	{
		$this->timers = array('init' => microtime(true),
			'select' => 0, 'exec' => 0, 'longTransaction' => 0.2);
	}

	/**
	 * @param boolean $readOnly
	 * @return $this
	 */
	public function setReadOnly($readOnly)
	{
		$this->readOnly = $readOnly;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getReadOnly()
	{
		return $this->readOnly;
	}

	/**
	 * @param boolean $checkTransactionBeforeWriting
	 * @return $this
	 */
	public function setCheckTransactionBeforeWriting($checkTransactionBeforeWriting)
	{
		$this->checkTransactionBeforeWriting = $checkTransactionBeforeWriting;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getCheckTransactionBeforeWriting()
	{
		return $this->checkTransactionBeforeWriting;
	}

	/**
	 * @return \ArrayObject
	 */
	public function getConnectionInfos()
	{
		return $this->connectionInfos;
	}

	/**
	 * @param \ArrayObject $connectionInfos
	 */
	public function setConnectionInfos($connectionInfos)
	{
		$this->connectionInfos = $connectionInfos;
		if (isset($connectionInfos['longTransaction']))
		{
			$this->timers['longTransaction'] = floatval($connectionInfos['longTransaction']);
		}
	}

	/**
	 * @param AbstractQuery $query
	 */
	public function addBuilderQuery(AbstractQuery $query)
	{
		if ($query->getCachedKey() !== null)
		{
			$this->builderQueries[$query->getCachedKey()] = $query;
		}
	}

	/**
	 * @param null $cacheKey
	 * @return Builder
	 */
	public function getNewQueryBuilder($cacheKey = null)
	{
		$query = ($cacheKey !== null && isset($this->builderQueries[$cacheKey])) ? $this->builderQueries[$cacheKey] : null;
		return new Builder($this, $cacheKey, $query);
	}

	/**
	 * @param AbstractQuery $query
	 */
	public function addStatementBuilderQuery(AbstractQuery $query)
	{
		if ($query->getCachedKey() !== null)
		{
			$this->statementBuilderQueries[$query->getCachedKey()] = $query;
		}
	}

	/**
	 * @param string $cacheKey
	 * @throws \RuntimeException
	 * @return StatementBuilder
	 */
	public function getNewStatementBuilder($cacheKey = null)
	{
		$query = ($cacheKey !== null
			&& isset($this->statementBuilderQueries[$cacheKey])) ? $this->statementBuilderQueries[$cacheKey] : null;
		return new StatementBuilder($this, $cacheKey, $query);
	}

	public function __destruct()
	{
		unset($this->builderQueries);
		unset($this->statementBuilderQueries);
	}

	/**
	 * @return void
	 */
	public abstract function closeConnection();

	/**
	 * @return \Change\Db\InterfaceSchemaManager
	 */
	public abstract function getSchemaManager();

	/**
	 * @return \Change\Db\SqlMapping
	 */
	public function getSqlMapping()
	{
		if ($this->sqlMapping === null)
		{
			$this->sqlMapping = new SqlMapping();
		}
		return $this->sqlMapping;
	}

	/**
	 * @return Logging
	 */
	public function getLogging()
	{
		return $this->getApplication()->getLogging();
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return void
	 */
	public abstract function beginTransaction($event = null);

	/**
	 * @param \Change\Events\Event $event
	 * @return void
	 */
	public abstract function commit($event);

	/**
	 * @param \Change\Events\Event $event
	 * @return void
	 */
	public abstract function rollBack($event);

	/**
	 * @param string $tableName
	 * @return integer
	 */
	public abstract function getLastInsertId($tableName);

	/**
	 * @api
	 * @param Query\InterfaceSQLFragment $fragment
	 * @return string
	 */
	public abstract function buildSQLFragment(Query\InterfaceSQLFragment $fragment);

	/**
	 * @param Query\InterfaceSQLFragment $fragment
	 * @return string
	 */
	public function buildCustomSQLFragment(Query\InterfaceSQLFragment $fragment)
	{
		$event = new \Change\Events\Event(static::EVENT_SQL_FRAGMENT_STRING, $this, array('fragment' => $fragment));
		$this->getEventManager()->trigger($event);
		$sql = $event->getParam('sql');
		if (is_string($sql))
		{
			return $sql;
		}
		$this->getLogging()->warn(__METHOD__ . '(' . get_class($fragment) . ') not implemented');
		return $fragment->toSQL92String();
	}

	/**
	 * @param array $argument
	 * @return Query\InterfaceSQLFragment|null
	 */
	public function getCustomSQLFragment(array $argument = array())
	{
		$event = new \Change\Events\Event(static::EVENT_SQL_FRAGMENT, $this, $argument);
		$this->getEventManager()->trigger($event);
		$fragment = $event->getParam('SQLFragment');
		if ($fragment instanceof Query\InterfaceSQLFragment)
		{
			return $fragment;
		}
		return null;
	}

	/**
	 * @param \Change\Db\Query\SelectQuery $selectQuery
	 * @return array
	 */
	public abstract function getQueryResultsArray(\Change\Db\Query\SelectQuery $selectQuery);

	/**
	 * @param AbstractQuery $query
	 * @return integer
	 */
	public abstract function executeQuery(AbstractQuery $query);

	/**
	 * @param mixed $value
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public abstract function phpToDB($value, $scalarType);

	/**
	 * @param mixed $value
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public abstract function dbToPhp($value, $scalarType);
}