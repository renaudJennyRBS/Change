<?php
namespace Rbs\Catalog\Product;

/**
* @name \Rbs\Catalog\Product\AxisConfiguration
*/
class AxisConfiguration
{
	/**
	 * @var integer
	 */
	protected $id = 0;

	/**
	 * @var boolean
	 */
	protected $url = false;

	/**
	 * @var boolean
	 */
	protected $categorizable = false;

	/**
	 * @var \Rbs\Catalog\Documents\Attribute|null
	 */
	protected $attribute;

	/**
	 * @param integer $id
	 * @param boolean $url
	 * @param boolean $categorizable
	 */
	function __construct($id = 0, $url = false, $categorizable = false)
	{
		$this->setId($id);
		$this->setUrl($url);
		$this->setCategorizable($categorizable);
	}

	/**
	 * @param integer $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = intval($id);
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param boolean $url
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = ($url == true);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param boolean $categorizable
	 * @return $this
	 */
	public function setCategorizable($categorizable)
	{
		$this->categorizable = ($categorizable == true);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getCategorizable()
	{
		return $this->url ? $this->categorizable : false;
	}

	/**
	 * @param null|\Rbs\Catalog\Documents\Attribute $attribute
	 * @return $this
	 */
	public function setAttribute($attribute)
	{
		$this->attribute = $attribute;
		return $this;
	}

	/**
	 * @return null|\Rbs\Catalog\Documents\Attribute
	 */
	public function getAttribute()
	{
		return $this->attribute;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		if (isset($array['id']))
		{
			$this->setId($array['id']);
		}

		if (isset($array['url']))
		{
			$this->setUrl($array['url']);
		}

		if (isset($array['categorizable']))
		{
			$this->setCategorizable($array['categorizable']);
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return ['id' => $this->getId(), 'url' => $this->getUrl(), 'categorizable' => $this->getCategorizable()];
	}
}