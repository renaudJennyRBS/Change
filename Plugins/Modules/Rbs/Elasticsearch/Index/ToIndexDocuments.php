<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Index;

/**
 * @name \Rbs\Elasticsearch\Index\ToIndexDocuments
 */
class ToIndexDocuments
{
	protected $toIndex = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	public function start($event)
	{
		if ($event instanceof \Change\Events\Event && $event->getParam('primary'))
		{
			$genericServices = $event->getServices('genericServices');
			if ($genericServices instanceof \Rbs\Generic\GenericServices)
			{
				if (count($genericServices->getIndexManager()->getClientsName()))
				{
					$this->toIndex = [];
				}
			}
		}
	}
	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function indexDocument($event)
	{
		if ($this->toIndex !== null && $event instanceof \Change\Documents\Events\Event)
		{
			$document = $event->getDocument();
			$this->toIndex[] = [
				'LCID' => $event->getApplicationServices()->getDocumentManager()->getLCID(),
				'id' => $document->getId(),
				'model' => $document->getDocumentModelName()
			];
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addJob($event)
	{
		if ($this->toIndex !== null && $event instanceof \Change\Events\Event && $event->getParam('primary'))
		{
			if (count($this->toIndex))
			{
				$jobManager = $event->getApplicationServices()->getJobManager();
				$jobManager->createNewJob('Elasticsearch_Index', $this->toIndex, null, false);
			}
			$this->toIndex = null;
		}
	}
}