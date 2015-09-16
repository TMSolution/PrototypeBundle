<?php

namespace CCO\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CCOUserBundle extends Bundle
{
    
    public function getParent() {
        return "FOSUserBundle";
    }
    
    
    
}
