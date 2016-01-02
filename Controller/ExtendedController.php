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
class ExtendedController extends DefaultController
{

    
    
    
    
    /**
     * Create action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function updateAction($id,Request $request) {

        $id= 365;
        return $this->updateAction($id,$request);
    }

    /**
     * Delete action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function deleteAction($id, Request $request) {

        $id= doSomethingWithId;
        return parent::deleteAction($id,$request);
    }

    /**
     * Edit action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function editAction($id, Request $request) {

        $id= 365;
        return parent::editAction($id, $request);
    }

    /**
     * read action.
     * 
     * @param $id Entity id
     * @return Response
     */
    public function readAction($id, Request $request) {

        $id= doSomethingWithId;
        return parent::readAction($id,$request);
    }
    
    
     /**
     * view action.
     * 
     * @param $id Entity id
     * @return Response
     */
    public function viewAction($id, Request $request) {

        $id= doSomethingWithId;
        return parent::viewAction($id,$request);
        
    }
    
    

}
