<?php
namespace ChangeTests\Modules\Commerce\Process;

/**
 * @name \ChangeTests\Modules\Commerce\Process\ProcessManagerTest
 */
class ProcessManagerTest extends \ChangeTests\Change\TestAssets\TestCase
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