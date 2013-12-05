<?php

namespace ChangeTests\Modules\Commerce\Std;

/**
 * @name \ChangeTests\Modules\Commerce\Std\ContextTest
 */
class ContextTest extends \ChangeTests\Change\TestAssets\TestCase
{


	public function testLoad()
	{
		$cs = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());

		$context = $cs->getContext();

		$context->getEventManager()->attach('load', function (\Change\Events\Event $event)
			{
				/** @var $target \Rbs\Commerce\Std\Context */
				$target = $event->getTarget();
				$target->setBillingArea(new FakeBillingArea_451235());
				$target->setZone('FZO');
				$target->setCartIdentifier('FAKECartIdentifier');
			}
			, 5);

		$this->assertInstanceOf('Rbs\Price\Tax\BillingAreaInterface', $context->getBillingArea());
		$this->assertEquals('FAK', $context->getBillingArea()->getCurrencyCode());
		$this->assertEquals('FZO', $context->getZone());
		$this->assertEquals('FAKECartIdentifier', $context->getCartIdentifier());
	}
}

class FakeBillingArea_451235 implements \Rbs\Price\Tax\BillingAreaInterface
{
	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return 'FAK';
	}

	/**
	 * @return \Rbs\Price\Tax\TaxInterface[]
	 */
	public function getTaxes()
	{
		return array();
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return 'BA';
	}
}

