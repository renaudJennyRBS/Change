<?php
namespace Change\Documents\Traits;


use Change\Documents\DocumentManager;
use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Change\Documents\Traits\Stateless
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getPersistentState()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Zend\EventManager\EventManagerInterface getEventManager()
 * @method \Change\Application\ApplicationServices getApplicationServices()
 * @method string[] getModifiedPropertyNames()
 */
trait Stateless
{
    /**
     * Load properties
     * @api
     */
    public function load()
    {
        if ($this->getPersistentState() === DocumentManager::STATE_INITIALIZED) {
            $this->doLoad();

            $event = new DocumentEvent(DocumentEvent::EVENT_LOADED, $this);
            $this->getEventManager()->trigger($event);
        }
    }

    /**
     * @throws \Exception
     * @return void
     */
    abstract protected function doLoad();

    /**
     * Call create() or update()
     * @api
     */
    public function save()
    {
        if ($this->getPersistentState() === DocumentManager::STATE_NEW) {
            $this->create();
        } else {
            $this->update();
        }
    }

    /**
     * @api
     */
    public function create()
    {
        if ($this->getPersistentState() !== DocumentManager::STATE_NEW) {
            throw new \RuntimeException('Document is not new', 51001);
        }

        $callable = array($this, 'onCreate');
        if (is_callable($callable)) {
            call_user_func($callable);
        }
        $event = new DocumentEvent(DocumentEvent::EVENT_CREATE, $this);
        $this->getEventManager()->trigger($event);

        $propertiesErrors = $event->getParam('propertiesErrors');
        if (is_array($propertiesErrors) && count($propertiesErrors)) {
            $e = new \RuntimeException('Document is not valid', 52000);
            $e->propertiesErrors = $propertiesErrors;
            throw $e;
        }

        $this->doCreate();

        $event = new DocumentEvent(DocumentEvent::EVENT_CREATED, $this);
        $this->getEventManager()->trigger($event);
    }

    /**
     * @throws \Exception
     * @return void
     */
    abstract protected function doCreate();

    /**
     * @api
     */
    public function update()
    {
        if ($this->getPersistentState() === DocumentManager::STATE_NEW) {
            throw new \RuntimeException('Document is new', 51002);
        }

        $callable = array($this, 'onUpdate');
        if (is_callable($callable)) {
            call_user_func($callable);
        }


        $event = new DocumentEvent(DocumentEvent::EVENT_UPDATE, $this);
        $this->getEventManager()->trigger($event);

        $propertiesErrors = $event->getParam('propertiesErrors');
        if (is_array($propertiesErrors) && count($propertiesErrors)) {
            $e = new \RuntimeException('Document is not valid', 52000);
            $e->propertiesErrors = $propertiesErrors;
            throw $e;
        }

        $modifiedPropertyNames = $this->getModifiedPropertyNames();

        $tm = $this->getApplicationServices()->getTransactionManager();
        try {
            $tm->begin();
            $this->doUpdate();
            $tm->commit();
        } catch (\Exception $e) {
            throw $tm->rollBack($e);
        }

        $event = new DocumentEvent(DocumentEvent::EVENT_UPDATED, $this, array('modifiedPropertyNames' => $modifiedPropertyNames));
        $this->getEventManager()->trigger($event);
    }

    /**
     * @throws \Exception
     * @return void
     */
    abstract protected function doUpdate();

    /**
     * @api
     */
    public function delete()
    {
        //Already deleted
        if ($this->getPersistentState() === DocumentManager::STATE_DELETED ||
            $this->getPersistentState() === DocumentManager::STATE_DELETING
        ) {
            return;
        }

        if ($this->getPersistentState() === DocumentManager::STATE_NEW) {
            throw new \RuntimeException('Document is new', 51002);
        }

        $callable = array($this, 'onDelete');
        if (is_callable($callable)) {
            call_user_func($callable);
        }
        $event = new DocumentEvent(DocumentEvent::EVENT_DELETE, $this);
        $this->getEventManager()->trigger($event);

        $tm = $this->getApplicationServices()->getTransactionManager();
        try {
            $tm->begin();
            $this->doDelete();
            $tm->commit();
        } catch (\Exception $e) {
            throw $tm->rollBack($e);
        }

        $event = new DocumentEvent(DocumentEvent::EVENT_DELETED, $this);
        $this->getEventManager()->trigger($event);
    }

    /**
     * @throws \Exception
     */
    abstract protected function doDelete();
}