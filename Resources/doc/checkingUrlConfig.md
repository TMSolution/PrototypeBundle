# Checking url config

>by Mariusz Piela <mariusz.piela@tmsolution.pl>

---


### Description

Command *prototype:show:url:config* was created to verify information about configuration for specified url adress.

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
In *Services* section you can verify names of services witch will be used to doing action mapped by route matching specified url. You can also verify phrase builded from *route* and *entity* pair of tags, witch were matched to find best suited service.
