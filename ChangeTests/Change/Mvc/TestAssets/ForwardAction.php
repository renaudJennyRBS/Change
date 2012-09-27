<?php
namespace Change\Website\Actions;

/**
 * @name \Change\Website\Actions\Error404
 */
class Error404 extends \Change\Mvc\AbstractAction
{
	/**
	 *
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
	
	/**
	 * @return boolean
	 */
	protected function checkPermissions()
	{
		return true;
	}

	/**
	 * @param \Change\Mvc\Context $context
	 * @param \Change\Mvc\Request $request
	 * @throws \Exception
	 * @return string null string[]
	 */
	protected function _execute($context, $request)
	{
		echo 'Website\Error404';
		return null;
	}
}

namespace Change\Users\Actions;

/**
 * @name \Change\Users\Actions\Login
 */
class Login extends \Change\Mvc\AbstractAction
{
	/**
	 *
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}

	
	/**
	 * @return boolean
	 */
	protected function checkPermissions()
	{
		return true;
	}
	
	/**
	 *
	 * @param \Change\Mvc\Context $context
	 * @param \Change\Mvc\Request $request
	 * @throws \Exception
	 * @return string null string[]
	 */
	protected function _execute($context, $request)
	{
		echo 'Users\Login';
		return null;
	}
}