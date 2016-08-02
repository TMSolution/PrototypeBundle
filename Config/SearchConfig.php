<?php

namespace Core\PrototypeBundle\Config;

/**
 * Description of ListConfig
 *
 * @author Mariusz
 */
class SearchConfig extends ListConfig {

    protected $listConfig;
    protected $map;

    public function __construct($container, ListConfig $listConfig, $map) {
        $this->container = $container;
        $this->listConfig = $listConfig;
        $this->map = $map;
    }

    public function getFormType() {
        return $this->listConfig->getFormType();
    }

    public function getQueryBuilder() {
        $request = $this->getContainer()->get('request_stack')->getCurrentRequest();
        $fieldFromRequest = $request->get('field');
        
        $field = $this->mapField($fieldFromRequest);
        
        if(!$field) throw new \Exception("\"field\" parameter has not been set");
        $queryBuilder = $this->listConfig->getQueryBuilder();
        $queryBuilder->resetDQLPart('select');
        $queryBuilder->select("DISTINCT $field as value ");
        return $queryBuilder;
    }

    public function setModel($model) {
        $this->listConfig->setModel($model);
    }
    
    public function count()
    {
       return  $this->listConfig->count();
    }
    
    

    protected function mapField($field) {
        $output = null;
        parse_str($field, $output);
       
        if (is_array($output)) {
            return $this->testValue($output, $this->map);
        }
    }

    protected function testValue(array $map, array $map2) {
        foreach ($map as $key => $value) {
            if (array_key_exists($key, $map2)) {
                
                if (isset($map2[$key]) && is_string($map2[$key])) {
                    return $map2[$key];
                } else {

                    $result = $this->testValue($map[$key], $map2[$key]);
                }
                if ($result) {
                    return $result;
                }
                
            } else {
                throw new \Exception('Bad configuration');
            }
        }
    }

}
