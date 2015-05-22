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
        $application  = $this->container->getParameter('mesd_crud_history_app_name');
        $authUserId = null;
        $class = null;
        $method = null;
        $modified = new \DateTime();
        $entityManager = $this->container->get('doctrine')->getManager($this->container->getParameter('mesd_crud_history.entity_manager'));
        $unitOfWork = $entityManager->getUnitOfWork();
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();
        if (is_object($token)) {
            $authUser = $token->getUser();
            if (is_object($authUser)) {
                $authUserId = $authUser->getId();
            }
        }

        $class = null;
        $trace = debug_backtrace();
        foreach ($trace as $caller) {
            // caller['class'] is the class changing the record
            if (isset($caller['class'])) {
               $loopClass = $caller['class'];
                // the following list is a collection of classes
                // we expect to be likely to be effecting change
                //
                // This list should be expanded if needs change
                //

                $likelyList = array("Command","Controller","Fixtures");
                foreach ($likelyList as $key => $likelylistItem){
                    if (stripos($loopClass, $likelylistItem)){
                        $class = $caller['class'];
                        $method = $caller['function'];
                        break;
                    }
                }

                if (!$class) {
                    $whiteList = $this->container->getParameter('mesd_crud_history.bundle_whitelist');
                    $whiteList = array_map(function($w) { return str_replace('_', '\\',$w);}, $whiteList);
                    foreach ($whiteList as $key => $whitelistItem){
                        if (stripos($loopClass, $whitelistItem) !== false){
                            $class = $caller['class'];
                            $method = $caller['function'];
                            break;
                        }
                    }
                }

            }
        }

        if (!$class)  { $class = 'Unknown/Unclear';}
        if (!$method) { $method = 'Unknown/Unclear';}

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
            foreach ($unitOfWork->getScheduledEntityUpdates() as $entityKey => $entity) {
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

            foreach ($unitOfWork->getScheduledEntityDeletions() as $entityKey => $entity) {
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
                    if (isset($change['changeset']['entity'])) {
                        $entityChangeSet = $change['changeset']['entity'];
                        foreach ($entityChangeSet as $entityChangeSetKey => $entityChangeSetValue) {
                            // var_dump($entityChangeSetKey);
                            // var_dump($entityChangeSetValue);
                            if ('object' == gettype($entityChangeSetValue[0]) && 'DateTime' != get_class($entityChangeSetValue[0]) ) {
                                $entityChangeSet[$entityChangeSetKey][0] = $entityChangeSet[$entityChangeSetKey][0]->getId();
                                $entityChangeSet[$entityChangeSetKey][1] = $entityChangeSet[$entityChangeSetKey][1]->getId();
                            }
                        }
                    }
                    $change['changeset']['entity'] = $entityChangeSet;
                    $crudHistory->setChanges(json_encode($change['changeset'], JSON_NUMERIC_CHECK));
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
            var_dump($key);
            $changes[$key] = $change[1];
        }
        return $changes;
    }
    public function getCollectionChanges($collection)
    {
        $changes = array();
        foreach ($collection as $entity) {
            if (!method_exists($entity, "getId")) {
                throw new \Exception('Crud Listener called on entity without getId() method.');
            }
            $value = strval($entity->getId());
            $changes[$value] = $value;
        }
        return $changes;
    }
}
