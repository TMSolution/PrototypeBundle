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

  
    /**
     * List action.
     * 
     * @return Response
     */
    public function listAction() {
        
        $model = $this->getModel($this->getEntityClass());
        $grid = $this->get('grid');
        $source = new Entity($model);
        $grid->setSource($source);
        $this->buildGrid($grid);
        $view=  $this->configureView($grid);
        
        

        
        return $grid->getGridResponse($view, [
                    'entityName' => $this->getEntityName(),
                    'newActionName' => $this->getAction('new'),
                    'routeName' => $this->getRoutePrefix() . '_new',
                    'config'=>$this->getConfig()
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
    
    
  

    

}
