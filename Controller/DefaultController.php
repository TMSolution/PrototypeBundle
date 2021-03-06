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
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Default controller.
 * 
 * @copyright (c) 2014-current, TMSolution
 */
class DefaultController extends FOSRestController {

    protected $entityClass = null;
    protected $request = null;
    protected $routePrefix = null;
    protected $entityName;
    protected $parents = [];
    protected $parentName = null;
    protected $parentId = null;
    protected $parentEntity = null;
    protected $baseRouteName = null;
    protected $routeParams = null;
    protected $configLoaded = false;
    protected $actionName = null;
    protected $configService;
    protected $state = null;
    protected $dispatcher = null;
    protected $model = null;
    protected $requestStack = null;
//element praktycznie zawsze zmieniany, konfiguracja na zewnątrz
    protected $config = [];

    public function __construct($container, RequestStack $requestStack) {

        $this->container = $container;
        $this->requestStack = $requestStack;
        $this->model = $this->getModel($this->getEntityClass());
        $this->testContainerName();
    }

    protected function getContainerName() {

        if ($this->getRequest()->query->get('containerName')) {
            return $this->getRequest()->query->get('containerName');
        }

        return $this->getRequest()->attributes->get('containerName');
    }

    protected function getEntityPath() {

        return $this->getRequest()->attributes->get('entityPath');
    }

    protected function getValidId($id) {
        if (is_numeric($id)) {
            return $id;
        } else
            throw new \Exception("The value \"$id\" is not valid entity id");
    }

    protected function getParents() {


        if (!count($this->parents)) {
            $entityPath = $this->getEntityPath();

            $entityPathArr = explode("/", $entityPath);
            array_pop($entityPathArr);

            foreach ($entityPathArr as $key => $value) {
                if ($key % 2 == 0) {

                    $className = $this->container->get("classmapperservice")->getEntityClass($value, $this->getRequest()->getLocale());
                    $entityContainer = new \stdClass();
                    $entityContainer->id = $this->getValidId($entityPathArr[$key + 1]);
                    $entityContainer->entity = $this->container->get("prototype.objectservice")->getEntity($className, $entityContainer->id);
                    $entityContainer->entityName = $value;
                    $entityContainer->path = implode("/", array_slice($entityPathArr, 0, $key + 1));
                    $entityContainer->className = $className;
                    $this->parents[] = $entityContainer;
                }
            }
        }
        return $this->parents;
    }

    public function getRequest() {
        return $this->requestStack->getCurrentRequest();
    }

    protected function getParentEntityClassName() {

        if(null!=$this->getParent())
        {    
            return $this->getParent()->className;
        }
        return null;
    }

    

    protected function getRouteSymbol() {


        $baseRoute = $this->getBaseRouteName();
        $routeParams = $this->getRouteParams();

        if ($routeParams) {
            if (array_key_exists('parentName', $routeParams)) {

                return $baseRoute . '.' . $this->getEntityName() . '.' . $routeParams['parentName'];
            }

            return $baseRoute . '.' . $this->getEntityName();
        }
    }

    protected function getActionPrefix() {
        $parent = $this->getParent();
        $routeParams = $this->getRouteParams();

        $actionAddress = '';
        $actionAddress.=$routeParams['prefix'];

        if ($parent) {
            $actionAddress.='.' . $parent->entityName;
        }
        $actionAddress.='.' . $this->getEntityName();
        return str_replace("-", "_", $actionAddress);
    }

    protected function getDispatchName($firedAction) {
        dump($this->getActionPrefix() . '.' . $firedAction);
        return sprintf("%s.%s", $this->getActionPrefix(), $firedAction);
    }

    protected function dispatch($name, $event) {
        if (null === $this->dispatcher) {
            $this->dispatcher = $this->get('event_dispatcher');
        }
        $this->dispatcher->dispatch($this->getDispatchName($name), $event);
    }

    protected function getParentEntity($request) {
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

    protected function getDefaultParameters() {

        /* @todo defaultRoute warto się pozbyć jeśli można */
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'query' => $this->getRequest()->query->all(),
            '_route' => $this->getRequest()->attributes->get('_route'),
            'entityName' => $this->getEntityName(),
            'parents' => $this->getParents(),
            'parentName' => $this->getParentName(),
            'parentId' => $this->getParentId(),
            'prefix' => $this->getPrefix(),
            'model' => $this->model,
            'config' => $this->getConfig(),
            'routeParams' => $this->getRouteParams(),
            'state' => $this->getStates(),
            'isMasterRequest' => $this->isMasterRequest(),
            'parentEntity' => $this->getParentEntity($this->getRequest()),
            'targetContainer' => $this->getTargetContainer($this->getRequest()),
            'containerName' => $this->getContainerName(),
            'actionPrefix' => $this->getActionPrefix()
        ]);
        return $params;
    }

    protected function getRouteName($name) {
        return $this->getRouteService()->getRouteName($this->getConfig(), $name);
    }

    protected function getNextRoute($name, $default = "read") {

        if (!$name) {
            $name = $default;
        }

        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'routeParams' => $this->getRouteParams(),
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
    public function createAction(Request $request) {


        $entity = $this->model->getEntity();

        $formType = $this->getFormType($this->getEntityClass(), $this->model);
        $form = $this->makeForm($formType, $entity, 'POST', $this->getEntityName(), $this->getRouteName('create'), $this->getRouteParams());
        $form->handleRequest($request);
        $params = $this->getDefaultParameters()
                ->merge([
            'actionName' => 'create',
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
            $this->setRouteParam('id', $entity->getId());
            $this->setRouteParam('submittype', $this->getSubmitType($request));
            $this->dispatch('after.create', $event);
            $view = $this->redirectView($this->getNextRoute($this->getSubmitType($request)), 301);
            return $this->handleView($view);
        } else {
            $response = ["success" => false, "entity" => $params['entity']];
        }


        $this->dispatch('on.invalidcreate', $event);
        $view = $this->view($response)
                ->setTemplateData($params->getArray())
                ->setTemplate($this->getConfig()->get('actions.create.templates.element'))
                ->setHeader('Location', $this->getLocationUrl('create'));
        return $this->handleView($view);
    }

    public function listAction(Request $request) {



        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
        $listConfig = $this->getListConfig();
        $formType = $listConfig->getFormType();
        $queryBuilder = $listConfig->getQueryBuilder($request);

        $paginator = $this->container->get('knp_paginator');
        if ($formType) {

            $form = $this->makeForm($formType, $this->model->getEntity(), 'GET', $entityName, $this->getRouteName("list"), $this->getRouteParams());


            $lexik = $this->container->get('lexik_form_filter.query_builder_updater');
            //$form->submit($this->getRequest());
            $form->submit($this->getRequest()->query->get($form->getName()));
            $lexik->addFilterConditions($form, $queryBuilder);
        }
        $query = $queryBuilder->getQuery(); //->getResult();



        $query->setHydrationMode($this->getConfig()->get('actions.list.hydrateMode'));

        $pagination = $paginator->paginate(
                $query, $this->getRequest()->query->getInt('page', 1), $limit = $this->getConfig()->get('actions.list.limit')
        );





        $buttonRouteParams = $this->getRouteParams();
        $params = $this->get('prototype.controler.params');

        if ($this->getRequest()->getRequestFormat() == 'html') {

             


            $params = $this->getDefaultParameters()
                    ->merge(
                    [
                        'actionName' => 'list',
                        'containerName'=>$this->getContainerName(),
                        'routeName' => $routePrefix . '_new',
                        'defaultRoute' => $this->generateBaseRoute('list'),
                        'pagination' => $pagination,
                        'allRecordsCount' => $listConfig->count(),
                        'fieldsNames' => $listConfig->getFieldsNames($this->model),
                        'routePrefix' => $routePrefix,
                        'fieldsAliases' => $listConfig->getFieldsAliases(),
                        'submitType' => $this->getSubmitType($this->getRequest()),
                        'state' => $this->getStates()

                    //'form'=>$form
            ]);




            if (isset($form)) {
                $params['form'] = $form->createView();
            }

            //$buttonRouteParams = $this->getRouteParams();
            //$buttonRouteParams['containerName'] = 'container';
            $params['buttonRouteParams'] = $buttonRouteParams;
        }

//        $pagination->setParam('targetContainer', $this->getTargetContainer($this->getRequest()));
//        $pagination->setParam('entityName', $entityName);
        $this->setRouteParam('pagination', $pagination);




        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);


        $this->dispatch('before.list', $event);
        $view = $this->view([
                            "status" => "success",
                            "totalCount" => $pagination->getTotalItemCount(),
                            "page" => $pagination->getCurrentPageNumber(),
                            "items" => $pagination->getItems(),
                            "limit" => $pagination->getItemNumberPerPage()
                        ])
                        ->setTemplate($this->getConfig()->get('actions.list.templates.element'))
                        ->setTemplateData($params->getArray())->setHeader('Location', $this->getLocationUrl('list', $pagination->getQuery()));

        $this->dispatch('after.list', $event);
        return $this->handleView($view);
    }

    /**
     * Create action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function updateAction($id, Request $request) {



        $formType = $this->getFormType($this->getEntityClass(), null, $this->model);

        $event = $this->get('prototype.event');
        $this->dispatch('before.find', $event);

        $entity = $this->model->findOneById($id);


        $routePrefix = $this->getRoutePrefix();

        $updateForm = $this->makeForm($formType, $entity, 'PUT', $this->getEntityName(), $this->getAction('update'), $this->getRouteParams(), $id);


        $updateForm->handleRequest($request);

//                dump($updateForm);
//        exit();
        //  $params = $this->get('prototype.controler.params');


        $buttonRouteParams = $this->getRouteParams();
        $buttonRouteParams['containerName'] = 'container';

        $params = $this->getDefaultParameters()
                ->merge(
                [
                    'actionName' => 'update',
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


        $redirectUpdate = $this->getConfig()->get('actions.update.redirect');

        $isValid = $updateForm->isValid();
        if ($isValid) {
            $this->dispatch('before.update', $event);
            $this->model->update($entity, true);
            $this->dispatch('after.update', $event);
        }

        if ($isValid && $redirectUpdate) {
            $this->setRouteParam('submittype', $this->getSubmitType($request));
            $view = $this->redirectView($this->getNextRoute($this->getSubmitType($request)), 301);
            return $this->handleView($view);
        }

        if (!$isValid) {
            $this->dispatch('invalid.update', $event);
        }

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
    public function deleteAction($id, Request $request) {




        $event = $this->get('prototype.event');
        $this->dispatch('before.find', $event);

        $entity = $this->model->findOneById($id);

        $routePrefix = $this->getRoutePrefix();

        $event->setParams($this->getRouteParams());
        $event->setModel($this->model);

        $params = $this->getDefaultParameters()->merge($this->getRouteParams());
        $response = [];
        //@todo grid jest juz nie używany
        //$params['redirectUrl'] = $this->generateUrl($routePrefix . '_grid', $this->getRouteParams());

        if (null != $entity) {
            $this->dispatch('before.delete', $event);
            try {
                $this->model->delete($entity, true);
                $response['success'] = true;
                $response['message'] = 'ok';
            } catch (\Exception $e) {

//                if ($e->getPrevious()->getCode() == '23000') {
//                    $response['status'] = 'fail';
//                    $response['message'] = $e->getMessage();
//                }
                $response['success'] = false;
                $response['message'] = $e->getMessage();
            }
            $this->dispatch('after.delete', $event);
        }

        $view = $this->view($response)
                ->setTemplateData($params->getArray())
                ->setTemplate($this->getConfig()->get('actions.delete.templates.element'));

        return $this->handleView($view);
    }

    /**
     * Edit action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function editAction($id, Request $request) {


        $formType = $this->getFormType($this->getEntityClass(), null, $this->model);

        $event = $this->get('prototype.event');
        $this->dispatch('before.find', $event);

        $entity = $this->model->findOneById($id);
        $editForm = $this->makeForm($formType, $entity, 'PUT', $this->getEntityName(), $this->getRouteName('update'), $this->getRouteParams(), $id);

        $buttonRouteParams = $this->getRouteParams();
        $buttonRouteParams['containerName'] = 'container';

        $params = $this->get('prototype.controler.params');
        $params = $this->getDefaultParameters()
                ->merge([
            'actionName' => 'edit',
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
    public function defaultAction(Request $request) {





        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'actionName' => 'default',
            'config' => $this->getConfig(),
            'routeParams' => $this->getRouteParams(),
            'state' => $this->getStates(),
            'isMasterRequest' => $this->isMasterRequest(),
            'containerName' => 'container'
        ]);

        $event = $this->get('prototype.event');
        $event->setParams($params);

        // $this->dispatch('before.show', $event);


        $view = $this->view()->setTemplate($this->getConfig()->get('actions.default.templates.element'))
                ->setTemplateData($params->getArray());
        return $this->handleView($view);
    }

    /**
     * read action.
     * 
     * @param $id Entity id
     * @return Response
     */
    public function readAction($id, Request $request) {


        $request = $this->requestStack->getCurrentRequest();

        $event = $this->get('prototype.event');
        $this->dispatch('before.find', $event);


        $entity = $this->model->findOneById($id);
        $buttonRouteParams = $this->getRouteParams();
        $buttonRouteParams['containerName'] = 'element';
        $params = $this->get('prototype.controler.params');

        $params = $this->getDefaultParameters()
                ->merge(
                [
                    'actionName' => 'read',
                    'entity' => $entity,
                    'properties' => $this->prepareProperties($this->model, $entity),
                    'buttonRouteParams' => $buttonRouteParams,
                    'defaultRoute' => $this->generateBaseRoute('read'),
        ]);


        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('on.read', $event);

        $response = ["success" => true, "entity" => $params['entity']];


        //Render
        $view = $this->view($response)->setTemplate($this->getConfig()->get('actions.read.templates.element'))
                ->setTemplateData($params->getArray())
                ->setHeader('Location', $this->getLocationUrl('read'));
        return $this->handleView($view);
    }

    protected function getLocationUrl($action, $params = []) {
        //@todo


        $routePrefix = $this->getRoutePrefix();
        $routeParams = array_merge($this->getRouteParams(), $params);

        //die($routePrefix);
        //wyłączone ekspertymentalinie   $routeParams["containerName"] = "container";
        //@todo
        $url = $this->generateUrl($routePrefix . '-' . $action, $routeParams);
        if (!$url) {
            $url = $this->generateUrl($this->getBaseRouteName(), $routeParams);
        } else {
            // throw new \Exception("Route {$this->getBaseRouteName()} doesn't exist!");
        }

        return $url;
    }

    protected function prepareProperties($model, $entity) {

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

    /**
     * New action.
     * 
     * @return Response
     */
    public function newAction(Request $request) {


        $entity = $this->getModel($this->getEntityClass())->getEntity();
        $formType = $this->getFormType($this->getEntityClass(), null, $this->model);
        $form = $this->makeForm($formType, $entity, 'POST', $this->getEntityName(), $this->getRouteName("create"), $this->getRouteParams());
        $params = $this->get('prototype.controler.params');
        $params = $this->getDefaultParameters()
                ->merge(
                [
                    'actionName' => 'new',
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
        $view = $this->view($params->getArray())
                ->setTemplate($this->getConfig()->get('actions.create.templates.element'))
                ->setHeader('Location', $this->getLocationUrl('new'));
        return $this->handleView($view);
    }

    public function viewAction($id, Request $request) {


        $entity = $this->model->findOneById($id);

        //$viewConfig = $this->getViewConfig();
        // $viewConfig->setModel($this->model);




        $params = $this->get('prototype.controler.params');
        $params = $this->getDefaultParameters()
                ->merge([
            'actionName' => 'view',
            'entity' => $entity,
            'defaultRoute' => $this->generateBaseRoute('view'),
        ]);

        //$viewConfig->getView($params);
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);

        $this->dispatch('on.view', $event);


        //Render
        $view = $this->view($params->getArray())->setTemplate($this->getConfig()->get('actions.view.templates.element'))
                ->setHeader('Location', $this->getLocationUrl('view'));
        ;
        return $this->handleView($view);
    }

    protected function generateBaseRoute($action) {
        $params = $this->getRouteParams();
        $params['state'] = null;

        //dump( $this->generateUrl($this->getAction($action), $params, UrlGeneratorInterface::ABSOLUTE_URL));    
        return $this->generateUrl($this->getAction($action), $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Get dependency container.
     * 
     * @return Symfony\Component\DependencyInjection\ContainerInterface Dependency container
     */
    protected function getContainer() {
        return $this->get('service_container');
    }

    /**
     * Get class of controller entity.
     * 
     * @return string
     */
    protected function getEntityClass() {

        if (null == $this->entityClass) {
            $this->entityClass = $this->container->get("classmapperservice")->getEntityClass($this->getEntityName(), $this->getRequest()->getLocale());
            if (!$this->entityClass) {
                throw new \Exception('ClassName  for entityName doesn\'t exist');
            }
        }

        return $this->entityClass;
    }

    /**
     * Get mapping name of controller entity.
     * 
     * @return string
     */
    protected function getEntityName() {

        if (null == $this->entityName) {


            $entityPath = $this->getEntityPath();
            //dump($entityPath);
            $entityPathArr = explode("/", $entityPath);
            $entityName = array_pop($entityPathArr);
            $this->getRequest()->attributes->set('entityName', $entityName);
            $this->entityName = $entityName;
        }
        return $this->entityName;
    }

    /**
     * Get controller model.
     * 
     * @param string $entityClass
     * @param string $managerName
     * @return Core\BaseBundle\Model
     */
    protected function getModel($entityClass, $managerName = null) {
        if ($managerName) {
            $factory = "model_factory" . $managerName;
        } else {
            $factory = "model_factory";
        }
        $this->modelFactory = $this->get($factory);
        $this->model = $this->modelFactory->getModel($entityClass);
        return $this->model;
    }

    /**
     * Get default model for this controller.
     * 
     * @return Core\BaseBundle\Model Default model for this controller
     */
    protected function getDefaultModel() {
        $this->modelFactory = $this->get("model_factory");
        $this->model = $this->modelFactory->getModel($this->getEntityClass());

        return $this->model;
    }

    protected function getPrefix() {
        $request = $this->requestStack->getCurrentRequest();

        $prefix = $request->attributes->get('prefix');

        if (!$prefix) {
            return new \Exception("Prefix parameter dose'nt send");
        }

        return $prefix;
    }

    /**
     * Get form type class for entity.
     * 
     * @param string $entityClass Entity object
     * @param string $class Entity class
     * @return Symfony\Component\Form\Extension\Core\Type\FormType FormType
     */
    protected function getFormType($entityClass, $class = null) {

        $configurator = $this->get("prototype.formtype.configurator.service");
        $formType = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getPrefix());
        if (get_class($formType) == 'Core\PrototypeBundle\Form\FormType') {
            $formType->setModel($this->getModel($entityClass));
        }
        if (!$formType) {
            $formTypeFactory = $this->get("prototype_formtype_factory");
            $formType = $formTypeFactory->getFormType($this->getModel($entityClass));
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
    protected function makeForm($formType, $entity, $method, $entityName, $action, $routeParams, $id = null, $class = null, $url = null) {

        $routeParams = array_merge($routeParams, ["containerName" => "element"]);
        if ($this->getRequest()->getRequestFormat() == 'html') {
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
    protected function getBaseRouteName() {
        if (null == $this->baseRouteName) {
            $this->baseRouteName = $this->getRequest()->attributes->get('_route');
        }
        return $this->baseRouteName;
    }

    /**
     * Get prefix of current route.
     * 
     * @return string Current route
     */
    protected function getRoutePrefix() {

        if (null === $this->routePrefix) {
            $routeStringArray = explode("-", $this->getBaseRouteName());
            $this->routePrefix = implode("-", array_slice($routeStringArray, 0, -1));
        }
        return $this->routePrefix;
    }

    /**
     * Get route for custom action.
     * 
     * @param string $actionName Custom action name
     * @return string Custom route
     */
    protected function getAction($actionName) {
        return $this->getRoutePrefix() . "-" . $actionName;
    }

    protected function loadConfig() {

        $configurator = $this->get("prototype.configurator.service");
        $config = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getPrefix());
        return $config;
    }

    protected function getConfig() {


        if (false == $this->configService) {

            $this->configService = $this->loadConfig();
            $this->configService->merge($this->config);
        }
        return $this->configService;
    }

    protected function getRouteParams() {

        if (null == $this->routeParams) {

            $parametersArr = $this->getRequest()->attributes->all();
            $this->routeParams = $parametersArr["_route_params"];
        }
        return $this->routeParams;
    }

    protected function setRouteParam($param, $value) {
        $this->routeParams[$param] = $value;
        return $this->routeParams;
    }

    protected function getStates() {
        $params = $this->getRouteParams();
        return $this->state = $params["state"];
    }

    protected function getParent() {
        $parents = $this->getParents();
        if (count($parents) > 0) {
            return end($parents);
        }
        return false;
    }

    protected function getParentName() {
        
        if(null!=$this->getParent())
        {    
        return $this->getParent()->entityName;
        }
        
    }

    protected function getParentId() {
        if(null!=$this->getParent())
        {     
        return $this->getParent()->id;
        }
        
    }

    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH) {
        try {

            //dump($route);
            //dump($parameters);
            return $this->container->get('router')->generate($route, $parameters, $referenceType);
        } catch (\Exception $e) {
            
        }
    }

    protected function getBasePath() {
        $params = $this->getRouteParams();
        $params["state"] = null;
        return $this->generateUrl($this->getBaseRouteName(), $params);
    }

    protected function setBaseTwigParams() {
        
    }

    protected function isMasterRequest() {
        if ($this->container->get('request_stack')->getParentRequest() == null) {
            return true;
        }
        return false;
    }

    protected function testContainerName() {

        if ($this->isMasterRequest() && !$this->getRequest()->isXmlHttpRequest() && $this->getContainerName() == 'element') {
            throw $this->createNotFoundException('The site does not exist');
        }
    }

    protected function getSubmitType($request) {
        return $submitType = $request->get('submittype') != null ? $request->get('submittype') : 'read';
    }

    protected function getTargetContainer($request) {


        return $request->get('targetContainer');
    }

    protected function getListConfig() {
        $configurator = $this->get("prototype.listconfig.configurator.service");


//       dump(" {$this->getBaseRouteName()}, {$thdumpis->getEntityClass()}, {$this->getParentEntityClassName()}, {$this->getPrefix()}");
        
        
        $listConfig = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getPrefix());
        $listConfig->setModel($this->getModel($this->getEntityClass()));
        return $listConfig;
    }

    protected function getViewConfig() {
        $configurator = $this->get("prototype.viewconfig.configurator.service");
        $viewConfig = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getPrefix());
        $viewConfig->setModel($this->getModel($this->getEntityClass()));
        return $viewConfig;
    }

    protected function getRouteService() {
        return $this->container->get("prototype.routeservice");
    }

}
