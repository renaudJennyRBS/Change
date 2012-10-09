<?php
namespace Change\Configuration;

/**
 * @name \Change\Configuration\OauthCompiler
 */
class OauthCompiler
{
	/**
	 * @param \DOMElement $node
	 * @return array
	 */
	public function getConfigurationArray($node)
	{		
		$path = \Change\Stdlib\Path::compilationPath('Config', 'Oauth', 'Script');
		if (!is_dir($path))
		{
			mkdir($path, 0777, true);
		}
		
		$subNode = $node->getElementsByTagName('consumer'); 
		if ($subNode->length)
		{
			$consumer = $subNode->item(0)->textContent;
			file_put_contents($path . DIRECTORY_SEPARATOR . 'consumer.txt', $consumer);
		}
		else if (!file_exists($path . DIRECTORY_SEPARATOR . 'consumer.txt'))
		{
			$profile = trim(file_get_contents(PROJECT_HOME . '/profile'));
			$consumer = $profile .'#' . $profile;
			file_put_contents($path . DIRECTORY_SEPARATOR . 'consumer.txt', $consumer);
		}
		
		$subNode = $node->getElementsByTagName('token'); 
		if ($subNode->length)
		{
			$token = $subNode->item(0)->textContent;
			file_put_contents($path . DIRECTORY_SEPARATOR . 'token.txt', $token);
		}
		else if (!file_exists($path . DIRECTORY_SEPARATOR . 'token.txt'))
		{
			$ts = time();
			$token = md5($ts . mt_rand()) .'#' . md5($ts . mt_rand());
			file_put_contents($path . DIRECTORY_SEPARATOR . 'token.txt', $token);
		}
		
		return array();
	}
}