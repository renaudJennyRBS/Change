<?php
namespace ChangeTests\Rbs\Discount;

/**
* @name \ChangeTests\Rbs\Discount\DiscountManagerTest
*/
class DiscountManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	protected function attachSharedListener(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		parent::attachSharedListener($sharedEventManager);
		$this->attachCommerceServicesSharedListener($sharedEventManager);
	}

	protected function setUp()
	{
		parent::setUp();
		$this->initServices($this->getApplication());
	}

	/**
	 * @return \Rbs\Discount\DiscountManager
	 */
	protected function getDiscountManager() {
		return $this->commerceServices->getDiscountManager();
	}

	public function testGetInstance()
	{
		$this->assertInstanceOf('Rbs\Discount\DiscountManager', $this->getDiscountManager());
	}
} 