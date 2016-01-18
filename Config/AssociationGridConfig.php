<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Config;

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
 * Generated with {@see TMSolution\GridBundle\Command\GridConfigCommand}.
 */
class AssociationGridConfig extends GridConfig
{

    protected $request;

    /* public function buildGrid($grid, $routePrefix)
      {

      $grid=parent::buildGrid($grid, $routePrefix);
      $this->request = $this->getContainer()->get('request');

      // $this->manipulateQuery($grid);
      //$this->configureColumn($grid);
      //$this->configureFilter($grid);
      //$this->configureExport($grid);
      //$this->configureRowButton($grid, $routePrefix);

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
       // $this->request = $this->getContainer()->get('request');
        $objectName = $this->request->get('objectName');
        $model = $this->getContainer()->get('model_factory')->getModel($objectName);
        $parentName = $this->request->get('parentName');
        $parentEntity = $this->getContainer()->get("classmapperservice")->getEntityClass($parentName, $this->request->getLocale());

        return $this->findParentFieldName($model, $parentEntity);
    }

    protected function manipulateQuery($grid)
    {

        
        $parentId = $this->request->get("parentId");
        $tableAlias = $grid->getSource()->getTableAlias();
        $parentFieldName = $this->getParentFieldNameFromRequest();


        $fieldsInfo = $this->model->getFieldsInfo();


        $tableAlias = $grid->getSource()->getTableAlias();

        $analizedFieldsInfo = $this->analizedFieldsInfo;
        $queryBuilderFn = function ($queryBuilder) use($tableAlias, $grid, $analizedFieldsInfo, $parentFieldName, $parentId) {
           


            $queryBuilder->resetDQLPart('select');
            $queryBuilder->resetDQLPart('join');

            $fields = [];

            foreach ($analizedFieldsInfo as $field => $fieldParam) {

                if (array_key_exists('association', $fieldParam) && ($fieldParam['association'] == 'ManyToOne' || $fieldParam['association'] == 'OneToOne' )) {
                   
                    $fields[] = "_{$field}.{$fieldParam['default_field']} as {$field}::{$fieldParam['default_field']}";
                    if ($fieldParam['default_field'] != 'id') {
                       
                        $fields[] = "_{$field}.id as {$field}::id";
                    }
                   
                } else {

                    $fields[] = "{$tableAlias}.{$field}";
                }
            }


            $fieldsSql = implode(',', $fields);

            $queryBuilder->select($fieldsSql);

            $fieldsArr = [];

            foreach ($analizedFieldsInfo as $field => $fieldParam) {

                if (array_key_exists('association', $fieldParam) && ($fieldParam['association'] == 'ManyToOne' || $fieldParam['association'] == 'OneToOne' )) {

                    $queryBuilder->leftJoin("$tableAlias.{$field}", "_{$field}");
                    $fieldsArr[] = $field;
                }
            }


            if ($this->manyToManyRelationExists) {
                $queryBuilder->addGroupBy($tableAlias . '.id');
            }

;
 
            if($parentFieldName){
            if (!in_array($parentFieldName, $fieldsArr)) {
                
              //  dump($parentFieldName);
                $queryBuilder->leftJoin("$tableAlias.$parentFieldName", "_{$parentFieldName}");
            }
            
            $queryBuilder->Where("_{$parentFieldName}.id=:$parentFieldName");
            $queryBuilder->setParameter("$parentFieldName", (int) $parentId);
            
            }
            else{
                throw new \Exception('Parent field name doesn\'t exist!');
            }
            
       
        };








        $grid->getSource()->manipulateQuery($queryBuilderFn);
    }

    protected function configureColumn($grid)
    {
        
    }

    protected function configureFilter($grid)
    {
        
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
        $parameters = array_merge($parametersArr["_route_params"],$parameters);


        $parentId = $this->request->get("parentId");
        $parentName = $this->request->get("parentName");

        $rowAction = new RowAction('glyphicon glyphicon-eye-open', $routePrefix . '_view', false, null, ['id' => 'button-id', 'class' => 'button-class lazy-loaded', 'data-original-title' => 'View', 'data-route-target' => '.content']);
        $rowAction->setRouteParameters($parameters);
        $grid->addRowAction($rowAction);

       /* $rowAction = new RowAction('glyphicon glyphicon-edit', $routePrefix . '_update', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Edit']);
        $rowAction->setRouteParameters($parameters);
        $grid->addRowAction($rowAction);*/

        $rowAction = new RowAction('glyphicon glyphicon-remove', $routePrefix . '_delete', false, null, ['id' => 'button-id', 'class' => 'button-class grid-button-delete', 'data-original-title' => 'Delete']);
        $rowAction->setRouteParameters($parameters);
        $grid->addRowAction($rowAction);
    }

}
