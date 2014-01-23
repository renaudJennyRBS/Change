<?php
namespace Rbs\Mail\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Mail\Commands\InstallMails
 */
class InstallMails
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$response = $event->getCommandResponse();

		$templateCode = $event->getParam('template');
		if ($templateCode)
		{
			$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Theme_Template');
			$dqb->andPredicates($dqb->eq('code', $templateCode), $dqb->eq('mailSuitable', true));
			$template = $dqb->getFirstDocument();
			if ($template)
			{
				$package = $event->getParam('package');
				if ($package)
				{
					//TODO
				}
				else
				{
					//TODO too
				}

				/* @var $genericServices \Rbs\Generic\GenericServices */
				$genericServices = $event->getServices('genericServices');
				$mailManager = $genericServices->getMailManager();;
				$eventManager = $mailManager->getEventManager();
				$args = $eventManager->prepareArgs(array(
					'mailTemplate' => $template
				));
				$eventManager->trigger('installMails', $this, $args);

				$response->addInfoMessage('Mails installed');
			}
			else
			{
				$response->addErrorMessage('template suitable for mail with code: ' . $templateCode . ' not found');
			}
		}
		else
		{
			$response->addErrorMessage('no template code given');
		}


	}
}