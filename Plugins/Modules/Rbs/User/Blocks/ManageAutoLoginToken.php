<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Blocks;

/**
 * @name \Rbs\User\Blocks\ManageAutoLoginToken
 */
class ManageAutoLoginToken extends \Change\Presentation\Blocks\Standard\Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('authenticated', false);

		$parameters->setNoCache();
		$parameters->setLayoutParameters($event->getBlockLayout());

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('authenticated', true);
		}

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$authenticationManager = $event->getApplicationServices()->getAuthenticationManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$i18nManager = $event->getApplicationServices()->getI18nManager();

		$currentUser = $authenticationManager->getCurrentUser();

		$tokens = $this->getTokens($dbProvider, $currentUser->getId());
		$result = array();
		foreach ($tokens as $token)
		{
			$token['date'] = $i18nManager->transDateTime($token['validity_date']);
			$result[] = $token;
		}

		$attributes['tokensData'] = $result;
		return 'manage-auto-login-token.twig';
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param integer $userId
	 * @return array
	 */
	protected function getTokens($dbProvider, $userId)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'), $fb->column('device'), $fb->column('validity_date'));
		$qb->from($fb->table('rbs_user_auto_login'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('user_id'), $fb->parameter('userId')),
			$fb->gt($fb->column('validity_date'), $fb->dateTimeParameter('validityDate'))
		));
		$sq = $qb->query();

		$sq->bindParameter('userId', $userId);
		$now = new \DateTime();
		$sq->bindParameter('validityDate', $now);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('id')->addStrCol('device')->addDtCol('validity_date'));
	}
}