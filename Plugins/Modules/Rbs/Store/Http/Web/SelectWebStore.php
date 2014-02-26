<?php
namespace Rbs\Store\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Store\Http\Web\SelectWebStore
 */
class SelectWebStore extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$request = $event->getRequest();
		$data = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			/** @var $webStore \Rbs\Store\Documents\WebStore */
			$webStore = isset($data['webStoreId']) ? $documentManager->getDocumentInstance($data['webStoreId']) : null;
			/** @var $billingArea \Rbs\Price\Documents\BillingArea */
			$billingArea = isset($data['billingAreaId']) ? $documentManager->getDocumentInstance($data['billingAreaId']) : null;

			$zone = isset($data['zone']) ? $data['zone'] : null;

			if ($this->checkStoreAndArea($webStore, $billingArea))
			{
				$context = $commerceServices->getContext();
				$context->setWebStore($webStore);
				$context->setBillingArea($billingArea);

				if ($zone == null)
				{
					$zones = array();
					foreach ($billingArea->getTaxes() as $tax)
					{
						$zones = array_merge($zones, $tax->getZoneCodes());
					}
					$zones = array_unique($zones);
					if (count($zones) == 1)
					{
						$zone = $zones[0];
					}
				}

				if ($zone)
				{
					$context->setZone($zone);
				}

				$context->save();

				$event->setResult($this->getNewAjaxResult());
				return;
			}
		}

		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$message = $i18nManager->trans('m.rbs.store.front.error_invalid_parameters', ['ucf']);
		$result = $this->getNewAjaxResult(['errors' => array($message)]);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
		$event->setResult($result);
	}
	/**
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @param \Rbs\Price\Documents\BillingArea $billingArea
	 * @return bool
	 */
	protected function checkStoreAndArea($webStore, $billingArea)
	{
		if (!($webStore instanceof \Rbs\Store\Documents\WebStore))
		{
			return false;
		}
		if (!($billingArea instanceof \Rbs\Price\Documents\BillingArea))
		{
			return false;
		}
		return in_array($billingArea->getId(), $webStore->getBillingAreas()->getIds());
	}
}