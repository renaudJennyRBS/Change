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
 * @name \Change\Commands\Events\ConsoleCommandResponse
 * @see http://symfony.com/doc/current/components/console/introduction.html for the styling options in Symfony console tool.
 */
class ConsoleCommandResponse implements CommandResponseInterface
{
	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	/**
	 * @var boolean
	 */
	protected $hasError;

	/**
	 * @param $output
	 * @return $this
	 */
	public function setOutput($output)
	{
		$this->output = $output;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function hasError()
	{
		return $this->hasError;
	}

	/**
	 * @param $message
	 * @return $this
	 */
	public function addCommentMessage($message)
	{
		$this->output->writeln('<comment>' . $message . '</comment>');
		return $this;
	}

	/**
	 * @param $message
	 * @return $this
	 */
	public function addInfoMessage($message)
	{
		$this->output->writeln('<info>' . $message . '</info>');
		return $this;
	}

	/**
	 * @param $message
	 * @return $this
	 */
	public function addWarningMessage($message)
	{
		$this->output->writeln('<fg=magenta>' . $message . '</fg=magenta>');
		return $this;
	}

	/**
	 * @param $message
	 * @return $this
	 */
	public function addErrorMessage($message)
	{
		$this->hasError = true;
		$this->output->writeln('<error>' . $message . '</error>');
		return $this;
	}

	/**
	 * @param $data
	 */
	public function setData($data)
	{
		// Nothing to do in console
	}
}