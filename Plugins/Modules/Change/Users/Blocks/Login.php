<?php
namespace Change\Users\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Change\Users\Blocks\Login
 */
class Login extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('login', Property::TYPE_STRING, true);
		$parameters->addParameterMeta('password', Property::TYPE_STRING, true);
		$parameters->addParameterMeta('realm', Property::TYPE_STRING, true, 'web');
		$parameters->addParameterMeta('accessorId', Property::TYPE_INTEGER, false);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$request = $event->getHttpRequest();

		$login = $request->getPost('login');
		if ($login) {$parameters->setParameterValue('login', $login);}

		$password = $request->getPost('password');
		if ($password) {$parameters->setParameterValue('password', $password);}
		if ($event->getAuthentication())
		{
			$parameters->setParameterValue('accessorId', $event->getAuthentication()->getIdentity());
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters(), getBlockResult(),
	 *        getPresentationServices(), getDocumentServices()
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		return 'login.twig';
	}
}