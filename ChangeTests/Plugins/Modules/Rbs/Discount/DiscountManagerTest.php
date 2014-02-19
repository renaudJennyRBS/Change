<?php
namespace ChangeTests\Rbs\Discount;

/**
* @name \ChangeTests\Rbs\Discount\DiscountManagerTest
*/
class DiscountManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var \Rbs\Commerce\CommerceServices;
	 */
	protected $commerceServices;

	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	protected function setUp()
	{
		parent::setUp();
		$this->commerceServices = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('commerceServices', $this->commerceServices);
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