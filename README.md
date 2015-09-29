
# PrototypeBundle installation description

>by Damian Piela <damian.piela@tmsolution.pl>

---

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


