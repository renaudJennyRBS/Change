<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Commands\Events;

/**
 * @name \Change\Commands\Events\RestCommandResponse
 */
class RestCommandResponse implements CommandResponseInterface
{

	protected $comment;

	protected $info;

	protected $warning;

	protected $error;

	protected $data;

	public function addCommentMessage($message)
	{
		if ($this->comment === null)
		{
			$this->comment = array();
		}
		$this->comment[] = $message;
		return $this;
	}

	public function addInfoMessage($message)
	{
		if ($this->info === null)
		{
			$this->info = array();
		}
		$this->info[] = $message;
		return $this;
	}

	public function addWarningMessage($message)
	{
		if ($this->warning === null)
		{
			$this->warning = array();
		}
		$this->warning[] = $message;
		return $this;
	}

	public function addErrorMessage($message)
	{
		if ($this->error === null)
		{
			$this->error = array();
		}
		$this->error[] = $message;
		return $this;
	}

	public function setData($data)
	{
		$this->data = $data;
		return $this;
	}

	public function toArray()
	{
		$arrayResult = array();

		if ($this->comment !== null)
		{
			$arrayResult['comment'] = $this->comment;
		}

		if ($this->info !== null)
		{
			$arrayResult['info'] = $this->info;
		}

		if ($this->warning !== null)
		{
			$arrayResult['warning'] = $this->warning;
		}

		if ($this->error !== null)
		{
			$arrayResult['error'] = $this->error;
		}

		if ($this->data !== null)
		{
			$arrayResult['data'] = $this->data;
		}

		return $arrayResult;
	}
}