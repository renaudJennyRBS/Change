<?php
namespace ChangeTests\Modules\Commerce\Process;

/**
 * @name \ChangeTests\Modules\Commerce\Process\ProcessManagerTest
 */
class ProcessManagerTest extends \ChangeTests\Change\TestAssets\TestCase
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
	 * @return \Rbs\Commerce\Process\ProcessManager
	 */
	protected function getProcessManager() {
		return $this->commerceServices->getProcessManager();
	}

	public function testGetInstance()
	{
		$this->assertInstanceOf('Rbs\Commerce\Process\ProcessManager', $this->getProcessManager());
	}

	public function testGetOrderProcessByCart() {
		/** @var $webStore \Rbs\Store\Documents\WebStore */
		$webStore = $this->getNewReadonlyDocument('Rbs_Store_WebStore', 100);

		/** @var $process \Rbs\Commerce\Documents\Process */
		$process = $this->getNewReadonlyDocument('Rbs_Commerce_Process', 200);
		$process->setActive(true);
		$webStore->setOrderProcess($process);


		$cart = new \Rbs\Commerce\Cart\Cart('testGetOrderProcessByCart', $this->commerceServices->getCartManager());
		$cart->setWebStoreId($webStore->getId());

		$this->assertSame($process, $this->getProcessManager()->getOrderProcessByCart($cart));

		$process->setActive(false);
		$this->assertNull($this->getProcessManager()->getOrderProcessByCart($cart));
	}
}