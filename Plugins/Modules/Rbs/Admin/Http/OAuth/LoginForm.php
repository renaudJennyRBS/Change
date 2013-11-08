<?php
namespace Rbs\Admin\Http\OAuth;

/**
 * @name \Rbs\Admin\Http\OAuth\LoginForm
 */
class LoginForm
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function execute($event)
	{
		$data = $event->getParam('data');
		/** @var $httpEvent \Change\Http\Event */
		$httpEvent = $event->getParam('httpEvent');
		if ($data['realm'] === 'Rbs_Admin')
		{
			$applicationServices = $event->getApplicationServices();
			$uri = $httpEvent->getUrlManager()->getSelf();
			$uri->setPath($event->getApplication()->getConfiguration('Change\Install\webBaseURLPath') . '/admin.php/')->setQuery('');
			$data['baseUrl'] = $uri->normalize()->toString();
			$html = $applicationServices->getTemplateManager()->renderTemplateFile(__DIR__ . '/Assets/login.twig', $data);
			$event->setParam('html', $html);
		}
	}
}