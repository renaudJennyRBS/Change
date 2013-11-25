<?php
namespace Rbs\User\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\User\Blocks\AccountShort
 */
class AccountShort extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('accessorId');

		$currentUser = $event->getAuthenticationManager()->getCurrentUser();
		if ($currentUser->authenticated())
		{
			$parameters->setParameterValue('accessorId', $currentUser->getId());

			/* @var $user \Rbs\User\Documents\User */
			/*$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($currentUser->getId(), 'Rbs_User_User');*/
			$parameters->setParameterValue('accessorName', $currentUser->getName());
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		return 'account-short.twig';
	}
}
