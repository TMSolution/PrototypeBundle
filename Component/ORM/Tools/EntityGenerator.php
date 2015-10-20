<?php

namespace Core\PrototypeBundle\Component\ORM\Tools;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\EntityGenerator as BaseEntityGenerator;

class EntityGenerator extends BaseEntityGenerator
{

    /**
     * @var string
     */
    protected static $toStringMethodTemplate = '/**
 * __toString method
 *
 * return string
 */
public function __toString()
{
<spaces><body>
}
';

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityBody(ClassMetadataInfo $metadata)
    {
        $fieldMappingProperties = $this->generateEntityFieldMappingProperties($metadata);
        $associationMappingProperties = $this->generateEntityAssociationMappingProperties($metadata);
        $stubMethods = $this->generateEntityStubMethods ? $this->generateEntityStubMethods($metadata) : null;
        $lifecycleCallbackMethods = $this->generateEntityLifecycleCallbackMethods($metadata);

        $code = array();

        if ($fieldMappingProperties) {
            $code[] = $fieldMappingProperties;
        }

        if ($associationMappingProperties) {
            $code[] = $associationMappingProperties;
        }

        $code[] = $this->generateEntityConstructor($metadata);

        if ($stubMethods) {
            $code[] = $stubMethods;
        }

        if ($lifecycleCallbackMethods) {
            $code[] = $lifecycleCallbackMethods;
        }

        $code[] = $this->generateEntityToString($metadata);

        return implode("\n", $code);
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateEntityToString(ClassMetadataInfo $metadata)
    {
        if ($this->hasMethod('__toString', $metadata)) {
            return '';
        }

        if (array_key_exists('name', $metadata->fieldMappings)) {
            $method = "return (string)\$this->getName();";
        } else {
            reset($metadata->fieldMappings);
            $firstKey = key($metadata->fieldMappings);
            $method = "return (string)\$this->get" . ucfirst($firstKey) . "();";
        }

        return $this->prefixCodeWithSpaces(str_replace("<body>", $method, self::$toStringMethodTemplate));
    }

}
