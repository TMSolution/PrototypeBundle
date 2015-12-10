<?php

namespace Core\PrototypeBundle\Config;

use Core\PrototypeBundle\Config\ListConfig;

/**
 * Description of ListConfig
 *
 * @author Mariusz
 */
class AssociationListConfig extends ListConfig
{

    protected function getParentFieldNameFromRequest()
    {
        // $this->request = $this->getContainer()->get('request');
        $objectName = $this->request->get('objectName');
        $model = $this->getContainer()->get('model_factory')->getModel($objectName);
        $parentName = $this->request->get('parentName');
        $parentEntity = $this->getContainer()->get("classmapperservice")->getEntityClass($parentName, $this->request->getLocale());

        return $this->findParentFieldName($model, $parentEntity);
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

}
