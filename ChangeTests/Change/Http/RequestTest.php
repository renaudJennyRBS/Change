<?php
namespace ChangeTests\Change\Http;

use Change\Http\Request;

class RequestTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Http\Request
	 */
	public function testConstruct()
	{
		//$_SERVER['REQUEST_URI'] = '/';
		$request = new Request();
		$this->assertInstanceOf('\Change\Http\Request', $request);
		$this->assertNull($request->getPath());
		$this->assertNull($request->getIfModifiedSince());
		$this->assertNull($request->getIfNoneMatch());

		$_SERVER['REQUEST_URI'] = '/';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Sat, 29 Oct 1994 19:43:31 GMT';
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'd00806e9f37d1764a1948ea1edf';

		$request = new Request();
		$this->assertEquals('/', $request->getPath());
		$this->assertInstanceOf('\DateTime', $request->getIfModifiedSince());
		$date = new \DateTime('Sat, 29 Oct 1994 19:43:31 GMT');

		$this->assertEquals($date->format(\DateTime::ISO8601), $request->getIfModifiedSince()->format(\DateTime::ISO8601));
		$this->assertEquals('d00806e9f37d1764a1948ea1edf', $request->getIfNoneMatch());

		return $request;
	}

	/**
	 * @depends testConstruct
	 * @param Request $request
	 * @return Request
	 */
	public function testPath($request)
	{
		$request->setPath(null);
		$this->assertNull($request->getPath());
		return $request;
	}

	/**
	 * @depends testPath
	 * @param Request $request
	 * @return Request
	 */
	public function testIfModifiedSince($request)
	{
		$request->setIfModifiedSince(null);
		$this->assertNull($request->getIfModifiedSince());
		return $request;
	}

	/**
	 * @depends testIfModifiedSince
	 * @param Request $request
	 * @return Request
	 */
	public function testIfNoneMatch($request)
	{
		$request->setIfNoneMatch(null);
		$this->assertNull($request->getIfNoneMatch());
		return $request;
	}
}