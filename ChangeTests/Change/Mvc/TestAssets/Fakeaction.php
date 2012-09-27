<?php

namespace Change\Fakemodule\Actions;

/**
 *
 * @name \Change\Fakemodule\Actions\Fakeaction
 */
class Fakeaction extends \Change\Mvc\AbstractAction
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
	 *
	 * @param \Change\Mvc\Context $context        	
	 * @param \Change\Mvc\Request $request        	
	 * @throws \Exception
	 * @return string null string[]
	 */
	protected function _execute($context, $request)
	{
		echo 'Fakemodule\Fakeaction';
		return null;
	}
}


/**
 *
 * @name \Change\Fakemodule\Actions\Fakesecureaction
 */
class Fakesecureaction extends \Change\Mvc\AbstractAction
{
	/**
	 *
	 * @return boolean
	 */
	public function isSecure()
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
		echo 'Fakemodule\Fakesecureaction';
		return null;
	}
}