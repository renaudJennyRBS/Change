<?php
namespace Change\Documents\Traits;

use Change\Documents\DocumentManager;
use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Change\Documents\Traits\DbStorage
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getPersistentState()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Zend\EventManager\EventManagerInterface getEventManager()
 * @method \Change\Application\ApplicationServices getApplicationServices()
 */
trait DbStorage
{
    /**
     * Load properties
     * @api
     */
    public function load()
    {
        if ($this->getPersistentState() === DocumentManager::STATE_INITIALIZED)
        {
            $this->doLoad();

            $event = new DocumentEvent(DocumentEvent::EVENT_LOADED, $this);
            $this->getEventManager()->trigger($event);
        }
    }

    /**
     * @throws \Exception
     * @return void
     */
    protected function doLoad()
    {
        $this->getDocumentManager()->loadDocument($this);
        $callable = array($this, 'onLoad');
        if (is_callable($callable))
        {
            call_user_func($callable);
        }
    }

    /**
     * @api
     */
    public function save()
    {
        if ($this->getPersistentState() === DocumentManager::STATE_NEW)
        {
            $this->create();
        }
        else
        {
            $this->update();
        }
    }


    /**
     * @api
     */
    public function create()
    {
        if ($this->getPersistentState() !== DocumentManager::STATE_NEW)
        {
            throw new \RuntimeException('Document is not new', 51001);
        }

        $callable = array($this, 'onCreate');
        if (is_callable($callable))
        {
            call_user_func($callable);
        }
        $event = new DocumentEvent(DocumentEvent::EVENT_CREATE, $this);
        $this->getEventManager()->trigger($event);

        $propertiesErrors = $event->getParam('propertiesErrors');
        if (is_array($propertiesErrors) && count($propertiesErrors))
        {
            $e = new \RuntimeException('Document is not valid', 52000);
            $e->propertiesErrors = $propertiesErrors;
            throw $e;
        }

        $tm = $this->getApplicationServices()->getTransactionManager();
        try
        {
            $tm->begin();
            $this->doCreate();
            $tm->commit();
        }
        catch (\Exception $e)
        {
            throw $tm->rollBack($e);
        }

        $event = new DocumentEvent(DocumentEvent::EVENT_CREATED, $this);
        $this->getEventManager()->trigger($event);
    }

    /**
     * @throws \Exception
     * @return void
     */
    protected function doCreate()
    {
        $dm = $this->getDocumentManager();
        $dm->affectId($this);
        $dm->insertDocument($this);

        if ($this instanceof Localizable)
        {
            $dm->insertLocalizedDocument($this, $this->getCurrentLocalizedPart());
        }
    }

    /**
     * @api
     */
    public function update()
    {
        if ($this->getPersistentState() === DocumentManager::STATE_NEW)
        {
            throw new \RuntimeException('Document is new', 51002);
        }

        $callable = array($this, 'onUpdate');
        if (is_callable($callable))
        {
            call_user_func($callable);
        }


        $event = new DocumentEvent(DocumentEvent::EVENT_UPDATE, $this);
        $this->getEventManager()->trigger($event);

        $propertiesErrors = $event->getParam('propertiesErrors');
        if (is_array($propertiesErrors) && count($propertiesErrors))
        {
            $e = new \RuntimeException('Document is not valid', 52000);
            $e->propertiesErrors = $propertiesErrors;
            throw $e;
        }

        $modifiedPropertyNames = $this->getModifiedPropertyNames();

        $tm = $this->getApplicationServices()->getTransactionManager();
        try
        {
            $tm->begin();
            $this->doUpdate();
            $tm->commit();
        }
        catch (\Exception $e)
        {
            throw $tm->rollBack($e);
        }

        $event = new DocumentEvent(DocumentEvent::EVENT_UPDATED, $this, array('modifiedPropertyNames' => $modifiedPropertyNames));
        $this->getEventManager()->trigger($event);
    }

    /**
     * @throws \Exception
     * @return void
     */
    protected function doUpdate()
    {
        if ($this->getDocumentModel()->useCorrection())
        {
            $corrections = $this->getCorrectionFunctions()->extractCorrections();
        }
        else
        {
            $corrections = array();
        }

        $dm = $this->getDocumentManager();
        if (count($corrections))
        {
            $cleanUpPropertiesNames = array();
            foreach ($corrections as $correction)
            {
                /* @var $correction \Change\Documents\Correction */
                $this->getCorrectionFunctions()->save($correction);
                $cleanUpPropertiesNames = array_merge($cleanUpPropertiesNames, $correction->getPropertiesNames());
            }

            foreach (array_unique($cleanUpPropertiesNames) as $propertyName)
            {
                $this->removeOldPropertyValue($propertyName);
            }

        }

        if ($this->hasModifiedProperties() || count($corrections))
        {
            $this->setModificationDate(new \DateTime());
            if ($this instanceof Editable)
            {
                $this->nextDocumentVersion();
            }

            if ($this->hasNonLocalizedModifiedProperties())
            {
                $dm->updateDocument($this);
            }

            if ($this instanceof Localizable)
            {
                $localizedPart = $this->getCurrentLocalizedPart();
                if ($localizedPart->hasModifiedProperties())
                {
                    $dm->updateLocalizedDocument($this, $localizedPart);
                }
            }
        }
    }

    /**
     * @api
     */
    public function delete()
    {
        //Already deleted
        if ($this->getPersistentState() === DocumentManager::STATE_DELETED ||
            $this->getPersistentState() === DocumentManager::STATE_DELETING)
        {
            return;
        }

        if ($this->getPersistentState() === DocumentManager::STATE_NEW )
        {
            throw new \RuntimeException('Document is new', 51002);
        }

        $callable = array($this, 'onDelete');
        if (is_callable($callable))
        {
            call_user_func($callable);
        }
        $event = new DocumentEvent(DocumentEvent::EVENT_DELETE, $this);
        $this->getEventManager()->trigger($event);

        $tm = $this->getApplicationServices()->getTransactionManager();
        try
        {
            $tm->begin();
            $this->doDelete();
            $tm->commit();
        }
        catch (\Exception $e)
        {
            throw $tm->rollBack($e);
        }

        $event = new DocumentEvent(DocumentEvent::EVENT_DELETED, $this);
        $this->getEventManager()->trigger($event);
    }

    /**
     * @throws \Exception
     */
    protected function doDelete()
    {
        $dm = $this->getDocumentManager();
        $dm->deleteDocument($this);

        if ($this instanceof Localizable)
        {
            $dm->deleteLocalizedDocuments($this);
        }
    }

    // Metadata management

    /**
     * @var array<String,String|String[]>
     */
    private $metas;

    /**
     * @var boolean
     */
    private $modifiedMetas = false;

    /**
     * @api
     */
    public function saveMetas()
    {
        if ($this->modifiedMetas)
        {
            $this->getDocumentManager()->saveMetas($this, $this->metas);
            $this->modifiedMetas = false;
        }
    }

    /**
     * @return void
     */
    protected function checkMetasLoaded()
    {
        if ($this->metas === null)
        {
            $this->metas = $this->getDocumentManager()->loadMetas($this);
            $this->modifiedMetas = false;
        }
    }

    /**
     * @api
     * @return boolean
     */
    public function hasModifiedMetas()
    {
        return $this->modifiedMetas;
    }

    /**
     * @api
     * @return array
     */
    public function getMetas()
    {
        $this->checkMetasLoaded();
        return $this->metas;
    }

    /**
     * @api
     * @param array $metas
     */
    public function setMetas($metas)
    {
        $this->checkMetasLoaded();
        if (count($this->metas))
        {
            $this->metas = array();
            $this->modifiedMetas = true;
        }
        if (is_array($metas))
        {
            foreach ($metas as $name => $value)
            {
                $this->metas[$name] = $value;
            }
            $this->modifiedMetas = true;
        }
    }

    /**
     * @api
     * @param string $name
     * @return boolean
     */
    public function hasMeta($name)
    {
        $this->checkMetasLoaded();
        return isset($this->metas[$name]);
    }

    /**
     * @api
     * @param string $name
     * @return mixed
     */
    public function getMeta($name)
    {
        $this->checkMetasLoaded();
        return isset($this->metas[$name]) ? $this->metas[$name] : null;
    }

    /**
     * @api
     * @param string $name
     * @param mixed|null $value
     */
    public function setMeta($name, $value)
    {
        $this->checkMetasLoaded();
        if ($value === null)
        {
            if (isset($this->metas[$name]))
            {
                unset($this->metas[$name]);
                $this->modifiedMetas = true;
            }
        }
        elseif (!isset($this->metas[$name]) || $this->metas[$name] != $value)
        {
            $this->metas[$name] = $value;
            $this->modifiedMetas = true;
        }
    }
}