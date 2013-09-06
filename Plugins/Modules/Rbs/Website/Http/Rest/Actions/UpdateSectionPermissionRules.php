<?php
namespace Rbs\Website\Http\Rest\Actions;

/**
 * @name \Rbs\Website\Http\Rest\Actions\UpdateSectionPermissionRules
 */
class UpdateSectionPermissionRules
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		if ($request->isPost())
		{
			$sectionId = $request->getPost('sectionId');
			$websiteId = $request->getPost('websiteId');
			$users = $request->getPost('users', []);
			$groups = $request->getPost('groups', []);
			$permissionManager = $event->getPermissionsManager();
			$existingAccessorIds = $permissionManager->getSectionAccessorIds($sectionId, $websiteId);
			$keepAccessorIds = [];
			foreach ($users as $user)
			{
				if (!in_array($user['id'], $existingAccessorIds))
				{
					$permissionManager->addWebRule($sectionId, $websiteId, $user['id']);
				}
				$keepAccessorIds[] = $user['id'];
			}
			foreach ($groups as $group)
			{
				if (!in_array($group['id'], $existingAccessorIds))
				{
					$permissionManager->addWebRule($sectionId, $websiteId, $group['id']);
				}
				$keepAccessorIds[] = $group['id'];
			}
			//diff between this two array mean accessor id you want to drop
			$accessorIdsToDrop = array_diff($existingAccessorIds, $keepAccessorIds);
			foreach ($accessorIdsToDrop as $accessorIdToDrop)
			{
				$permissionManager->deleteWebRule($sectionId, $websiteId, $accessorIdToDrop);
			}
			//if no rule still exist for this section, take the parent one
			if (count($permissionManager->getSectionAccessorIds($sectionId, $websiteId)) === 0)
			{
				$section = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($sectionId);
				/* @var $section \Rbs\Website\Documents\Section */
				$sectionPath = $section->getSectionPath();
				//parent section is in the second position, pop twice
				array_pop($sectionPath);
				$parentSection = array_pop($sectionPath);
				if ($parentSection)
				{
					/* @var $parentSection \Rbs\Website\Documents\Section */
					$parentPermissionRuleAccessorIds = $permissionManager->getSectionAccessorIds($parentSection->getId(), $parentSection->getWebsite()->getId());
					foreach ($parentPermissionRuleAccessorIds as $parentPermissionRuleAccessorId)
					{
						$permissionManager->addWebRule($sectionId, $websiteId, $parentPermissionRuleAccessorId);
					}
				}
				else
				{
					//our section hasn't parent section, so set full access
					$permissionManager->addWebRule($sectionId, $websiteId);
				}
			}
			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			$event->setResult($result);
		}
	}
}