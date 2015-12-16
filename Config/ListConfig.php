<?php

namespace Core\PrototypeBundle\Config;

/**
 * Description of ListConfig
 *
 * @author Mariusz
 */
class ListConfig
{

    //put your code here


    protected $manyToManyRelationExists = false;
    protected $model;
    protected $container;
    protected $fieldsNames = [];
    protected $fields;
    protected $prepared;
    protected $fieldsAliases = [];

    public function __construct($container, $formTypeClass=null)
    {
        $this->container = $container;
        $this->formTypeClass = $formTypeClass;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    public function setModel($model)
    {
        $this->model = $model;
        $this->prepareFields();
    }

    public function getLabelPrefix($entityClass)
    {

        $entityClassArr = explode("\\", $entityClass);
        $namespaceArr = [];
        foreach ($entityClassArr as $element) {

            if ($element == 'Entity') {

                break;
            }
            $namespaceArr[] = strtolower($element);
        }
        $namespaceArr[count($namespaceArr) - 1] = str_replace('bundle', '', $namespaceArr[count($namespaceArr) - 1]);
        return implode(".", $namespaceArr);
    }

    //do posprzątania - identyczna funkcjonalność jak w grid configu
    protected function analizeFieldsInfo($fieldsInfo)
    {



        foreach ($fieldsInfo as $key => $value) {

            if (array_key_exists("association", $fieldsInfo[$key]) && ( $fieldsInfo[$key]["association"] == "ManyToOne" || $fieldsInfo[$key]["association"] == "OneToOne" )) {

                if ($fieldsInfo[$key]["association"] == "ManyToMany") {
                    $this->manyToManyRelationExists = true;
                }

                $model = $this->getContainer()->get("model_factory")->getModel($fieldsInfo[$key]["object_name"]);
                if ($model->checkPropertyByName("name")) {
                    $fieldsInfo[$key]["default_field"] = "name";
                    $fieldsInfo[$key]["default_field_type"] = "Text";
                } else {
                    $fieldsInfo[$key]["default_field"] = "id";
                    $fieldsInfo[$key]["default_field_type"] = "Number";
                }
            } elseif (array_key_exists("association", $fieldsInfo[$key]) && ( $fieldsInfo[$key]["association"] == "ManyToMany" || $fieldsInfo[$key]["association"] == "OneToMany" )) {
                unset($fieldsInfo[$key]);
            }
        }

        return $fieldsInfo;
    }

    public function getFieldsNames()
    {
        return $this->fieldsNames;
    }

    public function getFieldsAliases()
    {
        return $this->fieldsAliases;
    }

    protected function prepareFields()
    {


        $tableAlias = $this->model->getEntityName();

        //   if(!$this->prepared){
        $analizedFieldsInfo = $this->analizeFieldsInfo($this->model->getFieldsInfo());
        $this->fields = [];

        $labelPrefix = $this->getLabelPrefix($this->model->getEntityClass()) . '.';
        foreach ($analizedFieldsInfo as $field => $fieldParam) {

            if (array_key_exists('association', $fieldParam) && ($fieldParam['association'] == 'ManyToOne' || $fieldParam['association'] == 'OneToOne' )) {

                $fieldAlias = "{$field}__{$fieldParam['default_field']}";
                $fieldName = strtolower($labelPrefix . $this->model->getEntityName() . ".{$field}.{$fieldParam['default_field']}");
                $this->fieldsNames[$fieldName] = ['name' => $fieldAlias, 'isAssociatedObjectId' => false, 'association' => true, 'idField' => "{$field}__id"];
                $this->fieldsAliases[$fieldAlias] = $fieldName;
                $this->fields[] = "_{$field}.{$fieldParam['default_field']} as $fieldAlias ";

                if ($fieldParam['default_field'] != 'id') {
                    $fieldAlias = "{$field}__id";
                    $fieldName = strtolower($labelPrefix . $this->model->getEntityName() . ".{$field}.id");
                    $this->fieldsNames[$fieldName] = ['name' => strtolower($fieldAlias), 'isAssociatedObjectId' => true, 'association' => true];
                    $this->fieldsAliases[$fieldAlias] = $fieldName;
                    $this->fields[] = "_{$field}.id as $fieldAlias";
                }
            } else {

                $fieldName = "{$tableAlias}.{$field}";
                $this->fields[] = $fieldName;
                $this->fieldsAliases[$field] = $labelPrefix . $fieldName;
                $this->fieldsNames[$labelPrefix . $fieldName] = ['name' => $fieldName, 'isAssociatedObjectId' => false, 'association' => false];
            }
        }
        //}
        //$this->prepared=true;
        return $this->fields;
    }

    public function getFormType()
    {

        if ($this->formTypeClass) {
            $formClass = $this->formTypeClass;
            $formType = new $formClass();
        } else {
            $formType = new \Core\PrototypeBundle\Form\FilterFormType();
        }

        $formType->setModel($this->model);
        dump($formType);
        return $formType;
    }

    public function getQuery()
    {

        return $this->getQueryBuilder()->getQuery(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    public function getQueryBuilder()
    {


        $analizedFieldsInfo = $this->analizeFieldsInfo($this->model->getFieldsInfo());
        $tableAlias = $this->model->getEntityName();
        $this->prepareFields($tableAlias, $this->model);
        $queryBuilder = $this->model->getQueryBuilder($tableAlias);
        $fieldsSql = implode(',', $this->fields);
        $queryBuilder->select($fieldsSql);


        foreach ($analizedFieldsInfo as $field => $fieldParam) {

            if (array_key_exists('association', $fieldParam) && ($fieldParam['association'] == 'ManyToOne' || $fieldParam['association'] == 'OneToOne' )) {


                $queryBuilder->leftJoin("$tableAlias.{$field}", "_{$field}");
            }
        }

        if ($this->manyToManyRelationExists) {
            $queryBuilder->addGroupBy($tableAlias . '.id');
        }


        return $queryBuilder; //->getQuery(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

}
