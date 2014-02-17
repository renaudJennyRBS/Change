<?php
namespace Rbs\Catalog\Product;

/**
 * @name \Rbs\Catalog\Std\ProductItem
 */
class ProductItem implements \Serializable
{
	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var \Rbs\Catalog\Documents\Product|boolean|null
	 */
	private $product = false;

	/**
	 * @var boolean
	 */
	private $weakRef = false;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param array $data
	 */
	function __construct(array $data = array())
	{
		$this->data = $data;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	public function setData(array $data = array())
	{
		$this->data = $data;
		$this->product = false;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function hasData()
	{
		return count($this->data) > 0;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		$this->documentManager = $documentManager;
		if ($this->weakRef)
		{
			$this->resolveWeakRef();
		}
		return $this;
	}

	protected function resolveWeakRef()
	{
		$this->weakRef = false;
		foreach ($this->data as $k => $v)
		{
			if ($v instanceof \Change\Documents\DocumentWeakReference)
			{
				$this->data[$k] = $v->getDocument($this->documentManager);
			}
		}
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @return integer|null
	 */
	protected function getId()
	{
		return (isset($this->data['id']) && intval($this->data['id']) > 0) ? intval($this->data['id']) : null;
	}

	/**
	 * @return \Rbs\Catalog\Documents\Product|null
	 */
	protected function getProduct()
	{
		if ($this->product === false)
		{
			$this->product = null;
			$productId = $this->getId();
			if ($productId && (null != ($dm = $this->getDocumentManager())))
			{
				$product = $dm->getDocumentInstance($productId);
				if ($product instanceof \Rbs\Catalog\Documents\Product)
				{
					$this->product = $product;
				}
			}
		}
		return $this->product;
	}

	/**
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return mixed
	 */
	public function url($urlManager = null)
	{
		if (!array_key_exists('url', $this->data))
		{
			$this->data['url'] = null;

			$product = $this->getProduct();
			if ($product && $urlManager instanceof \Change\Http\Web\UrlManager)
			{
				$this->data['url'] = $urlManager->getCanonicalByDocument($product)->normalize()->toString();
			}
		}
		return 	$this->data['url'];
	}

	function __call($name, $arguments)
	{
		if (!array_key_exists($name, $this->data))
		{
			$this->data[$name] = null;
			$product = $this->getProduct();
			if ($product)
			{
				$property = $product->getDocumentModel()->getProperty($name);
				if ($property)
				{
					$this->data[$name] = $property->getValue($product);
				}
			}
		}
		return $this->data[$name];
	}

	/**
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		$data = array_map(function($item) {
			return ($item instanceof \Change\Documents\AbstractDocument) ? new \Change\Documents\DocumentWeakReference($item) : $item;
		}, $this->data);
		return serialize($data);
	}

	/**
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->weakRef = true;
		$this->data = unserialize($serialized);
	}
}