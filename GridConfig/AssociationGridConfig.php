<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\GridConfig;

use APY\DataGridBundle\Grid\Export\ExcelExport;
use APY\DataGridBundle\Grid\Export\CSVExport;
use APY\DataGridBundle\Grid\Export\XMLExport;
use TMSolution\DataGridBundle\Grid\Column\NumberColumn;
use TMSolution\DataGridBundle\Grid\Column\TextColumn;
use TMSolution\DataGridBundle\Grid\Action\RowAction;
use TMSolution\DataGridBundle\GridBuilder\GridBuilder;

/**
 * GridConifg  for 'AppBundle\Entity\Product'.
 *
 * Generated with {@see TMSolution\GridBundle\Command\GridConfigCommand}.
 */
class AssociationGridConfig extends GridBuilder {

    protected $request;
    public function buildGrid($grid, $routePrefix) {

        $this->request=$this->getContainer()->get('request');
        $this->manipulateQuery($grid);
        //$this->configureColumn($grid);
        //$this->configureFilter($grid);
        //$this->configureExport($grid);
        $this->configureRowButton($grid,$routePrefix);

        return $grid;
    }

    public function getContainer() {
        return $this->container;
    }

    protected function findParentFieldName($model, $parentEntity) {
        $fieldsInfo = $model->getFieldsInfo();
        dump($fieldsInfo);
        foreach ($fieldsInfo as $fieldName => $fieldInfo) {
            if ($fieldInfo["is_object"] == true && $fieldInfo["object_name"] == $parentEntity && in_array($fieldInfo["association"], ["ManyToOne", "OneToOne", "ManyToMany"])) {

                return $fieldName;
            }
        }
    }
    
    protected function getParentFieldNameFromRequest()
    {
        $this->request=$this->getContainer()->get('request');
        $objectName = $this->request->get('objectName');
        $model = $this->getContainer()->get('model_factory')->getModel($objectName);
        $parentName = $this->request->get('parentName');
        $parentEntity = $this->getContainer()->get("classmapperservice")->getEntityClass($parentName, $this->request->getLocale());
         
        return $this->findParentFieldName($model,$parentEntity); 
    }

    protected function manipulateQuery($grid) {

          $parentId=$this->request->get("parentId");
          $tableAlias = $grid->getSource()->getTableAlias();
          $parentFieldName=$this->getParentFieldNameFromRequest();
          $queryBuilderFn = function ($queryBuilder) use($tableAlias,$parentFieldName,$parentId) {
         
          $queryBuilder->leftJoin("$tableAlias.$parentFieldName","_$parentFieldName");
          $queryBuilder->Where("_$parentFieldName.id=:$parentFieldName");        
          $queryBuilder->setParameter(":$parentFieldName", (int)$parentId);        
             
          };
          $grid->getSource()->manipulateQuery($queryBuilderFn);

         
    }

    protected function configureColumn($grid) {


    }

    protected function configureFilter($grid) {
       
    }

    protected function configureExport($grid) {

        $grid->addExport(new ExcelExport('Excel'));
        $grid->addExport(new CSVExport('CSV'));
        $grid->addExport(new XMLExport('XML'));
    }

    protected function configureRowButton($grid, $routePrefix) {
        
          
          $parentId=$this->request->get("parentId");
          $parentName=$this->request->get("parentName");
          
          $rowAction = new RowAction('glyphicon glyphicon-eye-open', $routePrefix.'_read', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Show']);
          $rowAction->setRouteParameters(['entityName' => 'product','id','parentName'=>$parentName,'parentId'=>$parentId ]);
          $grid->addRowAction($rowAction);

          $rowAction = new RowAction('glyphicon glyphicon-edit', $routePrefix.'_update', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Edit']);
          $rowAction->setRouteParameters(['entityName' => 'product','id','parentName'=>$parentName,'parentId'=>$parentId ]);
          $grid->addRowAction($rowAction);

          $rowAction = new RowAction('glyphicon glyphicon-remove', $routePrefix.'_delete', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Delete']);
          $rowAction->setRouteParameters(['entityName' => 'product','id','parentName'=>$parentName,'parentId'=>$parentId ]);
          $grid->addRowAction($rowAction);
         
    }

}