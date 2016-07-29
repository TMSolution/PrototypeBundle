<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\PrototypeBundle\Generator;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 * 
 */
class FilterFormTypeGenerator extends FormTypeGenerator{
 
    
    
    
     protected $types = [
         
        "string" => "Filters\TextFilterType::class",
        "text" => "Filters\TextFilterType::class",
        "blob" => "Filters\TextFilterType::class",
        
        "integer" => "Filters\NumberRangeFilterType::class",
        "smallint" => "Filters\NumberRangeFilterType::class",
        "bigint" => "Filters\NumberRangeFilterType::class",
        "decimal" => "Filters\NumberRangeFilterType::class",
        "float" => "Filters\NumberRangeFilterType::class",
        "duble" => "Filters\NumberRangeFilterType::class",
        "boolean" => "Filters\Filters\BooleanFilterType",
        "datetime" => "Filters\DateTimeRangeFilterType::class",
        "datetimetz" => "Filters\DateTimeRangeFilterType::class",
        "date" => "Filters\DateRangeFilterType::class",
        
        "time" => "Filters\TextFilterType::class",
        "array" => "array",
        "simple_array" => "array",
        "json_array" => "array",
         
        "object" => "entity"
    ];

    
}
