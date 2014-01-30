<?php
namespace Rbs\User\Events;

use Change\Events\Event;

/**
 * @name \Rbs\User\Events\InstallMails
 */
class InstallMails
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$mailTemplate = $event->getParam('mailTemplate');
		$filters = $event->getParam('filters');

		if (count($filters) === 0 || in_array('Rbs_User', $filters))
		{
			$docs = $applicationServices->getDocumentCodeManager()->getDocumentsByCode('user_account_request', 'Rbs Mail Install');
			if (count($docs) == 0)
			{
				$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'mails.json';
				$json = json_decode(file_get_contents($filePath), true);

				$import = new \Rbs\Generic\Json\Import($applicationServices->getDocumentManager());
				$import->setDocumentCodeManager($applicationServices->getDocumentCodeManager());

				$resolveDocument = function($id, $contextId) use ($mailTemplate) {
					switch ($id)
					{
						case 'mail_template':
							return $mailTemplate;
							break;
					}
					return null;
				};
				$import->getOptions()->set('resolveDocument', $resolveDocument);

				try
				{
					$applicationServices->getTransactionManager()->begin();
					/* @var $mail \Rbs\Mail\Documents\Mail */
					$import->fromArray($json);
					$applicationServices->getTransactionManager()->commit();
				}
				catch (\Exception $e)
				{
					$applicationServices->getTransactionManager()->rollBack($e);
				}
			}
		}
	}
}