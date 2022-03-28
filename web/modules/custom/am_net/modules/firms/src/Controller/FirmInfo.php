<?php

namespace Drupal\am_net_firms\Controller;

use Drupal\user\UserInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Employee Management Tool: Firm Info page controller.
 */
class FirmInfo extends EMTBase {

  /**
   * {@inheritdoc}
   *
   * Builds the term listing as render able array for table.html.twig.
   */
  public function render(UserInterface $user, TermInterface $firm) {
    $build = [
      '#id' => "manage-my-firm-edit-firm-info",
      '#attributes' => ['class' => ['manage-my-firm-edit-firm-info']],
    ];
    // Title.
    $title = $this->t('Edit Information');
    $header_description = $this->t('Modify your information below. Questions? Contact <a href="mailto:vscpa@vscpa.com">vscpa@vscpa.com</a> or call (800) 733-8272.');
    $build['title'] = [
      '#markup' => '<div class="page-header"><h1>' . $title . ' <small class="line-break">' . $header_description . '</small></h1></div>',
    ];
    // Edit form.
    $build['form'] = $this->getEditFirmForm($firm);
    return $build;
  }

}
