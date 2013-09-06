<?php
namespace Rbs\Website\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;

/**
 * @name \Rbs\Website\Http\Rest\Actions\SectionPermissionRules
 */
class SectionPermissionRules
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		if ($request->isGet())
		{
			$sectionId = $request->getQuery('sectionId');
			$websiteId = $request->getQuery('websiteId');
			$permissionManager = $event->getPermissionsManager();
			$users = [];
			$groups = [];
			foreach ($permissionManager->getSectionAccessorIds($sectionId, $websiteId) as $accessorId)
			{
				$accessor = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($accessorId);
				if ($accessor instanceof \Rbs\User\Documents\User)
				{
					$users[] = $accessor->getId();
				}
				else if ($accessor instanceof \Rbs\User\Documents\Group)
				{
					$groups[] = $accessor->getId();
				}
			}
			$result = new ArrayResult();
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			$result->setArray(['userIds' => $users, 'groupIds' => $groups]);
			$event->setResult($result);
		}
	}
}