<?php
namespace Change\Http\Rest;

use Zend\Stdlib\Parameters;

/**
 * @name \Change\Http\Request\Rest
 */
class Request extends \Change\Http\Request
{
	public function __construct()
	{
		parent::__construct();
		if (in_array($this->getMethod(), array('PUT', 'POST')))
		{
			try
			{
				$h = $this->getHeaders('Content-Type');
			}
			catch (\Exception $e)
			{
				//Header not found
				return;
			}

			if ($h && ($h instanceof \Zend\Http\Header\ContentType))
			{
				if (strpos($h->getFieldValue(), 'application/json') === 0)
				{
					$string = file_get_contents('php://input');

					$data = json_decode($string, true);
					if (JSON_ERROR_NONE === json_last_error())
					{
						if (is_array($data))
						{
							$this->setPost(new Parameters($data));
						}
					}
				}
			}
		}
	}
}