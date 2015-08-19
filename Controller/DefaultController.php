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

/**
 * Default controller.
 * 
 * @copyright (c) 2014-current, TMSolution
 */
class DefaultController extends BaseController {

    protected $objectName = null;
    protected $routePrefix = null;
    protected $entityName;
    protected $routeName;
    protected $configLoaded = false;
    protected $configService;
    //element praktycznie zawsze zmieniany, konfiguracja na zewnątrz
    protected $config = [
            /*
              //twig templates for Elements
              'twig_element_create' => 'CorePrototypeBundle:Element:create.html.twig',
              'twig_element_list' => 'CorePrototypeBundle:Element:list.html.twig',
              'twig_element_ajax_list' => 'CorePrototypeBundle:Element:list.ajax.html.twig',
              'twig_element_update' => 'CorePrototypeBundle:Element:update.html.twig',
              'twig_element_read' => 'CorePrototypeBundle:Element:read.html.twig',
              'twig_element_error' => 'CorePrototypeBundle:Element:error.html.twig',
              //twig templates for Containers
              'twig_container_create' => 'CorePrototypeBundle:Container:create.html.twig',
              'twig_container_list' => 'CorePrototypeBundle:Container:list.html.twig',
              'twig_container_update' => 'CorePrototypeBundle:Container:update.html.twig',
              'twig_container_read' => 'CorePrototypeBundle:Container:read.html.twig',
              'twig_container_error' => 'CorePrototypeBundle:Container:error.html.twig',
              //grid service
              'grid_config_service' => null,
              //form ttype class
              'formtype_class' => null */
    ];

    /**
     * Create action.
     * 
     * @return Response

      )
     */
    public function createAction() {

        $request = $this->getRequest();
        $model = $this->getModel($this->getEntityClass());
        $entity = $model->getEntity();
        $formType = $this->getFormType($this->getEntityClass(), null);
        $form = $this->makeForm($formType, $entity, 'POST', $this->getEntityName(), $this->getAction('create'));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity = $model->create($entity, true);
            return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_read', ['entityName' => $this->getEntityName(), 'id' => $entity->getId()]));
        }

        return $this->render($this->getConfig()->get('twig_element_create'), [
                    'entity' => $entity,
                    'form' => $form->createView(),
                    'config' => $this->getConfig()
        ]);
    }

    /**
     * Read action.
     * 
     * @return Response
     * @throws \BadMethodCallException Not implemented yet
     */
    /* public function listAction()
      {
      throw new \BadMethodCallException("Not implemented yet");
      } */

    /**
     * Create action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function updateAction($id) {

        $request = $this->getRequest();
        $formType = $this->getFormType($this->getEntityClass(), null);
        $model = $this->getModel($this->getEntityClass());
        $entity = $model->findOneById($id);
        $updateForm = $this->makeForm($formType, $entity, 'PUT', $this->getEntityName(), $this->getAction('update'), $id);
        $updateForm->handleRequest($request);

        if ($updateForm->isValid()) {
            $model->update($entity, true);
            return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_read', ['entityName' => $this->getEntityName(), 'id' => $id]));
        }

        return $this->render($this->getConfig()->get('twig_element_update'), array(
                    'entity' => $entity,
                    'form' => $updateForm->createView(),
                    'entityName' => $this->getEntityName(),
                    'listActionName' => $this->getAction('list'),
                    'updateActionName' => $this->getAction('update'),
                    'config' => $this->getConfig()
        ));
    }

    /**
     * Delete action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function deleteAction($id) {

        $model = $this->getModel($this->getEntityClass());
        //dump($model);
        $entity = $model->findOneById($id);
        if (null != $entity) {
            $model->delete($entity, true);
        }
        return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_list', ['entityName' => $this->getEntityName()]));
    }

    /**
     * Edit action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function editAction($id) {
        $formType = $this->getFormType($this->getEntityClass(), null);
        $model = $this->getModel($this->getEntityClass());
        $entity = $model->findOneById($id);
        $editForm = $this->makeForm($formType, $entity, 'PUT', $this->getEntityName(), $this->getAction('update'), $id);

        return $this->render($this->getConfig()->get('twig_element_update'), [
                    'entity' => $entity,
                    'form' => $editForm->createView(),
                    'entityName' => $this->getEntityName(),
                    'listActionName' => $this->getAction('list'),
                    'updateActionName' => $this->getAction('update'),
                    'config' => $this->getConfig()
        ]);
    }

    /**
     * Show action.
     * 
     * @param $id Entity id
     * @return Response
     */
    public function readAction($id) {
        $model = $this->getModel($this->getEntityClass());
        $entity = $model->findOneById($id);

        return $this->render($this->getConfig()->get('twig_element_read'), array(
                    'entity' => $entity,
                    'entityName' => $this->getEntityName(),
                    'editActionName' => $this->getAction('edit'),
                    'listActionName' => $this->getAction('list'),
                    'deleteActionName' => $this->getAction('delete'),
                    'properties' => $this->prepareProperties($model, $entity),
                    'config' => $this->getConfig()
        ));
    }

    protected function prepareProperties($model, $entity) {

        $properties = [];
        foreach ($model->getMetadata()->fieldMappings as $field) {

            $method = $model->checkMethod($entity, $field['fieldName']);

            if ($method) {


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
    public function newAction() {
        $entity = $this->getModel($this->getEntityClass())->getEntity();
        $formType = $this->getFormType($this->getEntityClass(), null);
        $form = $this->makeForm($formType, $entity, 'POST', $this->getEntityName(), $this->getAction('create'));
        return $this->render($this->getConfig()->get('twig_element_create'), array(
                    'entity' => $entity,
                    'form' => $form->createView(),
                    'entityName' => $this->getEntityName(),
                    'listActionName' => $this->getAction('list'),
                    'config' => $this->getConfig()
        ));
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
        if (null == $this->objectName) {
            $this->objectName = $this->getRequest()->attributes->get('objectName');
        }
        return $this->objectName;
    }

    /**
     * Get mapping name of controller entity.
     * 
     * @return string
     */
    protected function getEntityName() {
        if (null == $this->entityName) {
            $this->entityName = $this->getRequest()->attributes->get('entityName');
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
    protected function getModel($objectName, $managerName = null) {
        if ($managerName) {
            $factory = "model_factory_" . $managerName;
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
    protected function getDefaultModel() {
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
    protected function getFormType($objectName, $class = null) {

        $formTypeClass = $this->getConfig()->get("formtype_class");
        if (!empty($formTypeClass)) {
            $formType = new $formTypeClass;
        } else {
            $formTypeFactory = $this->get("prototype_formtype_factory");
            $formType = $formTypeFactory->getFormType($objectName, $class);
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
    protected function makeForm($formType, $entity, $method, $entityName, $action, $id = null, $class = null, $url = null) {
        if ($url && !$id) {
            $url = $this->generateUrl($url);
        } elseif ($url && $id) {
            $url = $this->generateUrl($action, ['id' => $id]);
        } elseif (empty($id)) {
            $url = $this->generateUrl($action, array('entityName' => $entityName));
        } else {
            //@todo: moze byc z tym problem
            $url = $this->generateUrl($action, array('entityName' => $entityName, 'id' => $id));
        }
        if (empty($class)) {
            $class = 'form-horizontal';
        }

        $form = $this->createForm($formType, $entity, array(
            'action' => $url, //$this->generateUrl('user_create'),
            'method' => $method,
            'attr' => array('class' => $class),
        ));

        return $form;
    }

    /**
     * Get current route.
     * 
     * @return string Current route
     */
    protected function getRouteName() {
        if (null == $this->routeName) {
            $this->routeName = $this->getRequest()->attributes->get('_route');
        }
        return $this->routeName;
    }

    /**
     * Get prefix of current route.
     * 
     * @return string Current route
     */
    protected function getRoutePrefix() {
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
    protected function getAction($actionName) {
        return $this->getRoutePrefix() . "_" . $actionName;
    }

    protected function loadConfig() {

        $configService = $this->getRequest()->attributes->get("config");
        if ($configService) {
            if ($this->has($configService)) {
                $config = $this->get($configService);
            } else {
                throw new \Exception("Config Service name was found, but service dosen't exists");
            }
        } else {

            $config = $this->get("prototype_config");
        }
       // dump($config);
        return $config;
    }

    protected function getConfig() {


        if (false == $this->configService) {

            $this->configService = $this->loadConfig();
            $this->configService->merge($this->config);
        }
        return $this->configService;
    }

}
