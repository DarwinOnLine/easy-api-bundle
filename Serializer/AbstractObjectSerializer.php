<?php

namespace EasyApiBundle\Serializer;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class AbstractObjectSerializer implements NormalizerInterface, ServiceSubscriberInterface
{
    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    /**
     * @return NormalizerInterface
     */
    public function getObjectNormalizer(): NormalizerInterface
    {
        return $this->container->get(ObjectNormalizer::class);
    }

    /**
     * @param mixed $object
     * @param string|null $format
     * @param array $context
     * @return array|\ArrayObject|bool|float|int|string|null
     * @throws ExceptionInterface
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->getObjectNormalizer()->normalize($object, null, $context);
    }

    abstract public function supportsNormalization($data, $format = null);

    /**
     * @return array|string[]
     */
    public static function getSubscribedServices(): array
    {
        return [ObjectNormalizer::class];
    }
}
