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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
    protected $routeParams = null;
    protected $configLoaded = false;
    protected $configService;
    protected $states = null;
    protected $dispatcher = null;
    protected $model=null;
    protected $requestStack=null;
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
    
    
    public function __construct($container,RequestStack $requestStack)
    {
        $this->setContainer($container);
        $this->requestStack=$requestStack;
    }
    
    protected function init(){
        $request=$this->requestStack->getCurrentRequest();
        $this->setObjectName($request);
        $this->testContainerNameType($request);
        $this->model = $this->getModel($this->initEntityClass($request));
        $this->entityName = $this->initEntityName($request);
        $this->setContainerName($request);
        $this->initRoutePrefix();
        $this->initRouteParams();
    }
    
    
    protected function setObjectName($request){
        if ($request->attributes->get('entityName')) {
            $this->objectName = $this->container->get("classmapperservice")->getEntityClass($request->attributes->get('entityName'), $request->getLocale());
        } else {
            throw new \Exception('Object name for entityName doesn\'t exists');
        }
    }

    protected function getDispatchName($fireAction)
    {
        $routePrefix = $this->getRoutePrefix();
        
        if (!$this->routeParams) {
            $routeParams = $this->getRouteParams();
        }

        return $routePrefix . '.' . $this->routeParams['entityName'] . '.' . $this->routeParams['actionId'] . '.' . $fireAction;
    }

    protected function dispatch($name, $event)
    {
        if (null === $this->dispatcher) {
            $this->dispatcher = $this->get('event_dispatcher');
        }
        $this->dispatcher->dispatch($this->getDispatchName($name), $event);
    }

    /**
     * Create action.
     * 
     * @return Response

      )
     */
    public function createAction(Request $request)
    {
        $this->init();
        $entity = $this->model->getEntity();
        $routePrefix = $this->getRoutePrefix();
        $formType = $this->getFormType($this->getEntityClass(), $this->model);
        $form = $this->makeForm($formType, $entity, 'POST', $this->entityName, $this->getAction('create'), $this->routeParams);
        $form->handleRequest($request);
        //config parameters for render and event broadcast
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'entityName' => $this->entityName,
            'model' => $this->model,
            'form' => $form->createView(),
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'cancelActionName' => $this->getAction('list'),
            'defaultRoute' => $this->generateBaseRoute('create'),
            'states' => $this->getStates()
        ]);

        //parent params 
        $parentId = $request->get('parentId');
        $parentName = $request->get('parentName');
        if ($parentId && $parentName) {
            $params['parentId'] = $parentId;
            $params['parentName'] = $parentName;
        }
        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
        $event->setForm($form);


        if ($form->isValid()) {

            $this->dispatch('before.create', $event);
            $entity = $this->model->create($entity, true);

            $this->model->flush();
            $this->dispatch('after.create', $event);

            $this->routeParams['id'] = $entity->getId();
            $view = $this->redirectView($this->generateUrl($routePrefix . '_read', $this->routeParams), 301);
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
    public function listAction(Request $request)
    {


        
       $this->init();
        
        $entity = $this->model->getEntity();
        $queryBuilder = $this->model->getQueryBuilder('a');
        $query = $queryBuilder->getQuery();

    



        //query
        //pageNumber
        //limit per page



        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
                $query, $request->query->getInt('page', 1)/* page number */, 10/* limit per page */
        );


        $this->setRouteParam('entity', $entity);
        $this->setRouteParam('config', $this->getConfig());
        $this->setRouteParam('pagination', $pagination);


        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($this->routeParams);
        $event->setModel($this->model);
        //$event->setList($list);


        $this->dispatch('on.list', $event);


        //  throw new \BadMethodCallException("Not implemented yet");
        // parameters to template
        //Render
        $view = $this->view($this->routeParams)->setTemplate($this->getConfig()->get('twig_element_list'));
        return $this->handleView($view);
    }

    /**
     * Create action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function updateAction($id, Request $request)
    {



$this->init();
        

        $formType = $this->getFormType($this->getEntityClass(), null, $this->model);
        $entity = $this->model->findOneById($id);
        
        $routePrefix = $this->getRoutePrefix();

        
        $updateForm = $this->makeForm($formType, $entity, 'PUT', $this->entityName, $this->getAction('update'), $this->routeParams, $id);

        $updateForm->handleRequest($request);

        //config parameters for render and event broadcast

        $params = $this->get('prototype.controler.params');

        //dump($params);

        $params->setArray([
            'entity' => $entity,
            'form' => $updateForm->createView(),
            'model' => $this->model,
            'entityName' => $this->entityName,
            'cancelActionName' => $this->getAction('list'),
            'updateActionName' => $this->getAction('update'),
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'defaultRoute' => $this->generateBaseRoute('update'),
            'states' => $this->getStates()
        ]);

        //Create event broadcast.


        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
        $event->setForm($updateForm);


        if ($updateForm->isValid()) {

            $this->dispatch('before.update', $event);
            $this->model->update($entity, true);
            $this->dispatch('after.update', $event);

            $view = $this->redirectView($this->generateUrl($routePrefix . '_read', $this->routeParams), 301);
            return $this->handleView($view);
        }

        $this->get('event_dispatcher')->dispatch($routePrefix . '.' . $this->entityName . '.' . $this->routeParams['actionId'] . '.' . 'invalid.update', $event);

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
    public function deleteAction($id, Request $request)
    {

        $this->init();
        $entity = $this->model->findOneById($id);
        
        $routePrefix = $this->getRoutePrefix();
 

        


        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($this->routeParams);
        $event->setModel($this->model);

        if (null != $entity) {
            $this->dispatch('before.delete', $event);
            $this->model->delete($entity, true);
            $this->dispatch('after.delete', $event);
        }
        $view = $this->redirectView($this->generateUrl($routePrefix . '_list', $this->routeParams), 301);
        return $this->handleView($view);
    }

    /**
     * Edit action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function editAction($id, Request $request)
    {
        $this->init();
        $formType = $this->getFormType($this->getEntityClass(), null, $this->model);
        $entity = $this->model->findOneById($id);
        $editForm = $this->makeForm($formType, $entity, 'PUT', $this->entityName, $this->getAction('update'), $this->routeParams, $id);

        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'entityName' => $this->entityName,
            'model' => $this->model,
            'cancelActionName' => $this->getAction('list'),
            'updateActionName' => $this->getAction('update'),
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'defaultRoute' => $this->generateBaseRoute('edit'),
            'states' => $this->getStates(),
            'form' => $editForm->createView(),
        ]);

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
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
        $this->init();
        $request=$this->requestStack->getCurrentRequest();
       

        $entity = $this->model->findOneById($id);
        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'element';
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'properties' => $this->prepareProperties($this->model, $entity),
            'entityName' => $this->entityName,
            'model' => $this->model,
            'editActionName' => $this->getAction('edit'),
            'listActionName' => $this->getAction('list'),
            'deleteActionName' => $this->getAction('delete'),
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'buttonRouteParams' => $buttonRouteParams,
            'states' => $this->getStates(),
            'defaultRoute' => $this->generateBaseRoute('read'),
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId()
        ]);

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('on.read', $event);

        //Render
        $view = $this->view($params['entity'])->setTemplate($this->getConfig()->get('twig_element_read'))->setTemplateData($params->getArray());
        return $this->handleView($view);
    }

    protected function prepareProperties($model, $entity)
    {

        $properties = [];
        $fields = array_merge($this->model->getMetadata()->fieldMappings, $this->model->getMetadata()->getAssociationMappings());


        foreach ($fields as $field) {


            $method = $this->model->checkMethod($entity, $field['fieldName']);
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

    protected function setContainerName(Request $request)
    {


        /* if ($request->isXmlHttpRequest()) {
          $this->setRouteParam('containerName', 'element');
          }
          else{ */



        $this->setRouteParam('containerName', $request->get('containerName'));
        /*  } */
    }

    /**
     * New action.
     * 
     * @return Response
     */
    public function newAction(Request $request)
    {
        $this->init();
        $this->testContainerNameType($request);
        $entity = $this->getModel($this->getEntityClass())->getEntity();
        $formType = $this->getFormType($this->getEntityClass(), null, $this->model);
        $form = $this->makeForm($formType, $entity, 'POST', $this->entityName, $this->getAction('create'), $this->routeParams);
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'form' => $form->createView(),
            'entityName' => $this->entityName,
            'model' => $this->model,
            'cancelActionName' => $this->getAction('list'),
            'routeParams' => $this->routeParams,
            'config' => $this->getConfig(),
            'defaultRoute' => $this->generateBaseRoute('new'),
            'states' => $this->getStates()
        ]);

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
        $event->setForm($form);

        $this->dispatch('on.new', $event);


        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_create'));
        return $this->handleView($view);
    }

    public function viewAction($id, Request $request)
    {
        $this->init();
        $this->testContainerNameType($request);
        $entity = $this->model->findOneById($id);
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'entityName' => $this->entityName,
            'cancelActionName' => $this->getAction('list'),
            'model' => $this->model,
            'defaultRoute' => $this->generateBaseRoute('view'),
            'routeParams' => $this->routeParams,
            'config' => $this->getConfig(),
            'states' => $this->getStates()
        ]);

        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('on.view', $event);


        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_view'));
        return $this->handleView($view);
    }

    protected function generateBaseRoute($action)
    {
        $params = $this->routeParams;
        $params['states'] = null;
        return $this->generateUrl($this->getAction($action), $params, UrlGeneratorInterface::ABSOLUTE_URL);
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
    protected function initEntityClass(Request $request)
    {
        if (null == $this->objectName) {
            $this->objectName = $request->attributes->get('objectName');
        }
        return $this->objectName;
    }
    
    
    /**
     * Get class of controller entity.
     * 
     * @return string
     */
    protected function getEntityClass()
    {
        return $this->objectName;
    }

    
    /**
     * Get mapping name of controller entity.
     * 
     * @return string
     */
    protected function initEntityName(Request $request)
    {
        if (null == $this->entityName) {
            $this->entityName = $request->attributes->get('entityName');
        }
        return $this->entityName;
    }
    
    
    
    /**
     * Get mapping name of controller entity.
     * 
     * @return string
     */
    protected function getEntityName()
    {
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
        $this->modelFactory = $this->get($factory);
        $this->model = $this->modelFactory->getModel($objectName);
        return $this->model;
    }

    /**
     * Get default model for this controller.
     * 
     * @return Core\BaseBundle\Model Default model for this controller
     */
    protected function getDefaultModel()
    {
        $this->modelFactory = $this->get("model_factory");
        $this->model = $this->modelFactory->getModel($this->getEntityClass());

        return $this->model;
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
     * @param string $this->entityName
     * @param string $action
     * @param string $id
     * @param string $class
     * @param string $url
     * @return Symfony\Component\Form\Extension\Core\Type\FormType
     */
    protected function makeForm($formType, $entity, $method, $entityName, $action, $routeParams, $id = null, $class = null, $url = null)
    {
        $routeParams = $this->getRouteParams();
        $routeParams['containerName'] = 'element';

        if ($url && !$id) {
            $url = $this->generateUrl($url);
        }
        /* elseif ($url && $id) {
          $url = $this->generateUrl($action, $params);
          } elseif (empty($id)) {
          $url = $this->generateUrl($action, $params);
          } */ else {

            //@todo: moze byc z tym problem
            $url = $this->generateUrl($action, $routeParams);
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
    protected function initRoutePrefix()
    {
        $routeStringArray = explode("_", $this->getRouteName());
        $this->routePrefix = implode("_", array_slice($routeStringArray, 0, -1));
        
    }
    
    protected function getRoutePrefix()
    {
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

    
    protected function initRouteParams()
    {
        $parametersArr = $this->get('request')->attributes->all();
        $this->routeParams = $parametersArr["_route_params"];
    }
    
    
    
    
    protected function getRouteParams()
    {
        
        return $this->routeParams;
    }

    protected function setRouteParam($param, $value)
    {
        $this->routeParams[$param] = $value;
        return $this->routeParams;
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

    protected function isMasterRequest()
    {
        if ($this->container->get('request_stack')->getParentRequest() == null) {
            return true;
        }
        return false;
    }
    
    protected function testContainerNameType($request){
        
     
        
        if($this->isMasterRequest() && !$request->isXmlHttpRequest() && $this->routeParams['containerName']=='element'){
            throw $this->createNotFoundException('The site does not exist');
        }
        
    }

}
