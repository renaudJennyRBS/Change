<?php

class ImageTest extends \ChangeTests\Change\TestAssets\TestCase
{

	/**
	 * @var \Rbs\Media\Documents\Image
	 */
	protected $image;

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function setUp()
	{
		parent::setUp();
		$this->image = $this->createAnImage();
	}

	public function tearDown()
	{
		$this->deleteAnImage($this->image);
		parent::tearDown();
	}

	public function testGetImageSize()
	{
		$imageSize = $this->image->getImageSize();
		$this->assertNotNull($imageSize);
		$this->assertArrayHasKey('width', $imageSize);
		$this->assertEquals(179, $imageSize['width']);
		$this->assertArrayHasKey('height', $imageSize);
		$this->assertEquals(239, $imageSize['height']);
	}

	public function testGetMimeType()
	{
		$mimeType = $this->image->getMimeType();
		$this->assertNotNull($mimeType);
		$this->assertEquals('image/png', $mimeType);
	}

	public function testGetPublicURL()
	{
		//Set a base url for the LocalStorage, if not that doesn't work
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Storage/images/baseURL', 'change.test.com');
		$publicURL = $this->image->getPublicURL();
		$this->assertNotNull($publicURL);
		$this->assertEquals('change.test.com/Storage/images/dummy.png', $publicURL);
		//test with another max width/height
		//TODO: add more tests when the getPublicURL issue will be solved
		$publicURL = $this->image->getPublicURL(100);
		$this->assertNotNull($publicURL);
		$this->assertEquals('change.test.com/Storage/images/dummy.png', $publicURL);

	}

	public function testUpdateRestDocumentLink()
	{
		$documentLink = new \Change\Http\Rest\Result\DocumentLink(new \Change\Http\UrlManager(new \Zend\Uri\Http()), $this->image, \Change\Http\Rest\Result\DocumentLink::MODE_PROPERTY);
		$this->assertNotNull($documentLink);
		$result = $documentLink->toArray();
		$this->assertNotNull($result);
		$this->assertArrayHasKey('actions', $result);
		$this->assertNotNull($result['actions']);
		$isResizeActionIsAvailable = false;
		foreach ($result['actions'] as $action)
		{
			if (in_array('resizeurl', $action))
			{
				$this->assertArrayHasKey('rel', $action);
				$this->assertEquals('resizeurl', $action['rel']);
				$this->assertArrayHasKey('href', $action);
				$this->assertNotNull($action['href']);
				$isResizeActionIsAvailable = true;
			}
		}
		$this->assertTrue($isResizeActionIsAvailable, 'action resize is not available');
	}

	public function testUpdateRestDocumentResult()
	{
		//Set a base url for the LocalStorage, if not public URL will be null
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Storage/images/baseURL', 'change.test.com');
		$documentResult = new \Change\Http\Rest\Result\DocumentResult(new \Change\Http\UrlManager(new \Zend\Uri\Http()), $this->image);
		$this->assertNotNull($documentResult);
		$result = $documentResult->toArray();
		$this->assertNotNull($result);
		$this->assertArrayHasKey('links', $result);
		$this->assertNotNull($result['links']);
		$isPublicURLLinkIsAvailable = false;
		foreach ($result['links'] as $link)
		{
			if (in_array('publicurl', $link))
			{
				$this->assertArrayHasKey('rel', $link);
				$this->assertEquals('publicurl', $link['rel']);
				$this->assertArrayHasKey('href', $link);
				$this->assertNotNull($link['href']);
				$isPublicURLLinkIsAvailable = true;
			}
		}
		$this->assertTrue($isPublicURLLinkIsAvailable, 'link Public URL is not available');
		$this->assertArrayHasKey('actions', $result);
		$this->assertNotNull($result['actions']);
		$isResizeActionIsAvailable = false;
		foreach ($result['actions'] as $action)
		{
			if (in_array('resizeurl', $action))
			{
				$this->assertArrayHasKey('rel', $action);
				$this->assertEquals('resizeurl', $action['rel']);
				$this->assertArrayHasKey('href', $action);
				$this->assertNotNull($action['href']);
				$isResizeActionIsAvailable = true;
			}
		}
		$this->assertTrue($isResizeActionIsAvailable, 'action resize is not available');
	}

	/**
	 * @return \Rbs\Media\Documents\Image
	 */
	protected function createAnImage()
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();

		/* @var $image \Rbs\Media\Documents\Image */
		$image = $dm->getNewDocumentInstanceByModelName('Rbs_Media_Image');

		$image->setLabel('test image');
		//Image is dummy.png file 179x239 placed directly in folder App/Storage/images/
		$image->setPath('change://images/dummy.png');
		try
		{
			$tm->begin();
			$image->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
			$this->fail('Image cannot be created with this error: ' . $e->getMessage());
		}
		$this->assertTrue($image->getId() > 0);
		return $image;
	}

	/**
	 * @param \Rbs\Media\Documents\Image $image
	 */
	protected function deleteAnImage($image)
	{
		$tm = $this->getApplicationServices()->getTransactionManager();

		try
		{
			$tm->begin();
			$image->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
			$this->fail('Image cannot be deleted with this error: ' . $e->getMessage());
		}
	}
}