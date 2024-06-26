<?php

namespace EasyApiBundle\Services\MediaUploader;

use EasyApiBundle\Entity\MediaUploader\AbstractMedia;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class MediaUploaderDirectoryNamer implements DirectoryNamerInterface
{
    /**
     * DirectoryNamer constructor.
     *
     * @param ServiceLocator $serviceLocator
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(protected readonly ServiceLocator $serviceLocator, protected readonly ParameterBagInterface $parameterBag)
    {
    }

    /**
     * Returns the name of a directory where files will be uploaded.
     * @param AbstractMedia $object
     * @param PropertyMapping $mapping
     * @return string
     */
    public function directoryName($object, PropertyMapping $mapping): string
    {
         if ($directoryNamer = $object->getDirectoryNamer()) {
            return $this->serviceLocator->get($directoryNamer)->directoryName($object, $mapping);
         }

        if ($directoryName = $object->getDirectoryName()) {
            $pathParameter = $this->parameterBag->get("media_uploader_directories_{$directoryName}");
            $this->evalPath($pathParameter, $object);
        }

        return $this->evalPath($object->getDirectoryValue(), $object);
    }

    /**
     * @param string|null $path
     * @param AbstractMedia $media
     * @return string
     */
    private function evalPath(?string $path, AbstractMedia $media): string
    {
        return null != $path ? str_replace(['%container_id%', '%object_id%'], [$media->getContainerEntity()->getUuid(), $media->getUuid()], $path): '';
    }
}
