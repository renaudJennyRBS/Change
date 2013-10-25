<?php
namespace Rbs\Media\Avatar;

/**
 * @name \Rbs\Media\Avatar\Gravatar
 * @api
 */
class Gravatar
{

	/**
	 * URL to gravatar service
	 */
	const GRAVATAR_URL = 'http://www.gravatar.com/avatar';
	/**
	 * Secure URL to gravatar service
	 */
	const GRAVATAR_URL_SECURE = 'https://secure.gravatar.com/avatar';

	/**
	 * Gravatar rating
	 */
	const RATING_G = 'g';
	const RATING_PG = 'pg';
	const RATING_R = 'r';
	const RATING_X = 'x';

	/**
	 * Default gravatar image value constants
	 */
	const DEFAULT_404 = '404';
	const DEFAULT_MM = 'mm';
	const DEFAULT_IDENTICON = 'identicon';
	const DEFAULT_MONSTERID = 'monsterid';
	const DEFAULT_WAVATAR = 'wavatar';

	/**
	 * Email to get Gravatar
	 * @var string
	 */
	protected $email = null;

	/**
	 * Size of image
	 * @var integer
	 */
	protected $size = 80; // Image size in pixel

	/**
	 * Default image
	 * @var string
	 */
	protected $defaultImg = self::DEFAULT_MM; // Default imageset [ 404 | mm | identicon | monsterid | wavatar ]

	/**
	 * Rating
	 * @var string
	 */
	protected $rating = self::RATING_G; // Maximum rating (inclusive) [ g | pg | r | x ]

	/**
	 * Use https url
	 * @var boolean
	 */
	protected $secure = false;

	/**
	 * @api
	 * @param string $email
	 */
	public function __construct($email)
	{
		$this->email = strtolower(trim($email));
	}

	/**
	 * @api
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @api
	 * @param string $defaultImg
	 */
	public function setDefaultImg($defaultImg)
	{
		$this->defaultImg = urlencode($defaultImg);
	}

	/**
	 * @api
	 * @return string
	 */
	public function getDefaultImg()
	{
		return $this->defaultImg;
	}

	/**
	 * @api
	 * @param string $rating
	 */
	public function setRating($rating)
	{
		switch ($rating)
		{
			case self::RATING_G:
			case self::RATING_PG:
			case self::RATING_R:
			case self::RATING_X:
				$this->rating = $rating;
				break;
			default:
				$this->rating = self::RATING_G;
		}
	}

	/**
	 * @api
	 * @return string
	 */
	public function getRating()
	{
		return $this->rating;
	}

	/**
	 * @api
	 * @param integer $size
	 */
	public function setSize($size)
	{
		if ($size < 1 || $size > 2048)
		{
			$size = 80;
		}

		$this->size = intval($size);
	}

	/**
	 * @api
	 * @return integer
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @api
	 * @param boolean $secure
	 */
	public function setSecure($secure)
	{
		$this->secure = $secure;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function getSecure()
	{
		return $this->secure;
	}

	/**
	 * @api
	 * Get the gravatar URL
	 * @return null|string
	 */
	public function getUrl()
	{
		$url = null;

		$email = $this->getEmail();
		if ($email !== null)
		{
			$url = ($this->getSecure() === false) ? self::GRAVATAR_URL : self::GRAVATAR_URL_SECURE;
			$url .= '/'   . md5($email)
				. '?s=' . $this->getSize()
				. '&d=' . $this->getDefaultImg()
				. '&r=' . $this->getRating();
		}

		return $url;
	}
}