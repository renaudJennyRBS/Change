<?php
namespace Change;

abstract class AbstractService extends AbstractSingleton
{
	/**
	 * @return \Change\Db\Provider
	 */
	protected function getDbProvider()
	{
		return \Change\Db\Provider::getInstance();
	}
} 