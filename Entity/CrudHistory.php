<?php

namespace Mesd\CrudHistoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CrudHistory
 */
class CrudHistory
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $authUser;

    /**
     * @var \DateTime
     */
    private $modified;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var integer
     */
    private $entity;

    /**
     * @var string
     */
    private $changes;

    /**
     * @var string $application
     */
    private $application;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     * @return CrudHistory
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set class
     *
     * @param string $class
     * @return CrudHistory
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set method
     *
     * @param string $method
     * @return CrudHistory
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return CrudHistory
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set tableName
     *
     * @param string $tableName
     * @return CrudHistory
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Get tableName
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Set entity
     *
     * @param integer $entity
     * @return CrudHistory
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set changes
     *
     * @param string $changes
     * @return CrudHistory
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;

        return $this;
    }

    /**
     * Get changes
     *
     * @return string
     */
    public function getChanges()
    {
        return $this->changes;
    }
    /**
     * @var \Mesd\Security\AuthenticationBundle\Entity\AuthUser
     */

    /**
     * Set authUser
     *
     * @param string $authUser
     * @return CrudHistory
     */
    public function setAuthUser($authUser)
    {
        $this->authUser = $authUser;

        return $this;
    }

    /**
     * Get authUser
     *
     * @return string
     */
    public function getAuthUser()
    {
        return $this->authUser;
    }

    /**
     * Default __toString.  Customize to suit
     */
    public function __toString()
    {
        return (string)$this->getId();
    }


    /**
     * Set application
     *
     * @param string $application
     * @return CrudHistory
     */
    public function setApplication($application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return string
     */
    public function getApplication()
    {
        return $this->application;
    }




















}