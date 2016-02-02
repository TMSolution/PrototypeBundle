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
    protected $actionName = null;
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
        $this->initBaseRouteName();
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
            throw new \Exception('Object name for entityName doesn\'t exist');
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

    protected function getRouteSymbol()
    {


        $baseRoute = $this->getBaseRouteName();     //$this->getRoutePrefix();
        $routeParams = $this->getRouteParams();

        if (array_key_exists('parentName', $routeParams)) {
            return $baseRoute . '.' . $routeParams['entityName'] . '.' . $routeParams['parentName'] . '.' . $routeParams['actionId'];
        }


        if (array_key_exists('actionId', $routeParams)) {
            return $baseRoute . '.' . $routeParams['entityName'] . '.' . $routeParams['actionId'];
        }

        return $baseRoute . '.' . $routeParams['entityName'];
    }

    protected function getDispatchName($fireAction)
    {
        dump($this->getRouteSymbol() . '.' . $fireAction);
        return $this->getRouteSymbol() . '.' . $fireAction;
    }

    protected function dispatch($name, $event)
    {
        if (null === $this->dispatcher) {
            $this->dispatcher = $this->get('event_dispatcher');
        }

        //dump($this->getDispatchName($name));die();

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
        
        /* @todo defaultRoute warto się pozbyć jeśli można */
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entityName' => $this->entityName,
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId(),
            //'actionId' => $this->getActionId(),
            'model' => $this->model,
            'config' => $this->getConfig(),
            'routeParams' => $this->routeParams,
            'states' => $this->getStates(),
            'isMasterRequest' => $this->isMasterRequest(),
            'parentEntity' => $this->getParentEntity($this->request),
        ]);
        return $params;
    }

    protected function getRouteName($name)
    {
        return $this->getRouteService()->getRouteName($this->getConfig(), $name);
    }

    protected function getNextRoute($name, $default = "read")
    {

        if (!$name) {
            $name = $default;
        }

        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'routeParams' => $this->routeParams,
        ]);

        $event = $this->get('prototype.event')->setParams($params);
        $this->dispatch('before.generateNextRoute', $event);

        $routeName = $this->getRouteService()->getRouteName($this->getConfig(), $name);
        $params = $event->getParams();
        $routeParams = $this->getRouteService()->getRouteParams($this->getConfig(), $name, $params['routeParams']);
        return $this->generateUrl($routeName, $routeParams);
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

        $formType = $this->getFormType($this->getEntityClass(), $this->model);
        $form = $this->makeForm($formType, $entity, 'POST', $this->entityName, $this->getRouteName('create'), $this->routeParams);
        $form->handleRequest($request);
        $params = $this->getDefaultParameters()
                ->merge([
            'entity' => $entity,
            'form' => $form->createView(),
            'defaultRoute' => $this->generateBaseRoute('create'),
            'submitType' => $this->getSubmitType($request)
        ]);

        $event = $this->get('prototype.event')->setParams($params)->setModel($this->model)->setForm($form);
        if ($form->isValid()) {


            $this->dispatch('before.create', $event);

            $entity = $this->model->create($entity, true);
            $this->model->flush();
            $event->setEntity($entity);
            $this->routeParams['id'] = $entity->getId();
            $this->routeParams['submittype'] = $this->getSubmitType($request);
            $this->dispatch('after.create', $event);
            $view = $this->redirectView($this->getNextRoute($this->getSubmitType($request)), 301);
            return $this->handleView($view);
        }


        $this->dispatch('on.invalidcreate', $event);
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('actions.create.templates.element'))->setHeader('Location', $this->getLocationUrl('create'));
        return $this->handleView($view);
    }

    public function listAction(Request $request)
    {
        
        $this->init();
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
        $listConfig = $this->getListConfig();
        $formType = $listConfig->getFormType();
        $queryBuilder = $listConfig->getQueryBuilder();
        $paginator = $this->container->get('knp_paginator');
        if ($formType) {
            $form = $this->makeForm($formType, $this->model->getEntity(), 'GET', $entityName, $this->getRouteName("list"), $this->getRouteParams());
            
            $lexik = $this->container->get('lexik_form_filter.query_builder_updater');
            $form->submit($this->request);

            $lexik->addFilterConditions($form, $queryBuilder);
        }
        $query = $queryBuilder->getQuery(); //->getResult();

        $query->setHydrationMode($this->getConfig()->get('actions.list.hydrateMode'));
 
        $pagination = $paginator->paginate(
                $query, $this->request->query->getInt('page', 1), $limit = $this->getConfig()->get('actions.list.limit')
        );



        $buttonRouteParams = $this->getRouteParams();
        $buttonRouteParams['containerName'] = 'container';


        $buttonRouteParams = $this->getRouteParams();
        $params = $this->get('prototype.controler.params');

        if ($this->request->getRequestFormat() == 'html') {



            $params = $this->getDefaultParameters()
                    ->merge(
                    [
                        'routeName' => $routePrefix . '_new',
                        'defaultRoute' => $this->generateBaseRoute('list'),
                        'pagination' => $pagination,
                        'fieldsNames' => $listConfig->getFieldsNames($this->model),
                        'routePrefix' => $routePrefix,
                        'fieldsAliases' => $listConfig->getFieldsAliases(),
                        'submitType' => $this->getSubmitType($request),
                        
                        'states' => $this->getStates()
                    //'form'=>$form
            ]);
            
            
            if(isset($form)){
                $params['form'] = $form->createView();
            }

            $buttonRouteParams = $this->getRouteParams();
            $buttonRouteParams['containerName'] = 'container';
            $params['buttonRouteParams'] = $buttonRouteParams;
        }

        $this->setRouteParam('pagination', $pagination);




        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('before.list', $event);
        $view = $this->view([
                            "status" => "success",
                            "totalCount" => $pagination->getPageCount(),
                            "page" => $pagination->getCurrentPageNumber(),
                            "items" => $pagination->getItems(),
                            "limit" => $pagination->getItemNumberPerPage()
                        ])
                        ->setTemplate($this->getConfig()->get('actions.list.templates.element'))
                        ->setTemplateData($params->getArray())->setHeader('Location', $this->getLocationUrl('list'));

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

        $event = $this->get('prototype.event');
        $this->dispatch('before.find', $event);

        $entity = $this->model->findOneById($id);
        
        
        $routePrefix = $this->getRoutePrefix();
        
        $updateForm = $this->makeForm($formType, $entity, 'PUT', $this->entityName, $this->getAction('update'), $this->routeParams, $id);

        
        $updateForm->handleRequest($request);
        
//                dump($updateForm);
//        exit();

        
        $params = $this->get('prototype.controler.params');

        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'container';

        $params = $this->getDefaultParameters()
                ->merge(
                [
                    'entity' => $entity,
                    'form' => $updateForm->createView(),
                    'buttonRouteParams' => $buttonRouteParams,
                    'defaultRoute' => $this->generateBaseRoute('update'),
                    'submitType' => $this->getSubmitType($request)
        ]);



        $event->setEntity($entity);
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

        $this->dispatch('invalid.update', $event);
        $this->dispatch('before.render', $event);
        $view = $this->view($params['entity'])
                ->setTemplateData($params->getArray())
                ->setTemplate($this->getConfig()->get('actions.update.templates.element'))->setHeader('Location', $this->getLocationUrl('update'));
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


        $event = $this->get('prototype.event');
        $this->dispatch('before.find', $event);

        $entity = $this->model->findOneById($id);

        $routePrefix = $this->getRoutePrefix();

        $event->setParams($this->routeParams);
        $event->setModel($this->model);

        $response = [];
        $response['status'] = 'success';
        $response['message'] = '';
        $this->routeParams["containerName"] = "container";

        //@todo grid jest juz nie używany
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

        $event = $this->get('prototype.event');
        $this->dispatch('before.find', $event);

        $entity = $this->model->findOneById($id);
        $editForm = $this->makeForm($formType, $entity, 'PUT', $this->entityName, $this->getRouteName('update'), $this->routeParams, $id);

        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'container';

        $params = $this->get('prototype.controler.params');
        $params = $this->getDefaultParameters()
                ->merge([
            'entity' => $entity,
            'buttonRouteParams' => $buttonRouteParams,
            'defaultRoute' => $this->generateBaseRoute('edit'),
            'form' => $editForm->createView(),
            'submitType' => $this->getSubmitType($request)
        ]);

        $event->setParams($params);
        $event->setModel($this->model);
        $event->setForm($editForm);

        $this->dispatch('on.edit', $event);


        $view = $this->view($params['entity'])
               
                ->setTemplate($this->getConfig()->get('actions.update.templates.element'))
                 ->setTemplateData($params->getArray())
                ->setHeader('Location', $this->getLocationUrl('edit'));
        return $this->handleView($view);
    }

    /**
     * read action.
     * 
     * @param $id Entity id
     * @return Response
     */
    public function readAction($id, Request $request)
    {

        $this->init();
        $request = $this->requestStack->getCurrentRequest();

        $event = $this->get('prototype.event');
        $this->dispatch('before.find', $event);


        $entity = $this->model->findOneById($id);
        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'element';
        $params = $this->get('prototype.controler.params');

        $params = $this->getDefaultParameters()
                ->merge(
                [
                    'entity' => $entity,
                    'properties' => $this->prepareProperties($this->model, $entity),
                    'buttonRouteParams' => $buttonRouteParams,
                    'defaultRoute' => $this->generateBaseRoute('read'),
        ]);


        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('on.read', $event);


        //Render
        $view = $this->view($params['entity'])->setTemplate($this->getConfig()->get('actions.read.templates.element'))
                ->setTemplateData($params->getArray())
                ->setHeader('Location', $this->getLocationUrl('read'));
        return $this->handleView($view);
    }

    protected function getLocationUrl($action, $prefix = null)
    {

        if (!$prefix) {
            if ($this->getActionId()) {
                $prefix = "action";
            } else {
                $prefix = "simple";
            }
        }

        $routePrefix = $this->getRoutePrefix();
        $routeParams = $this->getRouteParams();
        $routeParams["containerName"] = "container";
        //@todo
        $url = $this->generateUrl($routePrefix . '_' . $prefix . $action, $routeParams);
        if (!$url) {
            $url = $this->generateUrl($this->getBaseRouteName(), $routeParams);
        }else
        {
          // throw new \Exception("Route {$this->getBaseRouteName()} doesn't exist!");
        }

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
        $form = $this->makeForm($formType, $entity, 'POST', $this->entityName, $this->getRouteName("create"), $this->routeParams);
        $params = $this->get('prototype.controler.params');
        $params = $this->getDefaultParameters()
                ->merge(
                [
                    'entity' => $entity,
                    'form' => $form->createView(),
                    'defaultRoute' => $this->generateBaseRoute('new'),
                    'submitType' => $this->getSubmitType($request)
        ]);

//Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
        $event->setForm($form);

        $this->dispatch('on.new', $event);


//Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('actions.create.templates.element'))
                ->setHeader('Location', $this->getLocationUrl('new'));
        return $this->handleView($view);
    }

    public function viewAction($id, Request $request)
    {

        $this->init();
        $entity = $this->model->findOneById($id);

        $viewConfig = $this->getViewConfig();

        $viewConfig->setModel($this->model);




        $params = $this->get('prototype.controler.params');
        $params = $this->getDefaultParameters()
                ->merge([
            'entity' => $entity,
            'defaultRoute' => $this->generateBaseRoute('view'),
        ]);

        $viewConfig->getView($params);
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('on.view', $event);


        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('actions.view.templates.element'));
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
        $formType = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());
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

        $routeParams = array_merge($routeParams, ["containerName" => "element"]);
        if ($this->request->getRequestFormat() == 'html') {
            if ($url && !$id) {
                $url = $this->generateUrl($url);
            } else {
                $url = $this->generateUrl($action, $routeParams);
            }
        }

        $form = $this->createForm($formType, $entity, array(
            'action' => $url,
            'method' => $method
        ));

        return $form;
    }

    /**
     * Get current route.
     * 
     * @return string Current route
     */
    protected function initBaseRouteName()
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
    protected function getBaseRouteName()
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
        $routeStringArray = explode("_", $this->getBaseRouteName());
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
        $config = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());
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

    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        try {
            return $this->container->get('router')->generate($route, $parameters, $referenceType);
        } catch (\Exception $e) {
            
        }
    }

    protected function getBasePath()
    {
        $params = $this->getRouteParams();
        $params["states"] = null;
        return $this->generateUrl($this->getBaseRouteName(), $params);
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
        return $submitType = $request->get('submittype') != null ? $request->get('submittype') : 'read';
    }

    protected function getListConfig()
    {
        $configurator = $this->get("prototype.listconfig.configurator.service");
        $listConfig = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());

        $listConfig->setModel($this->model);
        return $listConfig;
    }

    protected function getViewConfig()
    {
        $configurator = $this->get("prototype.viewconfig.configurator.service");
        $viewConfig = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());
        $viewConfig->setModel($this->model);
        return $viewConfig;
    }

    protected function getRouteService()
    {
        return $this->container->get("prototype.routeservice");
    }

}
