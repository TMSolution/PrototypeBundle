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
      servicename: app.config.nazwa_controllera23
  Grid builder config:
      phrase: *
      servicename: prototype.gridbuilder
```
In *Services* section, you can configure names of services which will be used to evoke a specific action mapped onto the route matching the specified url. You can also verify a phrase built from *route* and *entity* tags, which are matched and used to find the best suited service.
