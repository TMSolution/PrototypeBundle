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
use Core\PrototypeBundle\Controller\DefaultController;

/**
 * Grid default controller.
 * 
 * @copyright (c) 2014-current, TMSolution
 */
class GridDefaultController extends DefaultController
{

    /**
     * List action.
     * 
     * @return Response
     */
    public function gridAction()
    {


        $entityName = $this->getEntityName();
        $routePrefix = $this->getRoutePrefix();
        $model = $this->getModel($this->getEntityClass());
        $grid = $this->get('grid');
        $source = new Entity($model);
        $grid->setSource($source);
        $this->buildGrid($grid);
        $view = $this->configureView($grid);

        //config parameters for render and event broadcast
        $params = [
            'entityName' => $entityName,
            'newActionName' => $this->getAction('new'),
            'routeName' => $routePrefix . '_new',
            'config' => $this->getConfig()
        ];

        //Create event broadcast.
        $event = $this->get('prototype.event');
        $event->setParams($params);
        $event->setModel($model);
        $event->setGrid($grid);
        
        $this->get('event_dispatcher')->dispatch($routePrefix . '.' . $entityName . '.' . 'grid.success', $event);

        return $grid->getGridResponse($view, $params);
    }

    protected function getGridConfig()
    {

        $configurator = $this->get("prototype.grid.configurator.service");
        $gridConfig = $configurator->getService($this->getRouteName(), $this->getEntityClass());
        if (!$gridConfig) {
            $gridConfigFactory = $this->get("prototype_grid_config_factory");
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

    protected function configureView($grid)
    {

        if ($this->getRequest()->isXmlHttpRequest()) {
            $view = $this->getConfig()->get('twig_element_ajaxlist');
        } else {

            $grid->resetSessionData();
            $view = $this->getConfig()->get('twig_element_list');
        }

        return $view;
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
