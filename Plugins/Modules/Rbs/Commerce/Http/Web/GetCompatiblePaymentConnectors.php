<?php
namespace Rbs\Commerce\Http\Web;

/**
 * @name \Rbs\Commerce\Http\Web\GetCompatiblePaymentConnectors
 */
class GetCompatiblePaymentConnectors extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());

		// TODO: mockup implementation... the real implementation should be done in a ProcessManager.
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$query = $documentManager->getNewQuery('Rbs_Payment_Connector');
		$pb = $query->getPredicateBuilder();
		$query->andPredicates($pb->activated());
		$connectors = $query->getDocuments();
		if (count($connectors))
		{
			$richTextContext = array('website' => $event->getUrlManager()->getWebsite());
			$richTextManager = $event->getApplicationServices()->getRichTextManager();

			$connectorsInfos = array();
			foreach ($connectors as $connector)
			{
				/* @var $connector \Rbs\Payment\Documents\DeferredConnector */
				$modeInfos = array(
					'id' => $connector->getId(),
					'title' => $connector->getCurrentLocalization()->getTitle(),
					'description' => $richTextManager->render($connector->getCurrentLocalization()->getDescription(), "Website", $richTextContext)
				);

				$visual = $connector->getVisual();
				if ($visual)
				{
					$modeInfos['visualId'] = $visual->getId();
					$modeInfos['visualUrl'] = $visual->getPublicURL(160, 90); // TODO: get size as a parameter?
				}

				$modeInfos['directiveName'] = 'rbs-commerce-payment-connector-deferred';

				$connectorsInfos[] = $modeInfos;
			}

			$result = $this->getNewAjaxResult($connectorsInfos);
			$event->setResult($result);
			return;
		}
	}
}