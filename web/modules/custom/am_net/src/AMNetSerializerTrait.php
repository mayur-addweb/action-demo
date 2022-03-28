<?php

namespace Drupal\am_net;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * AM.net entities serializer Helper trait implementation.
 */
trait AMNetSerializerTrait {

  /**
   * The Serializer Handler.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|null
   */
  protected $serializer = NULL;

  /**
   * De-serializes data into the given type.
   *
   * @param mixed $data
   *   The mixed serialized data.
   * @param string $type
   *   The concrete class object name.
   * @param string $format
   *   The format used for De-serializes data
   *   into the given type.
   *
   * @return object
   *   The de-serialized object instance.
   */
  public function deserialize($data, $type, $format = 'json') {
    $entity = $this->getSerializer()->deserialize($data, $type, $format);
    return $entity;
  }

  /**
   * Serializes data in the appropriate format.
   *
   * @param mixed $object
   *   The concrete object instance.
   * @param string $format
   *   The format used for serializes data
   *   into the given type.
   *
   * @return string
   *   The serialized object in the given format.
   */
  public function serialize($object, $format = 'json') {
    $data = $this->getSerializer()->serialize($object, $format);
    return $data;
  }

  /**
   * Get Serializer Handler.
   *
   * @return \Symfony\Component\Serializer\SerializerInterface|null
   *   The Serializer instance.
   */
  public function getSerializer() {
    if (is_null($this->serializer)) {
      $encoders = [new JsonEncoder()];
      $normalizers = [
        new AMNetEntityNormalizer(),
        new GetSetMethodNormalizer(),
        new ArrayDenormalizer(),
      ];
      $this->serializer = new Serializer($normalizers, $encoders);
    }
    return $this->serializer;
  }

}
