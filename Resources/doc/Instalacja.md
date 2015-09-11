# PrototypeBundle opis instalacji

>by Lukasz Sobieraj <lukasz.sobieraj@tmsolution.pl>

---

### Proces instalacji

Poniżej opisano kolejne kroki, w celu właściwej instalacji narzędzi do budowania aplikacji.

1.Utworzyć nowy projekt Symfony.

2.Do projektu należy podłączyć PrototypeBundle:
```
#composer.json
"require": {
        "tmsolution/prototype-bundle": "dev-master"
           }
```
oraz dołączyć PhantomBundle –  sklonuj z TMSolution GitHub – do src swojego projektu.

**Jeśli nie masz dostępu do konta TMSolution sforkuj projekt na swoje konto github**

3.Dodaj następujące wpisy:
```
#AppKernel.php
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

4.Stwórz schemat bazy danych za pomocą komendy:
```
doctrine:schema:create
```

5.Wygeneruj plik classmapper za pomocą komendy:
```
classmapper:generate:friendlynames
```
jako parametry należy podać nazwę bundle'a lub bundle'i, w celu wygenerowania przyjaznych nazw encji, np. PhantomBundle

6.Doodaj następujące wpisy:
 do bloku imports:
```
#config.yml
    { resource: classmapper.yml }
```
do bloku twig:
```
#config.yml
    globals:
        classmapperservice: "@classmapperservice"
```


#### Budowanie aplikacji####

1.Zainstaluj assety według komendy: 
```
php app/console assets:install
```

2.Wygeneruj configi dla grida następującą komendą:
```
datagrid:generate:grid:config
```
w parametrach podać ścieżkę do encji, np. PhantomBundle/Entity/Product, gdzie Product to nazwa encji

3.W pliku app/config/routing umieść następujący wpis:
```
fos_js_routing:
    resource: "@FOSJsRoutingBundle/Resources/config/routing/routing.xml" 
```
oraz
```
core_prototype:
    resource: "@CorePrototypeBundle/Resources/config/routing.yml"
    prefix:   /
```

4.Stwórz plik .bowerrc o następującej zawartości (format pliku: bowerrc)
```
{
    "directory": "web/assets/vendor/"
}
```
oraz plik bower.json o następującej zawartości:
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

Oba pliki umieść w głównym katalogu projektu.

5.Wpisz komendę  w konsoli:
```
bower install
```
 Więcej o instalacji bower dla symfony w linku: http://symfony.com/doc/current/cookbook/frontend/bower.html

W  pasku adresu należy wpisać:
```
http://localhost/testowyProjekt/web/app_dev.php/panel/product/list
```
panel/product - gdzie product to nazwa encji

