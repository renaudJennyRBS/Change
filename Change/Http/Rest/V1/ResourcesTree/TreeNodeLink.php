<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\ResourcesTree;

use Change\Http\Rest\V1\Link;
use Change\Http\Rest\V1\Resources\DocumentLink;

/**
 * @name \Change\Http\Rest\V1\ResourcesTree\TreeNodeLink
 */
class TreeNodeLink extends Link
{
	const MODE_LINK = 'link';
	const MODE_PROPERTY = 'property';

	/**
	 * @var string
	 */
	protected $mode;

	/**
	 * @var \Change\Documents\TreeNode
	 */
	protected $treeNode;

	/**
	 * @var DocumentLink
	 */
	protected $documentLink;

	/**
	 * @return \Change\Http\Rest\V1\Resources\DocumentLink
	 */
	public function getDocumentLink()
	{
		return $this->documentLink;
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 * @param \Change\Documents\TreeNode $treeNode
	 * @param string $mode
	 * @param array $extraColumn
	 */
	public function __construct(\Change\Http\UrlManager $urlManager, \Change\Documents\TreeNode $treeNode, $mode = self::MODE_PROPERTY, $extraColumn = array())
	{
		$this->treeNode = $treeNode;
		$this->mode = $mode;
		if ($mode === static::MODE_PROPERTY)
		{
			$this->documentLink = new DocumentLink($urlManager, $treeNode->getDocument(), DocumentLink::MODE_PROPERTY, $extraColumn);
		}
		parent::__construct($urlManager, $this->buildPathInfo());
	}


	protected function buildPathInfo()
	{
		list($vendor, $shortModuleName) = explode('_', $this->treeNode->getTreeName());
		$path = 'resourcestree/' . $vendor . '/' . $shortModuleName .  $this->treeNode->getPath() . $this->treeNode->getDocumentId();
		return $path;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		if ($this->mode === static::MODE_PROPERTY)
		{
			$treeNode = $this->treeNode;
			$result = array('id' => $treeNode->getDocumentId(),
				'childrenCount' => $treeNode->getChildrenCount(),
				'level' => $treeNode->getLevel(),
				'nodeOrder' => $treeNode->getPosition());
			$result['link'] = parent::toArray();
			$result['document'] = $this->documentLink->toArray();
			return $result;
		}
		return parent::toArray();
	}
}