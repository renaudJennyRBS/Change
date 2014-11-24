<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn;

/**
 * @name \Rbs\Productreturn\CodeBuilder
 */
class CodeBuilder
{
	public function onGetNewCode(\Change\Events\Event $event)
	{
		$padLength = 8;
		$application = $event->getApplication();

		$document = $event->getParam('document');
		if ($document instanceof \Rbs\Productreturn\Documents\ProductReturn)
		{
			$tableName = 'rbs_productreturn_seq_productreturn';
			$padLength = $application->getConfiguration()->getEntry('Rbs/Productreturn/CodeBuilder/ProductReturnNumberPad', $padLength);
		}
		else
		{
			return;
		}

		$applicationServices = $event->getApplicationServices();
		try
		{
			$applicationServices->getTransactionManager()->begin();
			$nextId = $this->getNextId($applicationServices->getDbProvider(), $tableName);
			$prefix = (new \DateTime())->format('Y');
			$number = str_pad($nextId, $padLength, '0', STR_PAD_LEFT);
			$event->setParam('newCode', $prefix . $number);
			$applicationServices->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			$event->getApplication()->getLogging()->exception($e);
			$applicationServices->getTransactionManager()->commit();
		}
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param $tableName
	 * @throw \Exception
	 * @return int
	 */
	protected function getNextId(\Change\Db\DbProvider $dbProvider, $tableName)
	{
		$sb = $dbProvider->getNewStatementBuilder();
		$fb = $sb->getFragmentBuilder();

		$iq = $sb->insert($fb->table($tableName))->addColumn($fb->column('creation_date'))
			->addValue($fb->dateTimeParameter('creationCate'))
			->insertQuery();
		$iq->bindParameter('creationCate', new \DateTime());

		$iq->execute();
		return $dbProvider->getLastInsertId($tableName);
	}
} 