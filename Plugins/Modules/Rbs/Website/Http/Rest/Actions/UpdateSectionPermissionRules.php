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
			$restrictions = $request->getPost('restrictions', false);
			$permissionManager = $event->getPermissionsManager();
			if (is_array($restrictions))
			{
				$users = isset($restrictions['users']) ? $restrictions['users'] : [];
				$groups = isset($restrictions['groups']) ? $restrictions['groups'] : [];
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
				$event->getApplicationServices()->getLogging()->fatal(var_export($accessorIdsToDrop, true));
				$permissionManager->deleteWebRules($sectionId, $websiteId, $accessorIdsToDrop);
				//if no rule still exist for this section, copy the parent rules
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
			}
			else
			{
				//if there is no restrictions, first step is to delete old rules
				$permissionManager->deleteWebRules($sectionId, $websiteId);
				//and add the one for all access
				$permissionManager->addWebRule($sectionId, $websiteId);
			}

			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			$event->setResult($result);
		}
	}
}