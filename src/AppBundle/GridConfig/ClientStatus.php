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
 * GridConifg  for 'AppBundle\Entity\ClientStatus'.
 *
 * Generated with {@see TMSolution\GridBundle\Command\GridConfigCommand}.
 */
class ClientStatus extends GridBuilder
{

    public function buildGrid($grid,$routePrefix)
    {
        /**
        * Uncomment to use method
        */
        
        //$this->manipulateQuery($grid);
        //$this->configureColumn($grid);
        //$this->configureFilter($grid);
        $this->configureExport($grid);
        $this->configureRowButton($grid,$routePrefix);
        
        return $grid;
    }
    
   protected function manipulateQuery($grid)
    {
       
       $tableAlias = $grid->getSource()->getTableAlias();
      $queryFn = function ($query) use($tableAlias) {
        $query->select($tableAlias.'.id,'.$tableAlias.'.name,'.$tableAlias.'.name2');
        };
      $grid->getSource()->manipulateQuery($queryFn);
    }

    protected function configureColumn($grid)
    {
    
     $grid->setDefaultOrder('id', 'asc');
     $grid->setVisibleColumns(['id','name','name2']);
     $grid->setColumnsOrder(['id','name','name2']);

     
    /** field id configuration */ 
   
    /*
    $column = $grid->getColumn('id');
    //$column->setSafe(false); // for html rendering
    
    $column->manipulateRenderCell(function($value, $row) {
    //$row->getField('field_name'); // method to get field from row
    //return strip_tags($value); // use strip_tags for not safety
    return $value;
          });
    $column->setTitle('ClientStatus.id');
    */
    
    /** field name configuration */ 
   
    /*
    $column = $grid->getColumn('name');
    //$column->setSafe(false); // for html rendering
    
    $column->manipulateRenderCell(function($value, $row) {
    //$row->getField('field_name'); // method to get field from row
    //return strip_tags($value); // use strip_tags for not safety
    return $value;
          });
    $column->setTitle('ClientStatus.name');
    */
    
    /** field name2 configuration */ 
   
    /*
    $column = $grid->getColumn('name2');
    //$column->setSafe(false); // for html rendering
    
    $column->manipulateRenderCell(function($value, $row) {
    //$row->getField('field_name'); // method to get field from row
    //return strip_tags($value); // use strip_tags for not safety
    return $value;
          });
    $column->setTitle('ClientStatus.name2');
    */
    
      

    }

    protected function configureFilter($grid)
    {
          /** filter columns [blocks]*/      
          $grid->setNumberPresentedFilterColumn(3);
          $grid->setShowFilters(['id','name','name2']);
          
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
        $rowAction->setRouteParameters(['entityName' => 'clientstatus','id']);
        $grid->addRowAction($rowAction);
    
        $rowAction = new RowAction('glyphicon glyphicon-edit', $routePrefix.'_update', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Edit']);
        $rowAction->setRouteParameters(['entityName' => 'clientstatus','id']);
        $grid->addRowAction($rowAction);
        
        $rowAction = new RowAction('glyphicon glyphicon-remove', $routePrefix.'_delete', false, null, ['id' => 'button-id', 'class' => 'button-class', 'data-original-title' => 'Delete']);
        $rowAction->setRouteParameters(['entityName' => 'clientstatus','id']);
        $grid->addRowAction($rowAction);
    }

}

