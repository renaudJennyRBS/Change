<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Planning;

/**
 * @name \Rbs\Storeshipping\Planning\OpeningHoursDay
 */
class OpeningHoursDay
{
	/**
	 * @var string
	 */
	protected $date;


	/**
	 * @var string
	 */
	protected $amBeginInterval;

	/**
	 * @var string
	 */
	protected $amEndInterval;

	/**
	 * @var string
	 */
	protected $pmBeginInterval;

	/**
	 * @var string
	 */
	protected $pmEndInterval;


	/**
	 * @return \DateTime
	 */
	public function getDateTime()
	{
		if (!$this->date)
		{
			$this->setDateTime(new \DateTime());
		}
		return new \DateTime($this->date);
	}

	/**
	 * @param \DateTime $date
	 * @return $this
	 */
	public function setDateTime(\DateTime $date)
	{
		$this->date = $date->format('Y-m-d') . 'T00:00:00+0000';
		return $this;
	}

	public function setOpeningHours($amBegin, $amEnd, $pmBegin, $pmEnd)
	{
		$this->amBeginInterval = $this->makeInterval($amBegin);
		$this->amEndInterval = $this->makeInterval($amEnd);
		$this->pmBeginInterval = $this->makeInterval($pmBegin);
		$this->pmEndInterval = $this->makeInterval($pmEnd);

		if ($this->amEndInterval && !$this->amBeginInterval)
		{
			$this->amEndInterval = null;
		}

		if ($this->pmBeginInterval && !$this->pmEndInterval)
		{
			$this->pmBeginInterval = null;
		}

		if ($this->amBeginInterval && !$this->amEndInterval && !$this->pmEndInterval)
		{
			$this->amBeginInterval = null;
		}
		if ($this->pmEndInterval && !$this->amBeginInterval && !$this->pmBeginInterval)
		{
			$this->pmEndInterval = null;
		}

		return $this;
	}

	/**
	 * @param string $time
	 * @return null|string
	 */
	protected function makeInterval($time)
	{
		if (is_string($time))
		{
			$a = explode(':', $time);
			if (count($a) == 2 && is_numeric($a[0]) && is_numeric($a[1]))
			{
				$hours = intval($a[0]);
				$minutes = intval($a[1]);
				if ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59)
				{
					return 'PT' . $hours .'H'. $minutes . 'M';
				}
			}
		}
		return null;
	}

	/**
	 * @return boolean
	 */
	public function isClosed()
	{
		return $this->amBeginInterval == null && $this->pmBeginInterval == null;
	}

	/**
	 * @return boolean
	 */
	public function isContinuousDay()
	{
		return ($this->amEndInterval == null) || ($this->pmBeginInterval == null);
	}

	/**
	 * @return \DateTime|null
	 */
	public function getAmBegin()
	{
		if ($this->amBeginInterval)
		{
			return $this->getDateTime()->add(new \DateInterval($this->amBeginInterval));
		}
		return null;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getPmBegin()
	{
		if ($this->pmBeginInterval)
		{
			return $this->getDateTime()->add(new \DateInterval($this->pmBeginInterval));
		}
		return null;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getBegin()
	{
		$begin = $this->getAmBegin();
		if (!$begin)
		{
			$begin = $this->getPmBegin();
		}
		return $begin;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getAmEnd()
	{
		if ($this->amEndInterval)
		{
			return $this->getDateTime()->add(new \DateInterval($this->amEndInterval));
		}
		return null;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getPmEnd()
	{
		if ($this->pmEndInterval)
		{
			return $this->getDateTime()->add(new \DateInterval($this->pmEndInterval));
		}
		return null;
	}

	/**
	 * @return \DateInterval|null
	 */
	public function getEnd()
	{
		$end = $this->getPmEnd();
		if (!$end)
		{
			$end = $this->getAmEnd();
		}
		return $end;
	}

	/**
	 * @param \DateTime $afterTime
	 * @return \DateTime|null
	 */
	protected function getNextOpenTime(\DateTime $afterTime)
	{

		$e = $this->getEnd();
		if ($afterTime < $e)
		{

			$b = $this->getBegin();
			if ($afterTime <= $b)
			{
				return $b;
			}
			elseif (!$this->isContinuousDay())
			{
				$b = $this->getPmBegin();
				if ($afterTime < $b)
				{
					if ($afterTime >= $this->getAmEnd())
					{
						return $b;
					}
				}
			}
			return $afterTime;
		}
		return null;
	}

	/**
	 * @param \DateTime $afterTime
	 * @param \DateInterval $offset
	 * @return \DateTime|null
	 */
	public function getOpenDateTime(\DateTime $afterTime = null, \DateInterval $offset = null)
	{
		if (!$this->isClosed())
		{
			if ($afterTime)
			{
				$afterTime = new \DateTime($afterTime->format('Y-m-d H:i:s+0000'));
				$this->setDateTime($afterTime);
			}
			else
			{
				$this->setDateTime(new \DateTime());
				$afterTime = $this->getDateTime();
			}

			$nextOpenTime = $this->getNextOpenTime($afterTime);
			if ($nextOpenTime)
			{
				if ($offset)
				{
					$offsetOpenTime = clone($nextOpenTime);
					$offsetOpenTime->add($offset);

					$nextOpenTime = $this->getNextOpenTime($offsetOpenTime);
					if ($nextOpenTime && $nextOpenTime != $offsetOpenTime)
					{
						$nextOpenTime->add($offset);
						if ($nextOpenTime >= $this->getEnd())
						{
							return null;
						}
					}
				}
			}
			return $nextOpenTime;
		}
		return null;
	}
}