<?php

/**
 * Copyright (c) 2014-current. TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;
use Core\PrototypeBundle\Event\Event;
use FOS\RestBundle\Controller\FOSRestController;

/**
 * Default controller.
 * 
 * @copyright (c) 2014-current, TMSolution
 */
class DefaultController extends FOSRestController
{

    protected $objectName = null;
    protected $routePrefix = null;
    protected $entityName;
    protected $parentName = null;
    protected $parentId = null;
    protected $routeName;
    protected $configLoaded = false;
    protected $configService;
    protected $states = null;
    protected $dispatcher=null;
    
    //element praktycznie zawsze zmieniany, konfiguracja na zewnątrz
    protected $config = [

            /*
              //twig templates for Elements
              'twig_base_index'=>'CorePrototypeBundle:Default\Base:index.html.twig'
              'twig_base_ajax_index'=>CorePrototypeBundle:Default\Base:ajax_index.html.twig'
              'twig_element_create' => 'CorePrototypeBundle:Default/Element:create.html.twig',
              'twig_element_list' => 'CorePrototypeBundle:Default/Element:list.html.twig',
              'twig_element_ajax_list' => 'CorePrototypeBundle:Default/Element:list.ajax.html.twig',
              'twig_element_update' => 'CorePrototypeBundle:Default/Element:update.html.twig',
              'twig_element_read' => 'CorePrototypeBundle:Default/Element:read.html.twig',
              'twig_element_error' => 'CorePrototypeBundle:Default/Element:error.html.twig',
              //twig templates for Containers
              'twig_container_create' => 'CorePrototypeBundle:Default/Container:create.html.twig',
              'twig_container_list' => 'CorePrototypeBundle:Default/Container:list.html.twig',
              'twig_container_update' => 'CorePrototypeBundle:Default/Container:update.html.twig',
              'twig_container_read' => 'CorePrototypeBundle:Default/Container:read.html.twig',
              'twig_container_error' => 'CorePrototypeBundle:Default/Container:error.html.twig',
             */
    ];

    protected function findParentFieldName($model, $parentEntity)
    {

        $fieldsInfo = $model->getFieldsInfo();

        foreach ($fieldsInfo as $fieldName => $fieldInfo) {
            if ($fieldInfo["is_object"] == true && $fieldInfo["object_name"] == $parentEntity && in_array($fieldInfo["association"], ["ManyToOne", "OneToOne", "ManyToMany"])) {

                return $fieldName;
            }
        }
    }

    protected function getDispatchName($fireAction)
    {
        $routePrefix = $this->getRoutePrefix();
        $entityName = $this->getEntityName();
        $routeParams = $this->getRouteParams();
        
        return $routePrefix . '.' . $routeParams['entityName'] . '.' . $routeParams['actionId'] . '.' . $fireAction;
    }
    
    protected function dispatch($name,$event)
    {
        if(null===$this->dispatcher)
        { 
            $this->dispatcher=$this->get('event_dispatcher');
        }
        $this->dispatcher->dispatch($this->getDispatchName($name), $event);
    }



    protected function addToParent($entity)
    {
        $parentId = $this->get('request')->get('parentId');
        $parentName = $this->get('request')->get('parentName');

        if ($parentId && $parentName) {


            $parentEntityName = $this->getContainer()->get("classmapperservice")->getEntityClass($parentName, $this->get('request')->getLocale());
            $parentModel = $this->getModel($parentEntityName);
            $parentEntity = $parentModel->findOneById($parentId);

            //$field = $this->findParentFieldName($parentModel, $parentEntity);
            //if ($field) {
            $addMethod = 'addPbxRecordFile';

            if ($parentEntity) {
                $parentEntity->$addMethod($entity);
            } else {
                throw new \Exception('Add to parent failure !');
            }
            //} else {
            //  throw new \Exception('Field  $fieldname in parent doesn\'t exists!');
            /// }
        }
    }

    /**
     * Create action.
     * 
     * @return Response

      )
     */
    public function createAction()
    {

        $request = $this->get('request');
        $model = $this->getModel($this->getEntityClass());
        $entity = $model->getEntity();
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();

        $formType = $this->getFormType($this->getEntityClass(), $model);
        $form = $this->makeForm($formType, $entity, 'POST', $entityName, $this->getAction('create'));
        $form->handleRequest($request);

        $routeParams = $this->getRouteParams();

        //config parameters for render and event broadcast
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'entityName' => $this->getEntityName(),
            'form' => $form->createView(),
            'config' => $this->getConfig(),
            'routeParams' => $routeParams,
            'states' => $this->getStates()
        ]);


     
        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);
        $event->setForm($form);


        if ($form->isValid()) {
            
            $this->dispatch('before.create', $event);
            $entity = $model->create($entity, true);
            $this->addToParent($entity);
            $model->flush();
            $this->dispatch('after.create', $event);

            $routeParams['id'] = $entity->getId();
            $view = $this->redirectView($this->generateUrl($routePrefix . '_read', $routeParams), 301);
            return $this->handleView($view);
        }

        //Event broadcast
        $this->dispatch('on.invalidcreate', $event);

        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_create'));
        return $this->handleView($view);
    }

    /**
     * Read action.
     * 
     * @return Response
     * @throws \BadMethodCallException Not implemented yet
     */
    public function listAction()
    {


        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
        $model = $this->getModel($this->getEntityClass());
        $entity = $model->getEntity();
        $queryBuilder = $model->getQueryBuilder('a');
        $query = $queryBuilder->getQuery();
        //query
        //pageNumber
        //limit per page



        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
                $query, $this->get('request')->query->getInt('page', 1)/* page number */, 10/* limit per page */
        );

        //config parameters for render and event broadcast
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'config' => $this->getConfig(),
            'pagination' => $pagination,
            'entityName' => $this->getEntityName(),
        ]);

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);
        //$event->setList($list);
      
        $this->dispatch('on.list', $event);


        //  throw new \BadMethodCallException("Not implemented yet");
        // parameters to template
        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_list'));
        return $this->handleView($view);
    }

    /**
     * Create action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function updateAction($id)
    {


        $request = $this->get('request');

        $model = $this->getModel($this->getEntityClass());

        $formType = $this->getFormType($this->getEntityClass(), null, $model);
        $entity = $model->findOneById($id);
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
        $routeParams = $this->getRouteParams();

        $updateForm = $this->makeForm($formType, $entity, 'PUT', $this->getEntityName(), $this->getAction('update'), $id);

        $updateForm->handleRequest($request);

        //config parameters for render and event broadcast

        $params = $this->get('prototype.controler.params');

        //dump($params);

        $params->setArray([
            'entity' => $entity,
            'form' => $updateForm->createView(),
            'entityName' => $this->getEntityName(),
            'listActionName' => $this->getAction('list'),
            'updateActionName' => $this->getAction('update'),
            'config' => $this->getConfig(),
            'routeParams' => $routeParams,
            'states' => $this->getStates()
        ]);

        //Create event broadcast.


        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);
        $event->setForm($updateForm);


        if ($updateForm->isValid()) {

            $this->dispatch('before.update', $event);
            $model->update($entity, true);
            $this->dispatch('after.update', $event);

            $view = $this->redirectView($this->generateUrl($routePrefix . '_read', $this->getRouteParams()), 301);
            return $this->handleView($view);
        }

        $this->get('event_dispatcher')->dispatch($routePrefix . '.' . $entityName . '.' . $routeParams['actionId'] . '.' . 'invalid.update', $event);

        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_update'));
        return $this->handleView($view);
    }

    /**
     * Delete action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function deleteAction($id)
    {

        $model = $this->getModel($this->getEntityClass());
        $entity = $model->findOneById($id);
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
        $routeParams = $this->getRouteParams();

        //config parameters for render and event broadcast
        $params = $this->get('prototype.controler.params');

        $params->setArray($routeParams);

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);

        if (null != $entity) {
            $this->dispatch('before.delete', $event);
            $model->delete($entity, true);
            $this->dispatch('after.delete', $event);
        }
        $view = $this->redirectView($this->generateUrl($routePrefix . '_list', $params->getArray()), 301);
        return $this->handleView($view);
    }

    /**
     * Edit action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function editAction($id)
    {

        $model = $this->getModel($this->getEntityClass());
        $formType = $this->getFormType($this->getEntityClass(), null, $model);
        $entity = $model->findOneById($id);
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
        $routeParams = $this->getRouteParams();

        $editForm = $this->makeForm($formType, $entity, 'PUT', $this->getEntityName(), $this->getAction('update'), $id);

        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'entityName' => $this->getEntityName(),
            'listActionName' => $this->getAction('list'),
            'updateActionName' => $this->getAction('update'),
            'config' => $this->getConfig(),
            'routeParams' => $routeParams,
            'states' => $this->getStates(),
            'form' => $editForm->createView(),
        ]);

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);
        $event->setForm($editForm);

        $this->dispatch('on.edit', $event);


        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_update'));
        return $this->handleView($view);
    }

    /**
     * read action.
     * 
     * @param $id Entity id
     * @return Response
     */
    public function readAction($id)
    {
        $model = $this->getModel($this->getEntityClass());
        $entity = $model->findOneById($id);
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
        $routeParams = $this->getRouteParams();


        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'properties' => $this->prepareProperties($model, $entity),
            'entityName' => $entityName,
            'editActionName' => $this->getAction('edit'),
            'listActionName' => $this->getAction('list'),
            'deleteActionName' => $this->getAction('delete'),
            'config' => $this->getConfig(),
            'routeParams' => $routeParams,
            'states' => $this->getStates(),
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId()
        ]);

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);

        $this->dispatch('on.read', $event);

        //Render
        $view = $this->view($params['entity'])->setTemplate($this->getConfig()->get('twig_element_read'))->setTemplateData($params->getArray());
        return $this->handleView($view);
    }

    protected function prepareProperties($model, $entity)
    {

        $properties = [];
        $fields = array_merge($model->getMetadata()->fieldMappings, $model->getMetadata()->getAssociationMappings());


        foreach ($fields as $field) {


            $method = $model->checkMethod($entity, $field['fieldName']);
            if ($method && $field['type'] != 4 /* && $field['type'] != 8 */) {


                $result = $entity->$method();
                if ($field['type'] == 'datetime') {
                    if (is_object($result)) {
                        $value = $result->format('Y-m-d H:i:s');
                    } else {
                        $value = '';
                    }
                } elseif ($field['type'] == 'date') {

                    if (is_object($result)) {
                        $value = $result->format('Y-m-d');
                    } else {
                        $value = '';
                    }
                } else {

                    $value = $result;
                }



                $properties[$field['fieldName']] = $value;
            }
        }



        return $properties;
    }

    /**
     * New action.
     * 
     * @return Response
     */
    public function newAction()
    {
        $entity = $this->getModel($this->getEntityClass())->getEntity();
        $model = $this->getModel($this->getEntityClass());
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
 

        $formType = $this->getFormType($this->getEntityClass(), null, $model);
        $form = $this->makeForm($formType, $entity, 'POST', $this->getEntityName(), $this->getAction('create'));

        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'form' => $form->createView(),
            'entityName' => $entityName,
            'listActionName' => $this->getAction('list'),
            'config' => $this->getConfig(),
            'states' => $this->getStates()
        ]);

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);
        $event->setForm($form);

        $this->dispatch('on.new', $event);


        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_create'));
        return $this->handleView($view);
    }
    
    
    
    
       public function viewAction()
    {
        $entity = $this->getModel($this->getEntityClass())->getEntity();
        $model = $this->getModel($this->getEntityClass());
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
 
        
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'entityName' => $entityName,
            'listActionName' => $this->getAction('list'),
            'config' => $this->getConfig(),
            'states' => $this->getStates()
        ]);

        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);
        
        $this->dispatch('on.view', $event);


        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_view'));
        return $this->handleView($view);
    }
    
    

    /**
     * Get dependency container.
     * 
     * @return Symfony\Component\DependencyInjection\ContainerInterface Dependency container
     */
    protected function getContainer()
    {
        return $this->get('service_container');
    }

    /**
     * Get class of controller entity.
     * 
     * @return string
     */
    protected function getEntityClass()
    {
        if (null == $this->objectName) {
            $this->objectName = $this->get('request')->attributes->get('objectName');
        }
        return $this->objectName;
    }

    /**
     * Get mapping name of controller entity.
     * 
     * @return string
     */
    protected function getEntityName()
    {
        if (null == $this->entityName) {
            $this->entityName = $this->get('request')->attributes->get('entityName');
        }
        return $this->entityName;
    }

    /**
     * Get controller model.
     * 
     * @param string $objectName
     * @param string $managerName
     * @return Core\BaseBundle\Model
     */
    protected function getModel($objectName, $managerName = null)
    {
        if ($managerName) {
            $factory = "model_factory" . $managerName;
        } else {
            $factory = "model_factory";
        }
        $modelFactory = $this->get($factory);
        $model = $modelFactory->getModel($objectName);
        return $model;
    }

    /**
     * Get default model for this controller.
     * 
     * @return Core\BaseBundle\Model Default model for this controller
     */
    protected function getDefaultModel()
    {
        $modelFactory = $this->get("model_factory");
        $model = $modelFactory->getModel($this->getEntityClass());

        return $model;
    }

    /**
     * Get form type class for entity.
     * 
     * @param string $objectName Entity object
     * @param string $class Entity class
     * @return Symfony\Component\Form\Extension\Core\Type\FormType FormType
     */
    protected function getFormType($objectName, $class = null)
    {

        $configurator = $this->get("prototype.formtype.configurator.service");
        $formType = $configurator->getService($this->getRouteName(), $this->getEntityClass());
        if (get_class($formType) == 'Core\PrototypeBundle\Form\FormType') {
            $formType->setModel($this->getModel($objectName));
        }
        if (!$formType) {

            $formTypeFactory = $this->get("prototype_formtype_factory");
            $formType = $formTypeFactory->getFormType($this->getModel($objectName));
        }
        return $formType;
    }

    /**
     * Get form.
     * 
     * @todo warto nad tym jeszcze troche popracowac
     * @param string $formType
     * @param string $entity
     * @param string $method
     * @param string $entityName
     * @param string $action
     * @param string $id
     * @param string $class
     * @param string $url
     * @return Symfony\Component\Form\Extension\Core\Type\FormType
     */
    protected function makeForm($formType, $entity, $method, $entityName, $action, $id = null, $class = null, $url = null)
    {
        $params = $this->getRouteParams();

        if ($url && !$id) {
            $url = $this->generateUrl($url);
        }
        /* elseif ($url && $id) {
          $url = $this->generateUrl($action, $params);
          } elseif (empty($id)) {
          $url = $this->generateUrl($action, $params);
          } */ else {

            //@todo: moze byc z tym problem
            $url = $this->generateUrl($action, $params);
        }

        $form = $this->createForm($formType, $entity, array(
            'action' => $url, //$this->generateUrl('user_create'),
            'method' => $method
        ));

        return $form;
    }

    /**
     * Get current route.
     * 
     * @return string Current route
     */
    protected function getRouteName()
    {
        if (null == $this->routeName) {
            $this->routeName = $this->get('request')->attributes->get('_route');
        }
        return $this->routeName;
    }

    /**
     * Get prefix of current route.
     * 
     * @return string Current route
     */
    protected function getRoutePrefix()
    {
        $routeStringArray = explode("_", $this->getRouteName());
        $this->routePrefix = implode("_", array_slice($routeStringArray, 0, -1));
        return $this->routePrefix;
    }

    /**
     * Get route for custom action.
     * 
     * @param string $actionName Custom action name
     * @return string Custom route
     */
    protected function getAction($actionName)
    {
        return $this->getRoutePrefix() . "_" . $actionName;
    }

    protected function loadConfig()
    {

        $configurator = $this->get("prototype.configurator.service");
        $config = $configurator->getService($this->getRouteName(), $this->getEntityClass());
        return $config;
    }

    protected function getConfig()
    {


        if (false == $this->configService) {

            $this->configService = $this->loadConfig();



            $this->configService->merge($this->config);
        }
        return $this->configService;
    }

    protected function getRouteParams()
    {
        $parametersArr = $this->get('request')->attributes->all();
        $parameters = $parametersArr["_route_params"];
        return $parameters;
    }

    protected function getStates()
    {
        $params = $this->getRouteParams();
        if ($this->states == null) {

            $states = explode("/", $params["states"]);
            if ($states["0"] == null) {
                $this->states = [];
            } else {
                $this->states = $states;
            }
        }
        return $this->states;
    }

    protected function getParentName()
    {
        $params = $this->getRouteParams();
        if ($this->parentName == null) {
            if (array_key_exists("parentName", $params)) {
                $this->parentName = $params["parentName"];
            }
        }
        return $this->parentName;
    }

    protected function getParentId()
    {
        $params = $this->getRouteParams();
        if ($this->parentId == null) {
            if (array_key_exists("parentId", $params)) {
                $this->parentId = $params["parentId"];
            }
        }
        return $this->parentId;
    }

    protected function getBasePath()
    {
        $params = $this->getRouteParams();
        $params["states"] = null;
        return $this->generateUrl($this->getRouteName(), $params);
    }

    protected function setBaseTwigParams()
    {
        
    }

}
