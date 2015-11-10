
# PrototypeBundle installation description

>by Damian Piela <damian.piela@tmsolution.pl>


---


__Scrutinizer CI__ [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/TMSolution/PrototypeBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/TMSolution/PrototypeBundle/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/TMSolution/PrototypeBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/TMSolution/PrototypeBundle/build-status/master)

### Installation

To install the bundle, add: 

```
//composer require

"tmsolution/prototype-bundle": "1.1.*"
```

to your project's `composer.json` file. Later, enable your bundle in the app's kernel:

```
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Core\PrototypeBundle\CorePrototypeBundle()
    );
}
```



