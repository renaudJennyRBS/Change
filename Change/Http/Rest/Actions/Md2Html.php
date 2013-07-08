<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Event;
use Change\Http\Request;
use Change\Presentation\Markdown\MarkdownParser;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\Md2Html
 */
class Md2Html
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();

		if ($request->isPost()) {
			$md = $request->getContent();
		} elseif ($request->isGet()) {
			$md = $request->getQuery('md');
		} else {
			$resolver = $event->getController()->getActionResolver();
			if ($resolver instanceof \Change\Http\Rest\Resolver)
			{
				$resolver->buildNotAllowedError($request->getMethod(), array(Request::METHOD_GET));
			}
			return;
		}

		$mdParser = new MarkdownParser($event->getDocumentServices());
		$event->setResult($this->generateResult($md, $mdParser->transform($md)));
	}

	/**
	 * @param string $md
	 * @param string $html
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($md, $html)
	{
		$result = new \Change\Http\Rest\Result\ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setArray(array('html' => $html));
		return $result;
	}
}