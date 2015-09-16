<?php 
/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */
namespace  AppBundle\GridConfig;

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
class ProductCategory extends GridBuilder
{

    public function buildGrid($grid,$routePrefix)
    {
        
//        $this->manipulateQuery($grid);
//        $this->configureColumn($grid);
//        $this->configureFilter($grid);
//        $this->configureExport($grid);
        $this->configureRowButton($grid,$routePrefix);
        
        return $grid;
    }
    
   protected function manipulateQuery($grid)
    {
      $tableAlias = $grid->getSource()->getTableAlias();
      $queryBuilderFn = function ($queryBuilder) use($tableAlias) {
       $queryBuilder->select($tableAlias.'.id,'.$tableAlias.'.name,'.'_productCategory.name as productCategory::name,'.'_productType.id as productType::id');

       $queryBuilder->resetDQLPart('join');
       $queryBuilder->leftJoin("$tableAlias.productCategory","_productCategory");
       $queryBuilder->leftJoin("$tableAlias.productType","_productType");
       
       $queryBuilder->addGroupBy($tableAlias.'.id');
       
       //dump($queryBuilder->getDQL()); //if you want to know how dql looks
       //dump($queryBuilder->getQuery()->getSQL()); //if you want to know how dql looks  
      };
      $grid->getSource()->manipulateQuery($queryBuilderFn);
    }

    protected function configureColumn($grid)
    {
     
                            
      $column = new TextColumn(array('id' => 'productCategory.name', 'field'=>'productCategory.name' ,'title' => 'productCategory.name', 'source' => $grid->getSource(), 'filterable' => true, 'sortable' => true));
      $grid->addColumn($column,$columnOrder=null);
                        
      $column = new NumberColumn(array('id' => 'productType.id', 'field'=>'productType.id' ,'title' => 'productType.id', 'source' => $grid->getSource(), 'filterable' => true, 'sortable' => true));
      $grid->addColumn($column,$columnOrder=null);
               
      $grid->setDefaultOrder('id', 'asc');
      $grid->setVisibleColumns(['id','name','productCategory.name','productType.id']);
      $grid->setColumnsOrder(['id','name','productCategory.name','productType.id']);

    /** field id configuration */    
    
    /*
      //$column->setSafe(false); // not convert html entities
      $column = $grid->getColumn('id'); 
      $column->setTitle('Product.id');    
      $column->manipulateRenderCell(function($value, $row) {
       //return strip_tags($value); //use this function when setSafe is false
       return $value;
      });
   
    */
    /** field name configuration */    
    
    /*
      //$column->setSafe(false); // not convert html entities
      $column = $grid->getColumn('name'); 
      $column->setTitle('Product.name');    
      $column->manipulateRenderCell(function($value, $row) {
       //return strip_tags($value); //use this function when setSafe is false
       return $value;
      });
   
    */
    /** field productCategory configuration */    
    
    /*
      //$column->setSafe(false); // not convert html entities
    $column = $grid->getColumn('productCategory.name'); 
      $column->setTitle('Product.productCategory.name');    
      $column->manipulateRenderCell(function($value, $row) {
       //return strip_tags($value); //use this function when setSafe is false
       return $value;
      });
   
    */
    /** field productType configuration */    
    
    /*
      //$column->setSafe(false); // not convert html entities
    $column = $grid->getColumn('productType.id'); 
      $column->setTitle('Product.productType.id');    
      $column->manipulateRenderCell(function($value, $row) {
       //return strip_tags($value); //use this function when setSafe is false
       return $value;
      });
   
    */
      
    }

    protected function configureFilter($grid)
    {
          /** filter columns [blocks]*/      
          $grid->setNumberPresentedFilterColumn(3);
          $grid->setShowFilters(['id','name','productCategory','productType']);
          
    }

    protected function configureExport($grid)
    {
           
          $grid->addExport(new ExcelExport('Excel'));
          $grid->addExport(new CSVExport('CSV'));
          $grid->addExport(new XMLExport('XML'));
          
    }

    protected function configureRowButton($grid,$routePrefix)
    {

        $rowAction = new RowAction('glyphicon glyphicon-eye-open', $routePrefix.'_read', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Show']);
        $rowAction->setRouteParameters(['entityName' => 'category','id']);
        $grid->addRowAction($rowAction);
    
        $rowAction = new RowAction('glyphicon glyphicon-edit', $routePrefix.'_update', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Edit']);
        $rowAction->setRouteParameters(['entityName' => 'category','id']);
        $grid->addRowAction($rowAction);
        
        $rowAction = new RowAction('glyphicon glyphicon-remove', $routePrefix.'_delete', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Delete']);
        $rowAction->setRouteParameters(['entityName' => 'category','id']);
        $grid->addRowAction($rowAction);
 
   }
 

}

