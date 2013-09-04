<?php
namespace Rbs\Commerce\Cart;

/**
* @name \Rbs\Commerce\Cart\CartError
*/
class CartError implements \Rbs\Commerce\Interfaces\CartError
{
	/**
	 * @var string
	 */
	protected $message;

	/**
	 * @var string
	 */
	protected $lineKey;

	/**
	 * @param string $message
	 * @param string $lineKey
	 */
	function __construct($message, $lineKey = null)
	{
		$this->message = $message;
		$this->lineKey = $lineKey;
	}

	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @return string
	 */
	public function getLineKey()
	{
		return $this->lineKey;
	}

	public function toArray()
	{
		return array('message'=>$this->message, 'lineKey' => $this->lineKey);
	}
}