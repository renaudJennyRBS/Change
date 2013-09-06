<?php
namespace Rbs\Generic\Http\Web;

use Change\Http\Web\Event;

/**
* @name \Rbs\Generic\Http\Web\GetError
*/
class GetError extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		$errId = $request->isPost() ? $request->getPost('errId',  $request->getQuery('errId')) : $request->getQuery('errId');
		if ($errId)
		{
			$session = new \Zend\Session\Container('Change_Errors');
			if (isset($session[$errId]) && is_array($session[$errId]))
			{
				$event->setResult($this->getNewAjaxResult($session[$errId]));
				return;
			}
		}
		$event->setResult($this->getNewAjaxResult(array('exception' => array())));
	}
}