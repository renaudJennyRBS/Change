<?php
namespace Rbs\Commerce\Http\Web;

/**
 * @name \Rbs\Commerce\Http\Web\GetCompatibleShippingModes
 */
class GetCompatibleShippingModes extends \Change\Http\Web\Actions\AbstractAjaxAction
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
		$query = $documentManager->getNewQuery('Rbs_Shipping_Mode');
		$pb = $query->getPredicateBuilder();
		$query->andPredicates($pb->activated());
		$shippingModes = $query->getDocuments();
		if (count($shippingModes))
		{
			$richTextContext = array('website' => $event->getUrlManager()->getWebsite());
			$richTextManager = $event->getApplicationServices()->getRichTextManager();

			$modesInfos = array();
			foreach ($shippingModes as $index => $shippingMode)
			{
				/* @var $shippingMode \Rbs\Shipping\Documents\Mode */
				$modeInfos = array(
					'id' => $shippingMode->getId(),
					'title' => $shippingMode->getCurrentLocalization()->getTitle(),
					'description' => $richTextManager->render($shippingMode->getCurrentLocalization()->getDescription(), "Website", $richTextContext)
				);

				$visual = $shippingMode->getVisual();
				if ($visual)
				{
					$modeInfos['visualId'] = $visual->getId();
					$modeInfos['visualUrl'] = $visual->getPublicURL(160, 90); // TODO: get size as a parameter?
				}

				if ($index == 0)
				{
					$modeInfos['feesValue'] = 'Offert';
					$modeInfos['directiveName'] = 'rbs-commerce-shipping-mode-configuration-none';
				}
				elseif ($index == 1)
				{
					$modeInfos['feesValue'] = '15,05 €';
					$modeInfos['directiveName'] = 'rbs-commerce-shipping-mode-configuration-address';
				}

				$modesInfos[] = $modeInfos;
			}

			$result = $this->getNewAjaxResult($modesInfos);
			$event->setResult($result);
			return;
		}
	}
}