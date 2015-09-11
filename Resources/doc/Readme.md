# PrototypeBundle installation description

>by Lukasz Sobieraj <lukasz.sobieraj@tmsolution.pl>

---

### Installation

To install the application tools follow the steps:

Create the new symfony project. 
Add PrototypeBundle using composer.json, add the line below
```
"require":  {
        "tmsolution/prototype-bundle": "dev-master"
            }
```
and then clone the PhantomBundle if you have access to the TMSolution account, otherwise fork the bundle on your own github account.

In the AppKernel.php file add below lines:
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

In the next step create a database scheama is needed. Follow the command below:
```
doctrine:schema:create
```

Generate a classmapper file with command:
``
classmapper:generate:friendlynames
```
Paramater is the name of bundle or bundle's.

 
jako parametry należy podać nazwę bundle'a lub bundle'i, w celu wygenerowania przyjaznych nazw encji, np. PhantomBundle
Do pliku app/config/config.yml dodać następujący wpis do bloku imports:
    { resource: classmapper.yml }
6. Do pliku config.yml w bloku twig dodać następujący wpis:
twig:
    debug:            "%kernel.debug%"
    strict_variables: "%kernel.debug%"
    globals:
        classmapperservice: "@classmapperservice"


Budowanie aplikacji.

1. Zainstalować assety według komendy: php app/console assets:install

2. Kolejno należy wygenerować configi dla grida następującą komendą:
datagrid:generate:grid:config
w parametrach podać ścieżkę do encji, np. PhantomBundle/Entity/Product, gdzie Product to nazwa encji

3. W folderze app/config/routing umioeścić następujący wpis:
fos_js_routing:
    resource: "@FOSJsRoutingBundle/Resources/config/routing/routing.xml" 
oraz
core_prototype:
    resource: "@CorePrototypeBundle/Resources/config/routing.yml"
    prefix:   /

4.  Następnie należy stworzyć plik .bowerrc o następującej zawartości (format pliku: bowerrc)
{
    "directory": "web/assets/vendor/"
}
oraz plik bower.json o następującej zawartości:
{
  "name": "app-name",
  "version": "0.0.1",
  "dependencies": {
  "bootstrap": "master",
  "blockUI": "master"
      },
  "private": true
}

Oba pliki umieścić w głównym katalogu projektu.
5. Następnie należy wpisać komendę bower install w konsoli. Więcej o instalacji bower dla symfony w linku: http://symfony.com/doc/current/cookbook/frontend/bower.html

W  pasku adresu można podgladac efekt powstania formularza na podstawie stworzonej encji:
http://localhost/testowyProjekt/web/app_dev.php/panel/product/list
panel/product - gdzie product to nazwa 

