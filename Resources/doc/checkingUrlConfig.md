# Checking url config

>by Mariusz Piela <mariusz.piela@tmsolution.pl>

---


### Description

Command *prototype:show:url:config* verifies information about configuration for a specified url adress.

### Usage


```
// sample usage
php app/console prototype:show:url:config /panel/category/read/2
```
will return 

```
//return 
Url configuration: 
  url: /panel/category/read/2
  route: core_prototype_defaultcontroller_read
  controller: Core\PrototypeBundle\Controller\GridDefaultController::readAction
  locale: en
  entityName: category
  entityClass: AppBundle\Entity\ProductCategory

Services: 
  Base config(twig): 
      phrase: *AppBundle\Entity\ProductCategory
      servicename: app.config.productconfig
      class: Core\PrototypeBundle\Service\Config
  Grid builder config: 
      phrase: *
      servicename: prototype.gridconfig
      class: TMSolution\DataGridBundle\GridBuilder\GridBuilder
```
For example:
If you enter the specified url, the `readAction` method of the `GridDefaultController` is called. In order to work properly, it needs a number of different services, which are listed in the `Services` section, like in the example above. Here, you can verify which services will be loaded. It is possible that a service will not be used, in case it's not needed. 
You can also verify a phrase built from *route* and *entity* tags, which are matched and used to find the best suited service.


