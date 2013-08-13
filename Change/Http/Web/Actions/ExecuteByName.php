<?php
namespace Change\Http\Web\Actions;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Change\Http\Web\Actions\ExecuteByName
*/
class ExecuteByName
{
	/**
	 * Use Required Event Params:
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$action = $event->getParam('action');
		if (is_array($action) && count($action) === 3)
		{
			$className = '\\' . $action[0] . '\\' . $action[1] . '\\Http\\Web\\' .str_replace('/', '\\', $action[2]);
			if (class_exists($className))
			{
				$callable = array($className, 'executeByName');
				if (is_callable($callable))
				{
					call_user_func($callable, $event);
				}
			}
		}
	}
}