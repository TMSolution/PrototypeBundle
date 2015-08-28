# Checking grid config

>by Mariusz Piela <mariusz.piela@tmsolution.pl>

---


### Description


### Use

In order to install the bundle, add: 

```
// sample usage
php app/console prototype:show:url:config /panel/category/read/2
```

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
