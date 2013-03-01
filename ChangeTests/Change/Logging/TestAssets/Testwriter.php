<?php
namespace Tests\Change\Logging\TestAssets;

/**
 * @name \Tests\Change\Logging\TestAssets\Testwriter
 */
class Testwriter extends \Zend\Log\Writer\AbstractWriter
{
	/**
	 * @var string
	 */
	protected $messages = array();
	
	/**
	 * @return string
	 */
	public function shiftMessage()
	{
		return array_shift($this->messages);
	}
	
	/**
	 * @return integer
	 */
	public function clearMessages()
	{
		$this->messages = array();
	}
	
	/**
	 * @return integer
	 */
	public function getMessageCount()
	{
		return count($this->messages);
	}
	
	/**
	 * @return Stream
	 * @throws Exception\InvalidArgumentException
	 * @throws Exception\RuntimeException
	 */
	public function __construct()
	{
		if($this->formatter === null) 
		{
			$this->formatter = new \Zend\Log\Formatter\Simple();
		}
	}
	
	/**
	 * Write a message to the log.
	 *
	 * @param array $event event data
	 * @return void
	 * @throws Exception\RuntimeException
	 */
	protected function doWrite(array $event)
	{
		$this->messages[] = $this->formatter->format($event);
	}
}