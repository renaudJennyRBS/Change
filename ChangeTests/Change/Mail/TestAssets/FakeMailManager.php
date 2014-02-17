<?php
namespace ChangeTests\Change\Mail\TestAssets;

use Change\Mail\MailManager;

/**
 * @name \ChangeTests\Change\Mail\TestAssets\FakeMailManager
 */
class FakeMailManager extends MailManager
{

	public function prepareFakeMail($message)
	{
		return parent::prepareFakeMail($message);
	}

}