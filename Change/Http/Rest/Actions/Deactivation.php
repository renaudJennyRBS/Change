<?php
namespace Change\Http\Rest\Actions;

/**
* @name \Change\Http\Rest\Actions\Deactivation
*/
class Deactivation extends Activation
{
	protected function getNewStatus()
	{
		return false;
	}
}