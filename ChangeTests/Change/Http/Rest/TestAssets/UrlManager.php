<?php
namespace ChangeTests\Change\Http\Rest\TestAssets;

/**
 * @name \ChangeTests\Change\Http\Rest\TestAssets\UrlManager
 */
class UrlManager extends \Change\Http\UrlManager
{
	/**
	 */
	public function __construct()
	{
		parent::__construct(new \Zend\Uri\Http('http://domain.net/'), '/rest.php');
	}
}