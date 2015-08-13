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
class GridDefaultController extends DefaultController {

    protected $hiddenGridFilters = ['id'];
 
    
    /**
     * Read action.
     * 
     * @return Response
     */
    public function listAction() {
        
        $model = $this->getModel($this->getEntityClass());
        $grid = $this->get('grid');
        $source = new Entity($model);
        //$businessQueryBuilder = $model->getQueryBuilder();
        //$source->initQueryBuilder($businessQueryBuilder);
        $grid->setSource($source);
        
        $this->buildGrid($grid);
        $view=  $this->configureView($grid);
        
        

        
        return $grid->getGridResponse($view, [
                    'entityName' => $this->getEntityName(),
                    'newActionName' => $this->getAction('new'),
                    'routeName' => $this->getRoutePrefix() . '_new'
        ]);
    }
    
    
    protected function buildGrid($grid)
    {
        $gridConfigServiceName = 'grid.' . str_replace('\\Entity\\', '.', $this->getEntityClass());
        if ($this->has($gridConfigServiceName)) {
            $grid=$this->get($gridConfigServiceName)->buildGrid($grid);
        }
        
    }
    
    
    protected function configureView($grid)
    {
        
        if ($this->getRequest()->isXmlHttpRequest()) {
            $view = $this->templates['ajax_list'];
        } else {

            $grid->resetSessionData();             
            $view = $this->templates['list'];
        }
        
        return $view;
    }

    

    /**
     * Grid configuration.
     * 
     * @author Łukasz Wawrzyniak <lukasz.wawrzyniak@tmsolution.pl>
     * @param \TMSolution\DataGridBundle\Grid\Grid $grid
     * @return \TMSolution\DataGridBundle\Grid\Grid
     */
    protected function configureGrid(\TMSolution\DataGridBundle\Grid\Grid $grid, $filterColumns = 3, $order = 'desc') {
        $grid->setDefaultOrder('id', $order);
        $grid->setNumberPresentedFilterColumn($filterColumns);
        if (!empty($this->hiddenGridFilters)) {
            $grid->setHideFilters($this->hiddenGridFilters);
        }
        if (!empty($this->gridColumns)) {
            $grid->setVisibleColumns($this->gridColumns);
            $grid->setColumnsOrder($this->gridColumns);
        }
        /* @TODO nie dziala, trzeba naprawić ! */
        $grid->setActionsColumnSize(50);

        return $grid;
    }
   


    /**
     * 
     * @param RowAction $actionObject
     * @param type $entityName
     * @return RowAction
     */
    protected function setGridActionRouteParameters(RowAction $actionObject, $entityName = null) {
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
    protected function setGridActionRouteParametersWithout(\TMSolution\DataGridBundle\Grid\Action\RowAction $actionObject, $action, $entityName = null) {
        if ($entityName === null) {
            $actionObject->setRouteParameters(array('entityName' => $entityName, 'action' => $action, 'id'));
        } else {
            $actionObject->setRouteParameters(array('action' => $action, 'id'));
        }
        return $actionObject;
    }

    /**
     * Adds grid export
     * @author Łukasz Wawrzyniak <lukasz.wawrzyniak@tmsolution.pl>
     * @param \TMSolution\DataGridBundle\Grid\Grid $grid
     * @return \TMSolution\DataGridBundle\Grid\Grid
     */
    protected function addGridExport(\TMSolution\DataGridBundle\Grid\Grid $grid) {
        $grid->addExport(new ExcelExport('Excel Eksport'));
        $grid->addExport(new CSVExport('CSV Eksport'));
        $grid->addExport(new XMLExport('XML Eksport'));
        return $grid;
    }

}
