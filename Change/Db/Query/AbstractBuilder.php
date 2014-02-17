<?php
namespace Change\Db\Query;

use Change\Db\DbProvider;

/**
* @name \Change\Db\Query\AbstractBuilder
*/
class AbstractBuilder
{
	/**
	 * @var string
	 */
	protected $cacheKey;
	/**
	 * @var DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var SQLFragmentBuilder
	 */
	protected $fragmentBuilder;

	/**
	 * @var AbstractQuery
	 */
	protected $query;

	/**
	 * @api
	 * @return DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @api
	 * @param DbProvider $dbProvider
	 */
	public function setDbProvider(DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @api
	 * @return SQLFragmentBuilder
	 */
	public function getFragmentBuilder()
	{
		return new SQLFragmentBuilder($this);
	}

	/**
	 * @api
	 * @return \Change\Db\SqlMapping
	 */
	public function getSqlMapping()
	{
		return $this->dbProvider->getSqlMapping();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isCached()
	{
		return $this->cacheKey !== null && $this->query !== null && $this->query->getCachedKey() === $this->cacheKey;
	}

	/**
	 * @api
	 * Explicitly reset the builder (which will destroy the current query).
	 * @return $this
	 */
	public function reset()
	{
		$this->cacheKey = null;
		$this->query = null;
		return $this;
	}

	/**
	 * @api
	 * @throws \InvalidArgumentException
	 * @throws \LogicException
	 * @param string|Expressions\Parameter $parameter
	 * @return $this
	 */
	public function addParameter($parameter)
	{
		if ($this->query === null)
		{
			throw new \LogicException('Query not initialized', 42016);
		}

		if (is_string($parameter))
		{
			$this->getFragmentBuilder()->parameter($parameter);
			return $this;
		}

		if (!($parameter instanceof Expressions\Parameter))
		{
			throw new \InvalidArgumentException('Argument 1 must be a Expressions\Parameter', 42004);
		}

		$this->query->addParameter($parameter);
		return $this;
	}
}