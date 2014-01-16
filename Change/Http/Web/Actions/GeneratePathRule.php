<?php
namespace Change\Http\Web\Actions;

use Change\Documents\AbstractDocument;
use Change\Http\Web\PathRule;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\GeneratePathRule
 */
class GeneratePathRule
{
	/**
	 * Use Required Event Params: pathRule
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function execute($event)
	{
		/* @var $pathRule PathRule */
		$pathRule = $event->getPathRule();
		if (!($pathRule instanceof PathRule))
		{
			throw new \RuntimeException('Invalid Parameter: pathRule', 71000);
		}

		/* @var $urlManager \Change\Http\Web\UrlManager */
		$urlManager = $event->getUrlManager();
		$document = $event->getDocument();
		if ($document instanceof AbstractDocument)
		{
			$transactionManager = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$transactionManager->begin();
				$newPathRule = $this->rewritePathRule($event->getApplicationServices()->getPathRuleManager(), $document,
					$pathRule);
				$transactionManager->commit();
			}
			catch (\Exception $exception)
			{
				throw $transactionManager->rollBack($exception);
			}

			if ($newPathRule === null)
			{
				$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_200);
				$action = new DisplayDocument();
				$action->execute($event);
			}
			else
			{
				$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_301);
				$urlManager->setAbsoluteUrl(true);
				$queryParameters = $pathRule->getQueryParameters();

				//Remove parameters stored in new pathRule
				$removeQueryParameters = $newPathRule->getQueryParameters();
				if (count($removeQueryParameters))
				{
					foreach ($removeQueryParameters as $name => $value)
					{
						if (isset($queryParameters[$name]) && $queryParameters[$name] == $value)
						{
							unset($queryParameters[$name]);
						}
					}
				}
				$uri = $urlManager->getByPathInfo($newPathRule->getRelativePath(), $queryParameters);
				$pathRule->setLocation($uri->normalize()->toString());
				$action = new RedirectPathRule();
				$action->execute($event);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\Web\PathRule $genericPathRule
	 * @throws
	 * @return \Change\Http\Web\PathRule|null
	 */
	public function rewritePathRule($pathRuleManager, $document, $genericPathRule)
	{
		$newPathRule = $pathRuleManager->populatePathRuleByDocument($genericPathRule, $document);
		if ($newPathRule && $newPathRule->getRelativePath())
		{
			$existingRules = $pathRuleManager->findPathRules($newPathRule->getWebsiteId(), $newPathRule->getLCID(),
				$newPathRule->getDocumentId(), $newPathRule->getSectionId());
			if (count($existingRules))
			{
				return $existingRules[0];
			}

			$redirectRules = $pathRuleManager->findRedirectedRules(
				$newPathRule->getWebsiteId(), $newPathRule->getLCID(),
				$newPathRule->getDocumentId(), $newPathRule->getSectionId());

			$redirectRule = null;
			foreach ($redirectRules as $rule)
			{
				if ($rule->getRelativePath() === $newPathRule->getRelativePath())
				{
					$rule->setQuery($newPathRule->getQuery());
					$rule->setHttpStatus(200);
					$pathRuleManager->updatePathRule($rule);
					return $rule;
				}
			}

			try
			{
				$pathRuleManager->insertPathRule($newPathRule);
			}
			catch (\Exception $pke)
			{
				$newPathRule->setRelativePath($document->getId() . '/' . $newPathRule->getRelativePath());
				$pathRuleManager->insertPathRule($newPathRule);
			}

			return $newPathRule;
		}
		return null;
	}
}
