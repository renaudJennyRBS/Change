<?php
namespace Change;

abstract class AbstractService extends AbstractSingleton
{
	/**
	 * @return f_persistentdocument_PersistentProvider
	 */
	protected function getPersistentProvider()
	{
		return \f_persistentdocument_PersistentProvider::getInstance();
	}
	
	/**
	 * @return f_persistentdocument_TransactionManager
	 */
	protected function getTransactionManager()
	{
		return \f_persistentdocument_TransactionManager::getInstance();
	}
} 