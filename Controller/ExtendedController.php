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
class ExtendedController extends DefaultController {

    /**
     * Create action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function updateAction($id, Request $request) {

    
        return parent::updateAction($this->getId(), $request);
    }

    /**
     * Delete action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function deleteAction($id, Request $request) {


        return parent::deleteAction($this->getId(), $request);
    }

    /**
     * Edit action.
     * 
     * @param id Entity id
     * @return Response
     */
    public function editAction($id, Request $request) {


        return parent::editAction($this->getId(), $request);
    }

    /**
     * read action.
     * 
     * @param $id Entity id
     * @return Response
     */
    public function readAction($id, Request $request) {


        return parent::readAction($this->getId(), $request);
    }

    /**
     * view action.
     * 
     * @param $id Entity id
     * @return Response
     */
    public function viewAction($id, Request $request) {


        return parent::viewAction($this->getId(), $request);
    }

    protected function getIdFinder() {
        $this->init();
        $configurator = $this->get("prototype.idfinder.configurator.service");

        $idFinder = $configurator->getService($this->getBaseRouteName(), $this->getEntityClass(), $this->getParentEntityClassName(), $this->getActionId());
        if ($idFinder) {
            return $idFinder;
        } else {

            throw new \Exception('IdFinder doesn\'t exists!');
        }
    }

    public function getId() {
        $id = $this->getIdFinder()->getId();
        if ($id) {
            return $id;
        } else {
            throw new \Excepiton('No id set');
        }
    }

}
