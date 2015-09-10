# PrototypeBundle opis instalacji

>by Lukasz Sobieraj <lukasz.sobieraj@tmsolution.pl>

---

### Proces instalacji narzędzi do tworzenia aplikacji

Poniżej opisano kolejne kroki, w celu właściwej instalacji narzędzi do budowania aplikacji.
1. Na wstępie należy utworzyć nowy projekt symfony.

2. Do projektu należy podłączyć PrototypeBundle – do pliku composer.json w bloku dodać następujący wpis
"require": {
        "tmsolution/prototype-bundle": "dev-master"
}
oraz dołączyć PhantomBundle – należy sklonować z TMSolution GitHub – do src swojego projektu.
*Jeśli nie masz dostępu do konta TMSolution skopiuj projekt na swój komputer.

3. W pliku AppKernel muszą pojawić się następujące wpisy:
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new APY\DataGridBundle\APYDataGridBundle(),
            new BeSimple\I18nRoutingBundle\BeSimpleI18nRoutingBundle(),
            new TMSolution\DataGridBundle\TMSolutionDataGridBundle(),
            new Core\ModelBundle\CoreModelBundle(),
            new Core\ClassMapperBundle\CoreClassMapperBundle(),
            new Core\PrototypeBundle\CorePrototypeBundle(),
            new PhantomBundle\PhantomBundle(), 

4. Następnie należy przeprowadzić proces tworzenia schematu bazy danych za pomocą komendy:
doctrine:schema:create

5. W kolejnym kroku należy wygenerować plik classmapper za pomocą komendy:
classmapper:generate:friendlynames 
jako parametry należy podać nazwę bundle'a lub bundle'i, w celu wygenerowania przyjaznych nazw encji, np. PhantomBundle
Do pliku app/config/config.yml dodać następujący wpis do bloku imports:
    - { resource: classmapper.yml }
    - 
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

