<?php

/**
 * Copyright (c) 2014-current. TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Controller;

use APY\DataGridBundle\Grid\Export\ExcelExport;
use APY\DataGridBundle\Grid\Export\CSVExport;
use APY\DataGridBundle\Grid\Export\XMLExport;
use TMSolution\DataGridBundle\Grid\Source\Entity;
use TMSolution\DataGridBundle\Grid\Action\RowAction;
use Symfony\Component\HttpFoundation\Request;
use Core\PrototypeBundle\Controller\DefaultController;

/**
 * Grid default controller.
 * 
 * @copyright (c) 2014-current, TMSolution
 */
class GridDefaultController extends DefaultController
{

    /**
     * grid action.
     * 
     * @return Response
     */
    public function gridAction(Request $request)
    {


        $this->init();
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();

        $grid = $this->get('grid');
        $source = new Entity($this->model);
        $grid->setSource($source);
        $grid->resetSessionData();
        $this->buildGrid($grid);
        $grid->setId($routePrefix . '_' . $entityName);

        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'container';



        $grid->setRouteUrl($this->generateUrl($routePrefix . "_ajaxgrid", $grid->getRouteParameters()));

        //config parameters for render and event broadcast
        $params = $this->get('prototype.controler.params');
        $params->setArray([
            'entityName' => $entityName,
            'newActionName' => $this->getAction('new'),
            'routeName' => $routePrefix . '_new',
            'config' => $this->getConfig(),
            'containerName' => 'container',
            'actionId' => 'default',
            'routeParams' => $this->routeParams,
            'buttonRouteParams' => $buttonRouteParams,
            'isMasterRequest' => $this->isMasterRequest()
        ]);


        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
        $event->setGrid($grid);


        $this->dispatch('before.grid', $event);
        $gridConfig = $grid->getGridConfig($this->getConfig()->get('twig_element_grid'), $params->getArray());
        $this->dispatch('after.grid', $event);
        $view = $this->view($grid->getResult($grid->getResult()))
                ->setTemplate($gridConfig->view)
                ->setTemplateData($gridConfig->parameters);

        return $this->handleView($view);
    }

    public function ajaxgridAction(Request $request)
    {

        $this->init();
        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();

        $grid = $this->get('grid');
        $source = new Entity($this->model);
        $grid->setSource($source);
        $this->buildGrid($grid);
        $grid->setId($routePrefix . '_' . $entityName);
        $grid->setRouteUrl($this->generateUrl($routePrefix . "_ajaxgrid", $grid->getRouteParameters()));
        //config parameters for render and event broadcast

        $buttonRouteParams = $this->routeParams;
        $buttonRouteParams['containerName'] = 'container';

        $params = $params = $this->get('prototype.controler.params');
        $params->setArray(
                [
                    'entityName' => $entityName,
                    'newActionName' => $this->getAction('new'),
                    'routeName' => $routePrefix . '_new',
                    'config' => $this->getConfig(),
                    'routeParams' => $this->routeParams,
                    'buttonRouteParams' => $buttonRouteParams,
                    'isMasterRequest' => $this->isMasterRequest()
        ]);


        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($this->model);
        $event->setGrid($grid);

        $this->dispatch('before.grid', $event);

        $gridConfig = $grid->getGridConfig($this->getConfig()->get('twig_element_ajaxgrid'), $params);
        $view = $this->view($grid->getResult())
                ->setTemplate($gridConfig->view)
                ->setTemplateData($gridConfig->parameters);
        $this->dispatch('after.grid', $event);
        return $this->handleView($view);
    }

    protected function getGridConfig()
    {

        $configurator = $this->get("prototype.gridconfig.configurator.service");
        $gridConfig = $configurator->getService($this->getRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());

        if (!$gridConfig) {
            $gridConfigFactory = $this->get("prototype.gridconfig");
            $gridConfig = $gridConfigFactory->getGridConfig($this->getEntityClass());
        }
        return $gridConfig;
    }

    protected function buildGrid($grid)
    {
        //@todo sprawdź czy jest ustawiony w configu

        $gridConfig = $this->getGridConfig();
        if ($gridConfig) {
            $gridConfig->buildGrid($grid, $this->getRoutePrefix());
        }
    }

    /**
     * 
     * @param RowAction $actionObject
     * @param type $entityName
     * @return RowAction
     */
    protected function setGridActionRouteParameters(RowAction $actionObject, $entityName = null)
    {
        $actionObject->setRouteParameters(['entityName' => $entityName ? $entityName : $this->getEntityName(), 'id']);
        return $actionObject;
    }

    /**
     * Sets route parameters for grid actions without ID
     * 
     * @author Łukasz Wawrzyniak <lukasz.wawrzyniak@tmsolution.pl>
     * @param \TMSolution\DataGridBundle\Grid\Action\RowAction $actionObject
     * @param string $entityName
     * @return \TMSolution\DataGridBundle\Grid\Action\RowAction
     */
    protected function setGridActionRouteParametersWithout(\TMSolution\DataGridBundle\Grid\Action\RowAction $actionObject, $action, $entityName = null)
    {
        if ($entityName === null) {
            $actionObject->setRouteParameters(array('entityName' => $entityName, 'action' => $action, 'id'));
        } else {
            $actionObject->setRouteParameters(array('action' => $action, 'id'));
        }
        return $actionObject;
    }

}
