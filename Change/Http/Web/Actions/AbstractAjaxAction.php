<?php
namespace Change\Http\Web\Actions;

/**
* @name \Change\Http\Web\Actions\AbstractAjaxAction
*/
abstract class AbstractAjaxAction
{
	function __invoke()
	{
		if (func_num_args() === 1)
		{
			$event = func_get_arg(0);
			if ($event instanceof \Change\Http\Web\Event)
			{
				$this->execute($event);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	abstract public function execute(\Change\Http\Web\Event $event);

	/**
	 * @param array $data
	 * @return \Change\Http\Web\Result\AjaxResult
	 */
	protected function getNewAjaxResult(array $data = array())
	{
		return new \Change\Http\Web\Result\AjaxResult($data);
	}
}