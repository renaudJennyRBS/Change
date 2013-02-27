<?php
namespace ChangeTests\Change\Http;

use Change\Http\Result;

class ResultTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Http\Result
	 */
	public function testConstruct()
	{
		$result = new Result();
		$this->assertInstanceOf('\Change\Http\Result', $result);
		$this->assertNull($result->getHttpStatusCode());

		return $result;
	}

	/**
	 * @depends testConstruct
	 * @param Result $result
	 * @return Result
	 */
	public function testHttpStatusCode($result)
	{
		$result->setHttpStatusCode(200);
		$this->assertEquals(200, $result->getHttpStatusCode());
		return $result;
	}

	/**
	 * @depends testHttpStatusCode
	 * @param Result $result
	 * @return Result
	 */
	public function testHeaders($result)
	{
		$h = $result->getHeaders();
		$this->assertInstanceOf('\Zend\Http\Headers', $h);

		$headers = new \Zend\Http\Headers();
		$result->setHeaders($headers);
		$this->assertNotSame($h, $result->getHeaders());

		$this->assertSame($headers, $result->getHeaders());

		$result->setHeaderLocation('http://headerlocation.net');

		$this->assertEquals('http://headerlocation.net', $headers->get('Location')->getFieldValue());

		$result->setHeaderContentLocation('http://headercontentlocation.net');
		$this->assertEquals('http://headercontentlocation.net', $headers->get('Content-location')->getFieldValue());

		$this->assertNull($result->getHeaderLastModified());
		$date = new \DateTime();
		$result->setHeaderLastModified($date);

		$this->assertEquals($date, $result->getHeaderLastModified());

		$result->setHeaderLastModified(null);
		$this->assertNull($result->getHeaderLastModified());

		$this->assertNull($result->getHeaderEtag());
		$result->setHeaderEtag('test');
		$this->assertEquals('test', $result->getHeaderEtag());
		$result->setHeaderEtag(null);
		$this->assertNull($result->getHeaderEtag());

		return $result;
	}
}