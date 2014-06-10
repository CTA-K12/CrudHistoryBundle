<?php

namespace Mesd\CrudHistoryBundle\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Mesd\CrudHistoryBundle\Entity\CrudHistory;
use Mesd\Security\AuthenticationBundle\Entity\AuthUser;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\AuthUsernamePasswordToken;

class CrudLogListener
{

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $globals = $this->container->get('twig')->getGlobals();
        $application = $globals['app_name'];
        $authUserId = null;
        $class = null;
        $method = null;
        $modified = new \DateTime();
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        if (is_object($token)) {
            $authUser = $token->getUser();
            if (is_object($authUser)) {
                $authUserId = $authUser->getId();
            }
        }
        $trace = debug_backtrace();
        foreach ($trace as $caller) {
            if (isset($caller['class'])) {
                if ((strpos($caller['class'], 'Command') !== false)
                    || (strpos($caller['class'], 'Controller') !== false)
                    || (strpos($caller['class'], 'Fixtures') !== false)) {
                    $class = $caller['class'];
                    $method = $caller['function'];
                }
            }
        }

        if (('log' == $this->container->getParameter('mesd_crud_history.log_commands')) || (strpos($class, 'Command') === false)) {
            $changeArray = array();
            $changeArray['application'] = $application;
            $changeArray['authUser'] = $authUserId;
            $changeArray['class'] = $class;
            $changeArray['modified'] = $modified;
            $changeArray['method'] = $method;
            $changeArray['changes'] = array();
            foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
                /*
                $change = array();
                $change['action'] = 'Entity Insertion';
                $change['changeset']['entity'] = $this->getEntityChanges($unitOfWork->getEntityChangeSet($entity));
                $change['changeset']['collections'] = array();
                $change['entityClass'] = get_class($entity);
                $classMetadata = $entityManager->getClassMetadata($change['entityClass']);
                $change['entityId'] = $entity->getId();
                $change['tableName'] = $classMetadata->getTableName();
                $changeArray['changes'][$change['entityClass']][$change['entityId']] = $change;
                */
            }
            foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
                $change = array();
                $change['action'] = 'Entity Update';
                $change['changeset']['entity'] = $unitOfWork->getEntityChangeSet($entity);
                $change['changeset']['collections'] = array();
                $change['entityClass'] = get_class($entity);
                $classMetadata = $entityManager->getClassMetadata($change['entityClass']);
                $change['entityId'] = $entity->getId();
                $change['tableName'] = $classMetadata->getTableName();
                $changeArray['changes'][$change['entityClass']][$change['entityId']] = $change;
            }
            foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
                $change = array();
                $change['action'] = 'Entity Deletion';
                $change['changeset']['entity'] = array();
                $change['changeset']['collections'] = array();
                $change['entityClass'] = get_class($entity);
                $classMetadata = $entityManager->getClassMetadata($change['entityClass']);
                $change['entityId'] = $entity->getId();
                $change['tableName'] = $classMetadata->getTableName();
                $changeArray['changes'][$change['entityClass']][$change['entityId']] = $change;
            }
            foreach ($unitOfWork->getScheduledCollectionDeletions() as $collection) {
                $change = array();
                $change['action'] = 'Collection Deletion';
                $change['changeset'] = $this->getCollectionChanges($collection);
                $entity = $collection->getOwner();
                $change['entityClass'] = get_class($entity);
                $change['entityId'] = strval($entity->getId());
                $mapping = $collection->getMapping();
                $tableName = $mapping['joinTable']['name'];
                $change['tableName'] = $tableName;
                if (!isset($changeArray['changes'][$change['entityClass']][$change['entityId']])) {
                    $changeArray['changes'][$change['entityClass']][$change['entityId']]['action'] = $change['action'];
                    $changeArray['changes'][$change['entityClass']][$change['entityId']]['entityId'] = $change['entityId'];
                    $changeArray['changes'][$change['entityClass']][$change['entityId']]['tableName'] = $change['tableName'];
                }
                $changeArray['changes'][$change['entityClass']][$change['entityId']]['changeset']['collections'][$tableName] = $change;
            }
            foreach ($unitOfWork->getScheduledCollectionUpdates() as $collection) {
                $change = array();
                $change['action'] = 'Collection Update';
                $change['changeset'] = $this->getCollectionChanges($collection);
                $entity = $collection->getOwner();
                $change['entityClass'] = get_class($entity);
                $change['entityId'] = strval($entity->getId());
                $mapping = $collection->getMapping();
                $tableName = $mapping['joinTable']['name'];
                $change['tableName'] = $tableName;
                if (!isset($changeArray['changes'][$change['entityClass']][$change['entityId']])) {
                    $changeArray['changes'][$change['entityClass']][$change['entityId']]['action'] = $change['action'];
                    $changeArray['changes'][$change['entityClass']][$change['entityId']]['entityId'] = $change['entityId'];
                    $changeArray['changes'][$change['entityClass']][$change['entityId']]['tableName'] = $change['tableName'];
                }
                $changeArray['changes'][$change['entityClass']][$change['entityId']]['changeset']['collections'][$tableName] = $change;
            }
            foreach ($changeArray['changes'] as $entityChanges) {
                foreach ($entityChanges as $change) {
                    $crudHistory = new CrudHistory();
                    $crudHistory->setAction($change['action']);
                    $crudHistory->setApplication($changeArray['application']);
                    $crudHistory->setAuthUser($changeArray['authUser']);
                    $crudHistory->setChanges(json_encode($change['changeset']));
                    $crudHistory->setClass($changeArray['class']);
                    $crudHistory->setEntity($change['entityId']);
                    $crudHistory->setMethod($changeArray['method']);
                    $crudHistory->setModified($changeArray['modified']);
                    $crudHistory->setTableName($change['tableName']);
                    $entityManager->persist($crudHistory);
                    $crudHistoryClassMetadata = $entityManager->getClassMetadata(get_class($crudHistory));
                    $unitOfWork->computeChangeSet($crudHistoryClassMetadata, $crudHistory);
                }
            }
        }
    }
    public function getEntityChanges($changeSet)
    {
        $changes = array();
        foreach ($changeSet as $key => $change) {
            $changes[$key] = $change[1];
        }
        return $changes;
    }
    public function getCollectionChanges($collection)
    {
        $changes = array();
        foreach ($collection as $entity) {
            $value = strval($entity->getId());
            $changes[$value] = $value;
        }
        return $changes;
    }
}
