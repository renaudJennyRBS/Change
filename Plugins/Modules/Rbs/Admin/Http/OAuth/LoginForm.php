<?php
namespace Rbs\Admin\Http\OAuth;

/**
 * @name \Rbs\Admin\Http\OAuth\LoginForm
 */
class LoginForm
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function execute($event)
	{
		$data = $event->getParam('data');
		/** @var $httpEvent \Change\Http\Event */
		$httpEvent = $event->getParam('httpEvent');
		if ($data['realm'] === 'Rbs_Admin')
		{
			/** @var $manager \Change\Http\OAuth\OAuthManager */
			$manager = $event->getTarget();
			$presentationServices = new \Change\Presentation\PresentationServices($manager->getApplicationServices());
			$uri = $httpEvent->getUrlManager()->getSelf();
			$uri->setPath($manager->getApplicationServices()->getApplication()->getConfiguration('Change\Install\webBaseURLPath') . '/admin.php/')->setQuery('');
			$data['baseUrl'] = $uri->normalize()->toString();
			$html = $presentationServices->getTemplateManager()->renderTemplateFile(__DIR__ . '/Assets/login.twig', $data);
			$event->setParam('html', $html);
		}
	}
}