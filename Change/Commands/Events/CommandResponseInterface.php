<?php
namespace Change\Commands\Events;

/**
 * @name \Change\Commands\Events\CommandResponseInterface
 */
interface CommandResponseInterface
{
	public function addCommentMessage($message);

	public function addInfoMessage($message);

	public function addWarningMessage($message);

	public function addErrorMessage($message);

	public function setData($data);
}