<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\ErrorResult
 */
class ErrorResult extends \Change\Http\Result
{
	/**
	 * @param string|\Exception $errorCode
	 * @param string $errorMessage
	 * @param integer $httpStatusCode
	 */
	public function __construct($errorCode = null, $errorMessage = null, $httpStatusCode = \Zend\Http\Response::STATUS_CODE_500)
	{
		if ($errorCode instanceof \Exception)
		{
			if ($errorMessage === null)
			{
				$errorMessage = $errorCode->getMessage();
			}

			if (isset($errorCode->httpStatusCode))
			{
				$httpStatusCode = $errorCode->httpStatusCode;
			}
			$errorCode = $errorCode->getCode() ? 'EXCEPTION-' . $errorCode->getCode() : 'EXCEPTION';
		}

		$this->setHttpStatusCode($httpStatusCode);
		$this->errorCode = $errorCode;
		$this->errorMessage = $errorMessage;
	}

	/**
	 * @var string
	 */
	protected $errorCode;

	/**
	 * @var string
	 */
	protected $errorMessage;

	/**
	 * @param string $errorCode
	 */
	public function setErrorCode($errorCode)
	{
		$this->errorCode = $errorCode;
	}

	/**
	 * @return string
	 */
	public function getErrorCode()
	{
		return $this->errorCode;
	}

	/**
	 * @param string $errorMessage
	 */
	public function setErrorMessage($errorMessage)
	{
		$this->errorMessage = $errorMessage;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return array('code' => $this->getErrorCode(), 'message' => $this->getErrorMessage());
	}
}