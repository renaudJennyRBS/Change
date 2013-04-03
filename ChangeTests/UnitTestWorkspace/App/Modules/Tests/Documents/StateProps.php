<?php
namespace Project\Tests\Documents;

/**
 * @name \Project\Tests\Documents\StateProps
 */
class StateProps extends \Compilation\Project\Tests\Documents\StateProps
{
	/**
	 * @return integer
	 */
	public function getLifeTime()
	{
		$cd = $this->getCreationDate();
		$md =  $this->getModificationDate();
		if ($cd && $md)
		{
			return $md->getTimestamp() - $cd->getTimestamp();
		}
		return null;
	}

	/**
	 * @param integer $lifeTime
	 */
	public function setLifeTime($lifeTime)
	{
		return;
	}
}