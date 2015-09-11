# PrototypeBundle installation description

>by Lukasz Sobieraj <lukasz.sobieraj@tmsolution.pl>

---

### Installation

To install the application tools follow the steps:

1.Create a new Symfony project. 

2.Add the PrototypeBundle using the line below
```
#composer.json
"require":  {
        "tmsolution/prototype-bundle": "dev-master"
            }
```
and then clone the PhantomBundle 
***if you have access to the TMSolution account, otherwise fork the bundle on your own github account***

3.In the AppKernel.php file add below lines:
```
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new APY\DataGridBundle\APYDataGridBundle(),
            new BeSimple\I18nRoutingBundle\BeSimpleI18nRoutingBundle(),
            new TMSolution\DataGridBundle\TMSolutionDataGridBundle(),
            new Core\ModelBundle\CoreModelBundle(),
            new Core\ClassMapperBundle\CoreClassMapperBundle(),
            new Core\PrototypeBundle\CorePrototypeBundle(),
            new PhantomBundle\PhantomBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
```

4.In the next step  database schema is needed. Please create it follow the command below:
```
doctrine:schema:create
```

5.Generate a classmapper file with command:
```
classmapper:generate:friendlynames
```
Paramater is the name of bundle or bundle's.

6.Add the line below to the import block:
```
#config.yml
    { resource: classmapper.yml }
```
and in the twig block in config.yml  add the command below:
```
#config.yml
    globals:
        classmapperservice: "@classmapperservice"
```




###Building application process###

1.Assets install with the command:
```
 php app/console assets:install
```

2.Generate the configs for grid use the command below:
```
datagrid:generate:grid:config
```
as a parameters fill the path to the entity, e.g. PhantomBundle/Entity/Product,where Product is the name of entity.


3.In the file app/config/routing add the lines below:
```
fos_js_routing:
    resource: "@FOSJsRoutingBundle/Resources/config/routing/routing.xml" 
```
and
```
core_prototype:
    resource: "@CorePrototypeBundle/Resources/config/routing.yml"
    prefix:   /
```

4.Create two files:
.bowerrc (type of file bowerrc)
with the content:
```
{
    "directory": "web/assets/vendor/"
}
```
bower.json 
with the content:
```
{
  "name": "app-name",
  "version": "0.0.1",
  "dependencies": {
  "bootstrap": "master",
  "blockUI": "master"
      },
  "private": true
}
```
This files have to be  in the main folder of project.

5.The last step require run a command below:
```
bower install
```
More about a correctly installation you can find here: http://symfony.com/doc/current/cookbook/frontend/bower.html


In the toolbar use e.g:
```
http://localhost/testproject/web/app_dev.php/panel/product/list
```
where product is the name of entity 

