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
    protected $request = null;
    protected $routePrefix = null;
    protected $entityName;
    protected $parentName = null;
    protected $parentId = null;
    protected $parentEntity = null;
    protected $routeName = null;
    protected $routeParams = null;
    protected $configLoaded = false;
    protected $configService;
    protected $states = null;
    protected $dispatcher = null;
    protected $model = null;
    protected $requestStack = null;
//element praktycznie zawsze zmieniany, konfiguracja na zewnątrz
    protected $config = [];

    public function __construct($container, RequestStack $requestStack)
    {
        $this->setContainer($container);
        $this->requestStack = $requestStack;
    }

    protected function init()
    {
        $this->configService = null;

        $this->request = $this->requestStack->getCurrentRequest();
        $this->initRouteName();
        $this->initRoutePrefix();
        $this->initRouteParams();

        $this->setObjectName($this->request);
        $this->testContainerNameType($this->request);
        $this->model = $this->getModel($this->initEntityClass($this->request));
        $this->entityName = $this->initEntityName($this->request);
        $this->setContainerName($this->request);
    }

    protected function setObjectName($request)
    {
        if ($request->attributes->get('entityName')) {
            $this->objectName = $this->container->get("classmapperservice")->getEntityClass($request->attributes->get('entityName'), $request->getLocale());
        } else {
            throw new \Exception('Object name for entityName doesn\'t exists');
        }
    }

    protected function getParentEntityClassName()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request->attributes->get('parentName')) {
            return $this->parentEntityClassName = $this->container->get("classmapperservice")->getEntityClass($request->attributes->get('parentName'), $request->getLocale());
        }
        return null;
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

    protected function getParentEntity($request)
    {
        $parentId = $request->get('parentId');
        $parentName = $request->get('parentName');

        if ($parentId && $parentName) {
            $parentEntityClassName = $this->container->get("classmapperservice")->getEntityClass($parentName, $request->getLocale());
            $parentModel = $this->container->get("model_factory")->getModel($parentEntityClassName);
            $parentEntity = $parentModel->findOneById($parentId);

            if ($parentEntity) {
                return $parentEntity;
            }
        }
        return null;
    }

    /* in progress */

    protected function getDefaultParameters()
    {

        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entityName' => $this->entityName,
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId(),
            'model' => $this->model,
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'states' => $this->getStates(),
            'isMasterRequest' => $this->isMasterRequest(),
            'parentEntity' => $this->getParentEntity($this->request),
        ]);
        return $params;
    }

    protected function getNextRoute($name, $default = "read")
    {

        $routings = $this->getConfig()->get('routings');

        if ($name && array_key_exists($name, $routings)) {
            $routeConfig = $routings[$name];
            $routeName = $routeConfig["route"];
            $routeName = str_replace('*', $this->getRoutePrefix() . '_', $routeName);
            if (is_array($routeConfig["route_params"])) {



                $route_params = array_merge($this->getRouteParams(), $routeConfig["route_params"]);
            } else {
                $route_params = $this->getRouteParams();
            }

            return $this->generateUrl($routeName, $route_params);
        } else {
            return $this->generateUrl($this->getRoutePrefix() . '_' . $default, $this->getRouteParams());
        }
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
        $params = $this->getDefaultParameters()
                ->merge([
            'entity' => $entity,
            'form' => $form->createView(),
            'cancelActionName' => $this->getAction('grid'),
            'defaultRoute' => $this->generateBaseRoute('create'),
            'parentActionName' => $this->getAction('view'),
            'submitType' => $this->getSubmitType($request)
        ]);

//Create event broadcast.
        $event = $this->get('prototype.event')->setParams($params)->setModel($this->model)->setForm($form);
        if ($form->isValid()) {

            $this->dispatch('before.create', $event);
            //tu chyba jest flush
            $entity = $this->model->create($entity, true);
            $this->model->flush();
            $this->dispatch('after.create', $event);
            $this->routeParams['id'] = $entity->getId();
            $this->routeParams['submittype'] = $this->getSubmitType($request);
            $view = $this->redirectView($this->getNextRoute($this->getSubmitType($request)), 301);
            return $this->handleView($view);
        }

        $this->dispatch('on.invalidcreate', $event);
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_create'))->setHeader('Location', $this->getLocationUrl('create', 'simple'));
        return $this->handleView($view);
    }

    public function listAction(Request $request)
    {

        $this->init();
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();

        $listConfig = $this->getListConfig();

        $formType = $listConfig->getFormType();
        $form = $this->makeForm($formType,   $this->model->getEntity(), 'POST', $entityName, $this->getAction('list'), $this->routeParams);
        
        $queryBuilder = $listConfig->getQueryBuilder();
//        $paginator = $this->get('knp_paginator');
//          $pagination = $paginator->paginate(
//          $query, $this->request->query->getInt('page', 1), 2
//          ); 

        $pagination = $this->container->get("savvy.filter_nator")->filterNate(
                $queryBuilder, $form, 'foo',  $this->request->query->getInt('page', 10)/* return 5 entities */,  1 /* starting from page 1 */
        );

        $buttonRouteParams = $this->getRouteParams();
        $buttonRouteParams['containerName'] = 'container';



        $params = $params = $this->get('prototype.controler.params');
        $params->setArray(
                [
                    'entityName' => $entityName,
                    'parentName' => $this->getParentName(),
                    'parentId' => $this->getParentId(),
                    'newActionName' => $this->getAction('new'),
                    'routeName' => $routePrefix . '_new',
                    'config' => $this->getConfig(),
                    'routeParams' => $this->getRouteParams(),
                    'buttonRouteParams' => $buttonRouteParams,
                    'isMasterRequest' => $this->isMasterRequest(),
                    'defaultRoute' => $this->generateBaseRoute('list'),
                    'pagination' => $pagination,
                    'fieldsNames' => $listConfig->getFieldsNames($this->model),
                    'routePrefix' => $routePrefix,
                    'fieldsAliases' => $listConfig->getFieldsAliases(),
                    'form'=>$form,
                    //'form'=>$form
        ]);

        $this->setRouteParam('pagination', $pagination);

        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('before.list', $event);
        $view = $this->view($params->getArray())
                        ->setTemplate($this->getConfig()->get('twig_element_list'))
                        ->setTemplateData()->setHeader('Location', $this->getLocationUrl('list', 'simple'));
        ;
        $this->dispatch('after.list', $event);
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


        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'container';

        $params->setArray([
            'entity' => $entity,
            'parentEntity' => $this->getParentEntity($request),
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId(),
            'form' => $updateForm->createView(),
            'model' => $this->model,
            'entityName' => $this->entityName,
            'cancelActionName' => $this->getAction('grid'),
            'updateActionName' => $this->getAction('update'),
            'parentActionName' => $this->getAction('view'),
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'buttonRouteParams' => $buttonRouteParams,
            'defaultRoute' => $this->generateBaseRoute('update'),
            'states' => $this->getStates(),
            'isMasterRequest' => $this->isMasterRequest(),
            'submitType'=>$this->getSubmitType($request)    
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
            $this->routeParams['submittype'] = $this->getSubmitType($request);
            $view = $this->redirectView($this->getNextRoute($this->getSubmitType($request)), 301);
            return $this->handleView($view);
        }

        $this->get('event_dispatcher')->dispatch($routePrefix . '.' . $this->entityName . '.' . $this->routeParams['actionId'] . '.' . 'invalid.update', $event);

//Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_update'))->setHeader('Location', $this->getLocationUrl('update', 'simple'));
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

        $response = [];
        $response['status'] = 'success';
        $response['message'] = '';
        $this->routeParams["containerName"] = "container";
        $response['redirectUrl'] = $this->generateUrl($routePrefix . '_grid', $this->routeParams);

        if (null != $entity) {
            $this->dispatch('before.delete', $event);
            try {
                $this->model->delete($entity, true);
            } catch (\Exception $e) {

                if ($e->getPrevious()->getCode() == '23000') {
                    $response['status'] = 'fail';
                    $response['message'] = $e->getMessage();
                }
            }
            $this->dispatch('after.delete', $event);
        }

        $view = $this->view($response); //->setTemplate($this->getConfig()->get('twig_element_update'));

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


        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'container';

       
        
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'parentEntity' => $this->getParentEntity($request),
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId(),
            'entityName' => $this->entityName,
            'model' => $this->model,
            'cancelActionName' => $this->getAction('grid'),
            'updateActionName' => $this->getAction('update'),
            'parentActionName' => $this->getAction('view'),
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'buttonRouteParams' => $buttonRouteParams,
            'defaultRoute' => $this->generateBaseRoute('edit'),
            'states' => $this->getStates(),
            'form' => $editForm->createView(),
            'isMasterRequest' => $this->isMasterRequest(),
            'submitType'=> $this->getSubmitType($request)  
        ]);

//Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
        $event->setForm($editForm);

        $this->dispatch('on.edit', $event);


//Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_update'))->setHeader('Location', $this->getLocationUrl('edit', 'simple'));
        ;
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
        $request = $this->requestStack->getCurrentRequest();


        $entity = $this->model->findOneById($id);
        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'element';
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'parentEntity' => $this->getParentEntity($request),
            'properties' => $this->prepareProperties($this->model, $entity),
            'entityName' => $this->entityName,
            'model' => $this->model,
            'editActionName' => $this->getAction('edit'),
            'listActionName' => $this->getAction('grid'),
            'deleteActionName' => $this->getAction('delete'),
            'parentActionName' => $this->getAction('view'),
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'buttonRouteParams' => $buttonRouteParams,
            'states' => $this->getStates(),
            'defaultRoute' => $this->generateBaseRoute('read'),
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId(),
            'isMasterRequest' => $this->isMasterRequest()
        ]);

//Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('on.read', $event);


        //Render
        $view = $this->view($params['entity'])->setTemplate($this->getConfig()->get('twig_element_read'))
                ->setTemplateData($params->getArray())
                ->setHeader('Location', $this->getLocationUrl('read', 'simple'));
        return $this->handleView($view);
    }

    protected function getLocationUrl($action, $prefix)
    {
        $routePrefix = $this->getRoutePrefix();
        $routeParams = $this->getRouteParams();
        $routeParams["containerName"] = "container";
        $url = $this->generateUrl($routePrefix . '_' . $prefix . $action, $routeParams);
        return $url;
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

        $this->setRouteParam('containerName', $request->get('containerName'));
    }

    /**
     * New action.
     * 
     * @return Response
     */
    public function newAction(Request $request)
    {

      
        $this->init();
        $entity = $this->getModel($this->getEntityClass())->getEntity();
        $formType = $this->getFormType($this->getEntityClass(), null, $this->model);
        $form = $this->makeForm($formType, $entity, 'POST', $this->entityName, $this->getAction('create'), $this->routeParams);
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'parentEntity' => $this->getParentEntity($request),
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId(),
            'form' => $form->createView(),
            'entityName' => $this->entityName,
            'model' => $this->model,
            'cancelActionName' => $this->getAction('grid'),
            'parentActionName' => $this->getAction('view'),
            'routeParams' => $this->routeParams,
            'config' => $this->getConfig(),
            'defaultRoute' => $this->generateBaseRoute('new'),
            'states' => $this->getStates(),
            'isMasterRequest' => $this->isMasterRequest(),
            'submitType' => $this->getSubmitType($request)
        ]);

//Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
        $event->setForm($form);

        $this->dispatch('on.new', $event);


//Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('twig_element_create'))
                ->setHeader('Location', $this->getLocationUrl('new', 'simple'));
        return $this->handleView($view);
    }

    public function viewAction($id, Request $request)
    {
        $this->init();
        $entity = $this->model->findOneById($id);

        $viewConfig = $this->getViewConfig();

        $viewConfig->setModel($this->model);

        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entity' => $entity,
            'parentEntity' => $this->getParentEntity($request),
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId(),
            'entityName' => $this->entityName,
            'cancelActionName' => $this->getAction('grid'),
            'model' => $this->model,
            'defaultRoute' => $this->generateBaseRoute('view'),
            'parentActionName' => $this->getAction('view'),
            'routeParams' => $this->routeParams,
            'config' => $this->getConfig(),
            'states' => $this->getStates(),
            'isMasterRequest' => $this->isMasterRequest()
        ]);

        $viewConfig->getView($params);
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

    protected function getActionId()
    {
        $request = $this->requestStack->getCurrentRequest();
        $actionId = $request->attributes->get('actionId');
        if ($actionId && $actionId != 'default') {
            return $actionId;
        }
        return null;
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
        $formType = $configurator->getService($this->getRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());
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
    protected function initRouteName()
    {
        // if (null == $this->routeName) {
        $this->routeName = $this->request->attributes->get('_route');
        //}
        return $this->routeName;
    }

    /**
     * Get current route.
     * 
     * @return string Current route
     */
    protected function getRouteName()
    {
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


        $config = $configurator->getService($this->getRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());
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
        $parametersArr = $this->request->attributes->all();
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

    protected function testContainerNameType($request)
    {

        if ($this->isMasterRequest() && !$request->isXmlHttpRequest() && $this->routeParams['containerName'] == 'element') {
            //throw $this->createNotFoundException('The site does not exist');
        }
    }
    
    protected function getSubmitType($request)
    {
        return  $submitType = $request->get('submittype') ? $request->get('submittype') : 'read';
    }
    
    

    protected function getListConfig()
    {
        $configurator = $this->get("prototype.listconfig.configurator.service");
        $listConfig = $configurator->getService($this->getRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());

        $listConfig->setModel($this->model);
        return $listConfig;
    }

    protected function getViewConfig()
    {
        $configurator = $this->get("prototype.viewconfig.configurator.service");
        $viewConfig = $configurator->getService($this->getRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());
        $viewConfig->setModel($this->model);
        return $viewConfig;
    }
    
    
    

}
