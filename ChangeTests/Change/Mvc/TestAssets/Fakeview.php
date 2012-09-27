<?php

namespace Change\Fakemodule\Views;

/**
 *
 * @name \Change\Fakemodule\Actions\Fakeview
 */
class Fakeview extends \Change\Mvc\AbstractView
{
	
	/**
	 * @param \Change\Mvc\Context $context
	 * @param \Change\Mvc\Request $request
	 */
	protected function _execute($context, $request)
	{
		echo 'Fakeview result';
		return null;
	}
}