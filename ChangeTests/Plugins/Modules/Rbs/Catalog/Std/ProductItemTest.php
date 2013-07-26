<?php
namespace ChangeTests\Modules\Catalog\Std;

use  Rbs\Catalog\Std\ProductItem;

class ProductItemTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
	}

	public function testConstructor()
	{
		$o = new ProductItem();
		$this->assertFalse($o->hasData());

		$o = new ProductItem(array('test' => 'ok'));
		$this->assertTrue($o->hasData());
	}

	public function testCall()
	{
		$o = new ProductItem(array('test' => 'ok'));
		$this->assertEquals('ok', $o->test());
		$this->assertNull($o->id());

		$this->assertNull($o->notFound());

		/* @var $product \Rbs\Catalog\Documents\Product */
		$product = $this->getNewReadonlyDocument('Rbs_Catalog_Product', 800);
		$o = new ProductItem(array('id' => 800, 'test' => 'ok'));
		$o->setDocumentManager($this->getDocumentServices()->getDocumentManager());

		$product->setTitle('title property');
		$this->assertEquals('ok', $o->test());
		$this->assertEquals(800, $o->id());
		$this->assertEquals('title property', $o->title());
		$product->setTitle('title property modified');
		$this->assertEquals('title property', $o->title());

		$baseUrl = 'http://localhost/index.php';

		/* @var $website \Rbs\Website\Documents\Website */
		$website = $this->getNewReadonlyDocument('Rbs_Website_Website', 500);
		$website->setBaseurl($baseUrl);
		$urlManager = $website->getUrlManager($this->getApplicationServices()->getI18nManager()->getLCID());
		$this->assertEquals('document/800.html', $o->url($urlManager));
	}

	public function testSerializable()
	{
		/* @var $brand \Rbs\Brand\Documents\Brand */
		$brand = $this->getNewReadonlyDocument('Rbs_Brand_Brand', 201);

		/* @var $product \Rbs\Catalog\Documents\Product */
		$product = $this->getNewReadonlyDocument('Rbs_Catalog_Product', 800);

		$product->setBrand($brand);

		$o = new ProductItem(array('id' => 800));
		$o->setDocumentManager($this->getDocumentServices()->getDocumentManager());

		$this->assertSame($brand, $o->brand());

		$data = serialize($o);
		$this->assertEquals('C:27:"Rbs\\Catalog\\Std\\ProductItem":103:{a:2:{s:2:"id";i:800;s:5:"brand";C:38:"Change\\Documents\\DocumentWeakReference":19:{201 Rbs_Brand_Brand}}}', $data);

		$o2 = unserialize($data);
		$this->assertNotSame($o, $o2);
		$this->assertInstanceOf('Change\\Documents\\DocumentWeakReference', $o2->brand());

		$o2->setDocumentManager($this->getDocumentServices()->getDocumentManager());
		$this->assertSame($brand, $o2->brand());
	}
}