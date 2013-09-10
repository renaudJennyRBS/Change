<?php
namespace Rbs\Review\Documents;

/**
 * @name \Rbs\Comment\Documents\Review
 */
class Review extends \Compilation\Rbs\Review\Documents\Review
{
	public function getLabel()
	{
		if (!parent::getLabel())
		{
			//TODO do something right
			return ' ';
		}
		return parent::getLabel();
	}
}
