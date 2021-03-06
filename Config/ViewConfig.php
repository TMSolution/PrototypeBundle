<?php

namespace Core\PrototypeBundle\Config;

/**
 * Description of ListConfig
 *
 * @author Mariusz
 */
class ViewConfig
{

    protected $model;
    protected $container;
    protected $backgroundColorClass = ['bgm-red', 'bgm-pink', 'bgm-purple', 'bgm-deeppurple', 'bgm-indigo', 'bgm-blue ', 'bgm-lightblue', 'bgm-cyan', 'bgm-teal', 'bgm-green', 'bgm-lightgreen', 'bgm-lime', 'bgm-yellow', 'bgm-amber', 'bgm-orange', 'bgm-deeporange', 'bgm-brown', 'bgm-gray', 'bgm-bluegray'];
    protected $chartTypes = ['line', 'bar', 'pie'];

    public function __construct($container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    protected function prepareCharts()
    {

        $fieldsInfo = $this->model->getFieldsInfo();
        $chartsArr = [];
        $i = 0;
        foreach ($fieldsInfo as $key => $value) {

            if (array_key_exists("association", $fieldsInfo[$key]) && ( $fieldsInfo[$key]["association"] == "ManyToMany" || $fieldsInfo[$key]["association"] == "OneToMany" )) {

                if ($i < 4) {
                    $chartsArr[] = $this->getSparklineMiniChart($i, $key, $fieldsInfo[$key]["object_name"]);
                } 
                elseif ($i < 5) {
                    $chartsArr[] = $this->getEasyPiePanelChart($i, $key, $fieldsInfo[$key]["object_name"]);
                }
                else {
                    $chartsArr[] = $this->getSparklinePanelChart($i, $key, $fieldsInfo[$key]["object_name"]);
                }
                
                $i++;
            }
        }

        return  ;
    }

    protected function getSparklineMiniChart($i, $key, $objectName)
    {
        $chart = $this->getContainer()->get('charts.sparkline.' . $this->chartTypes[rand(0, 2)] . '.generate');
        $chartOptions = [
            'type' => "mini",
            'htmlContainerId' => $key,
            'title' => $key,
            'backgroundColorClass' => $this->backgroundColorClass[rand(0, 18)],
            'value' => $this->getNumberOf($objectName),
            'data' => [rand(1, 50), rand(1, 50), rand(1, 50), rand(1, 50), rand(1, 50)]
        ];
        $chart->setOptions($chartOptions);

        return $chart->render();
    }

    protected function getEasyPiePanelChart($i, $key, $objectName)
    {

        $chart = $this->getContainer()->get('charts.easy.pie.generate');
        $chartOptions = [
            'type' => "panel",
            'htmlContainerId' => $key,
            'backgroundColorClass' => $this->backgroundColorClass[rand(0, 18)],
            'title' => $key,
            'value' => $this->getNumberOf($objectName),
            'listData' => [['title' => 'Quantity', 'value' => $this->getNumberOf($objectName)]],
            'trackColor' => 'rgba(255,255,255,0.2)',
            'scaleColor' => 'rgba(255,255,255,0.5)',
            'barColor' => 'rgba(255,255,255,0.7)',
            'lineWidth' => 7,
            'lineCap' => 'butt'
        ];

        $chart->setOptions($chartOptions);

        return $chart->render();
    }

    protected function getSparklinePanelChart($i, $key, $objectName)
    {
        $chart = $this->getContainer()->get('charts.sparkline.' . $this->chartTypes[rand(0, 2)] . '.generate');
        $chartOptions = [
            'type' => "panel",
            'htmlContainerId' => $key,
            'title' => $key,
            'backgroundColorClass' => $this->backgroundColorClass[$i],
            'data' => [rand(1, 50), rand(1, 50), rand(1, 50), rand(1, 50), rand(1, 50)],
            'listData' => [['title' => 'Quantity', 'value' => $this->getNumberOf($objectName)]]
        ];
        $chart->setOptions($chartOptions);

        return $chart->render();
    }

  

    protected function getNumberOf($objectName)
    {

        $model = $this->getContainer()->get("model_factory")->getModel($objectName);
        $result = $model->getQueryBuilder('u')->select("count(u.id) as counter")->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getView($options)
    {
        $options['chartWidgets'] = $this->prepareCharts();
        return $options;
    }

}
