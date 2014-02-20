<?php
namespace Rbs\Commerce\Process;

/**
 * @name \Rbs\Commerce\Process\ModifierInterface
 */
interface ModifierInterface
{
	/**
	 * @return boolean
	 */
	public function apply();
} 