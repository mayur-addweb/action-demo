<?php

namespace Drupal\am_net;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * AM.net entities serializer Helper trait implementation.
 */
class AMNetEntityNormalizer extends ObjectNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $data = parent::normalize($object, $format, $context);
    return array_filter($data, function ($value) {
      return (NULL !== $value);
    });
  }

}
