<?php
namespace Rbs\Productreturn\Events;

/**
 * @name \Rbs\Productreturn\Events\ProcessManager
 */
class ProcessManager
{
	/**
	 * Input params: process, context
	 * Output param: processData
	 * @param \Change\Events\Event $event
	 */
	public function onReturnGetShippingModesDataByAddress(\Change\Events\Event $event)
	{
		if (!$event->getParam('shippingModesData'))
		{
			$process = $event->getParam('process');
			if (is_numeric($process))
			{
				$process = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($process);
			}
			if ($process instanceof \Rbs\Productreturn\Documents\Process)
			{
				$context = $event->getParam('context');
				$data = isset($context['data']) && is_array($context['data']) ? $context['data'] : [];
				$addressData = isset($data['address']) && is_array($data['address']) ? $data['address'] : [];
				if ($addressData)
				{
					/** @var \Rbs\Commerce\CommerceServices $commerceServices */
					$commerceServices = $event->getServices('commerceServices');
					$processManager = $commerceServices->getProcessManager();

					$shippingModesData = [];
					$address = new \Rbs\Geo\Address\BaseAddress($addressData);
					$shippingModes = $process->getReshippingModes();
					foreach ($shippingModes as $shippingMode)
					{
						if ($shippingMode->activated() && $processManager->isValidAddressForShippingMode($shippingMode, $address))
						{
							$shippingModesData[] = $processManager->getShippingModeData($shippingMode, $context);
						}
					}
					$event->setParam('shippingModesData', $shippingModesData);
				}
			}
		}
	}
} 