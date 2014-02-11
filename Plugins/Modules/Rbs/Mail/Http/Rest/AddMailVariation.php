<?php
namespace Rbs\Mail\Http\Rest;

/**
 * @name \Rbs\Mail\Http\Rest\AddMailVariation
 */
class AddMailVariation
{
	public function execute(\Change\Http\Event $event)
	{
		$documentId = $event->getRequest()->getPost('documentId');
		if ($documentId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			/* @var $document \Rbs\Mail\Documents\Mail */
			$document = $documentManager->getDocumentInstance($documentId);
			if ($document)
			{
				/* @var $variation \Rbs\Mail\Documents\Mail */
				$variation = $documentManager->getNewDocumentInstanceByModelName('Rbs_Mail_Mail');
				//TODO find a better way to copy the mail
				$variation->setCode($document->getCode());
				$variation->setLabel($document->getLabel());
				$variation->setSubstitutions($document->getSubstitutions());
				$variation->setTemplate($document->getTemplate());
				$variation->setUseCache($document->getUseCache());
				$variation->setTTL($document->getTTL());
				$variation->setIsVariation(true);
				foreach ($document->getLCIDArray() as $LCID)
				{
					$documentManager->pushLCID($LCID);
					$variation->getCurrentLocalization()->setSubject($document->getCurrentLocalization()->getSubject());
					$variation->getCurrentLocalization()->setSenderMail($document->getCurrentLocalization()->getSenderMail());
					$variation->getCurrentLocalization()->setSenderName($document->getCurrentLocalization()->getSenderName());
					$variation->getCurrentLocalization()->setEditableContent($document->getCurrentLocalization()->getEditableContent());
					$variation->getCurrentLocalization()->setActive($document->getCurrentLocalization()->getActive());
					$variation->getCurrentLocalization()->setStartActivation($document->getCurrentLocalization()->getStartActivation());
					$variation->getCurrentLocalization()->setEndActivation($document->getCurrentLocalization()->getEndActivation());
					$documentManager->popLCID();
				}
				
				$tm = $event->getApplicationServices()->getTransactionManager();
				try
				{
					$tm->begin();
					$variation->save();
					$tm->commit();
				}
				catch (\Exception $e)
				{
					throw $tm->rollBack($e);
				}

				$event->setParam('documentId', $variation->getId());
				$event->setParam('modelName', $variation->getDocumentModelName());
				$action = new \Change\Http\Rest\Actions\GetDocument();
				$action->execute($event);
				return;
			}
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'invalid document mail id');
			$event->setResult($result);
		}
	}
}