<?php
namespace Rbs\User\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\User\Blocks\AccountSettings
 */
class AccountSettings extends Block
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
		$parameters->addParameterMeta('context', null);
		$parameters->addParameterMeta('errId');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$request = $event->getHttpRequest();

		$parameters->setParameterValue('errId', $request->getQuery('errId'));

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('authenticated', true);
		}
		else
		{
			$parameters->setParameterValue('context', $event->getHttpRequest()->getQuery('context'));
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
		$parameters = $event->getBlockParameters();
		if ($parameters->getParameterValue('authenticated'))
		{

		}
		else
		{
			$context = $parameters->getParameterValue('context');
			if ($context && ($context == 'accountRequest' || $context == 'accountSuccess'))
			{
				$attributes['context'] = $context;
				// Handle errors.
				$errId = $parameters->getParameterValue('errId');
				if ($errId)
				{
					$session = new \Zend\Session\Container('Change_Errors');
					$sessionErrors = isset($session[$errId]) ? $session[$errId] : null;
					if ($sessionErrors && is_array($sessionErrors))
					{
						$attributes['errors'] = isset($sessionErrors['errors']) ? $sessionErrors['errors'] : [];
					}
				}
			}
			else
			{
				//TODO 401
			}
		}
		return 'account-settings.twig';
	}
}