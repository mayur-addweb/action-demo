<?php

namespace Drupal\vscpa_sso\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserPasswordForm as UserPasswordFormBase;

/**
 * Form controller for the user password forms.
 *
 * @internal
 */
class UserPasswordForm extends UserPasswordFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    if ($user->isAuthenticated()) {
      $url = Url::fromRoute('am_net_user_profile.website_account', ['user' => $user->id()])->toString();
      $message = t("You have tried to use a one-time login link that is no longer valid (or you are already authenticated). If you are authenticated and need to reset your password please use the <a href='@url'>Website Account page</a> to change your password.", ['@url' => $url]);
      \Drupal::messenger()->addWarning($message);
      // Redirect to the Home page.
      return $this->redirect('<front>');
    }
    else {
      return parent::buildForm($form, $form_state);
    }

  }

}
