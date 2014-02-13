<?php
namespace Rbs\Commerce\Events\PaymentManager;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\PaymentManager\Listeners
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
			(new \Rbs\Commerce\Events\PaymentManager\Transaction())->handleProcessing($event);
		};
		$events->attach('handleProcessingForTransaction', $callback, 10);

		$callback = function (Event $event)
		{
			(new \Rbs\Commerce\Events\PaymentManager\Transaction())->handleSuccess($event);
		};
		$events->attach('handleSuccessForTransaction', $callback, 10);

		$callback = function (Event $event)
		{
			(new \Rbs\Commerce\Events\PaymentManager\Transaction())->handleFailed($event);
		};
		$events->attach('handleProcessingForTransaction', $callback, 10);

		$callback = function (Event $event)
		{
			(new \Rbs\Commerce\Events\PaymentManager\Transaction())->handleRegistration($event);
		};
		$events->attach('handleRegistrationForTransaction', $callback, 10);
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