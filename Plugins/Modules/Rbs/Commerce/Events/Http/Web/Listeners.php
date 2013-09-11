<?php
namespace Rbs\Commerce\Events\Http\Web;

use Change\Documents\Query\Query;
use Change\Http\Web\Event;
use Rbs\Commerce\Services\CommerceServices;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\Http\Web\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			$documentServices = $event->getDocumentServices();
			$commerceServices = new CommerceServices($event->getApplicationServices(), $documentServices);
			$event->setParam('commerceServices', $commerceServices);
			$extension = new \Rbs\Commerce\Presentation\TwigExtension($commerceServices);
			$event->getPresentationServices()->getTemplateManager()->addExtension($extension);

			$commerceServices->getEventManager()->attach('load',
				function (\Zend\EventManager\Event $event)
				{
					/* @var $commerceServices CommerceServices */
					$commerceServices = $event->getParam('commerceServices');
					$documentServices = $commerceServices->getDocumentServices();
					$session = new \Zend\Session\Container('Rbs_Commerce');
					if (!isset($session['initialized']))
					{
						$session['initialized'] = true;
						$session['cartIdentifier'] = $session['billingAreaId'] = $session['zone'] = null;
						$query = new Query($documentServices, 'Rbs_Price_BillingArea');
						$billingArea = $query->getFirstDocument();
						if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
						{
							$session['billingAreaId'] = $billingArea->getId();
							if ($billingArea->getTaxes()->count())
							{
								$tax = $billingArea->getTaxes()[0];
								$session['zone'] = $tax->getDefaultZone();
							}
						}
					}
					$commerceServices->setCartIdentifier($session['cartIdentifier']);
					$commerceServices->setZone($session['zone']);

					if ($session['billingAreaId'])
					{
						$billingAreaModel = $documentServices->getModelManager()->getModelByName('Rbs_Price_BillingArea');
						$commerceServices->setBillingArea($documentServices->getDocumentManager()
							->getDocumentInstance($session['billingAreaId'], $billingAreaModel));
					}
				}, 5);

			$commerceServices->getEventManager()->attach('save',
				function (\Zend\EventManager\Event $event)
				{
					/* @var $commerceServices CommerceServices */
					$commerceServices = $event->getParam('commerceServices');
					$session = new \Zend\Session\Container('Rbs_Commerce');
					$session['initialized'] = true;
					$session['cartIdentifier'] = $commerceServices->getCartIdentifier();
					$billingArea = $commerceServices->getBillingArea();
					if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
					{
						$session['billingAreaId'] = $billingArea->getId();
					}
					else
					{
						$session['billingAreaId'] = null;
					}
					$session['zone'] = $commerceServices->getZone();
				}, 5);
		};
		$events->attach(\Change\Http\Event::EVENT_REQUEST, $callback, 10);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}