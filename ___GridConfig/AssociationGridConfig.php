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
use TMSolution\DataGridBundle\GridConfig\GridConfig;

/**
 * GridConifg  for 'AppBundle\Entity\Product'.
 *
 * Generated with {@see TMSolution\GridConfig\Command\GridConfigCommand}.
 */
class AssociationGridConfig extends GridConfig
{

    protected $request;

    /* public function buildGrid($grid, $routePrefix) {


      $this->request=$this->getContainer()->get('request');
      $this->manipulateQuery($grid);
      $this->configureColumn($grid);
      //$this->configureFilter($grid);
      //$this->configureExport($grid);
      $this->configureRowButton($grid,$routePrefix);

      return $grid;
      } */

    public function getContainer()
    {
        return $this->container;
    }

    protected function findParentFieldName($model, $parentEntity)
    {
        $fieldsInfo = $model->getFieldsInfo();

        foreach ($fieldsInfo as $fieldName => $fieldInfo) {
            
            
            
            if ($fieldInfo["is_object"] == true && $fieldInfo["object_name"] == $parentEntity && in_array($fieldInfo["association"], ["ManyToOne", "OneToOne", "ManyToMany"])) {

                return $fieldName;
            }
        }
    }

    protected function getParentFieldNameFromRequest()
    {


        $parentName = $this->request->get('parentName');
        if ($parentName) {

            $parentEntity = $this->getContainer()->get("classmapperservice")->getEntityClass($parentName, $this->request->getLocale());
            
           
            if (!$parentEntity) {
                throw new \Exception('Parameter "$parentEntity" is null!');
            }
        } else {
            throw new \Exception('Parameter "parentName" required!');
        }
        
       // dump( $this->findParentFieldName($this->model, $parentEntity));
        return $this->findParentFieldName($this->model, $parentEntity);
    }

    protected function manipulateQuery($grid)
    {



        $parentId = $this->request->get("parentId");
        $tableAlias = $grid->getSource()->getTableAlias();
        $parentFieldName = $this->getParentFieldNameFromRequest();


        $queryBuilderFn = function ($queryBuilder) use($tableAlias, $parentFieldName, $parentId) {

            $queryBuilder->leftJoin("$tableAlias.$parentFieldName", "_$parentFieldName");
            $queryBuilder->Where("_$parentFieldName.id=:$parentFieldName");
            $queryBuilder->setParameter("$parentFieldName", (int) $parentId);
        };
        $grid->getSource()->manipulateQuery($queryBuilderFn);
    }

    protected function configureExport($grid)
    {

        $grid->addExport(new ExcelExport('Excel'));
        $grid->addExport(new CSVExport('CSV'));
        $grid->addExport(new XMLExport('XML'));
    }

    protected function configureRowButton($grid, $routePrefix)
    {

        /* @todo, aftert test - add to oryginal data-grid command generator */

        $parametersArr = $this->request->attributes->all();
        $parameters = ["id", "containerName" => "container", "actionId" => "default"];
        $parameters = array_merge($parameters, $parametersArr["_route_params"]);


        $parentId = $this->request->get("parentId");
        $parentName = $this->request->get("parentName");

        $rowAction = new RowAction('glyphicon glyphicon-eye-open', $routePrefix . '_read', false, null, ['id' => 'button-id', 'class' => 'button-class lazy-loaded', 'data-original-title' => 'Show']);
        $rowAction->setRouteParameters($parameters);
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction('glyphicon glyphicon-edit', $routePrefix . '_update', false, null, ['id' => 'button-id', 'class' => 'button-class lazy-loaded', 'data-original-title' => 'Edit']);
        $rowAction->setRouteParameters($parameters);
        $grid->addRowAction($rowAction);

        $rowAction = new RowAction('glyphicon glyphicon-remove', $routePrefix . '_delete', false, null, ['id' => 'button-id', 'class' => 'button-class lazy-loaded', 'data-original-title' => 'Delete']);
        $rowAction->setRouteParameters($parameters);
        $grid->addRowAction($rowAction);
    }

}
