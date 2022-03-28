<?php

namespace Drupal\vscpa_commerce\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\vscpa_commerce\Entity\EventSessionInterface;

/**
 * Class EventSessionController.
 *
 *  Returns responses for Event session routes.
 */
class EventSessionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Event session  revision.
   *
   * @param int $event_session_revision
   *   The Event session  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($event_session_revision) {
    $event_session = $this->entityManager()->getStorage('event_session')->loadRevision($event_session_revision);
    $view_builder = $this->entityManager()->getViewBuilder('event_session');

    return $view_builder->view($event_session);
  }

  /**
   * Page title callback for a Event session  revision.
   *
   * @param int $event_session_revision
   *   The Event session  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($event_session_revision) {
    $event_session = $this->entityManager()->getStorage('event_session')->loadRevision($event_session_revision);
    return $this->t('Revision of %title from %date', ['%title' => $event_session->label(), '%date' => format_date($event_session->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Event session .
   *
   * @param \Drupal\vscpa_commerce\Entity\EventSessionInterface $event_session
   *   A Event session  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(EventSessionInterface $event_session) {
    $account = $this->currentUser();
    $langcode = $event_session->language()->getId();
    $langname = $event_session->language()->getName();
    $languages = $event_session->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $event_session_storage = $this->entityManager()->getStorage('event_session');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $event_session->label()]) : $this->t('Revisions for %title', ['%title' => $event_session->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all event session revisions") || $account->hasPermission('administer event session entities')));
    $delete_permission = (($account->hasPermission("delete all event session revisions") || $account->hasPermission('administer event session entities')));

    $rows = [];

    $vids = $event_session_storage->revisionIds($event_session);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\vscpa_commerce\EventSessionInterface $revision */
      $revision = $event_session_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $event_session->getRevisionId()) {
          $link = $this->l($date, new Url('entity.event_session.revision', ['event_session' => $event_session->id(), 'event_session_revision' => $vid]));
        }
        else {
          $link = $event_session->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.event_session.translation_revert', [
                'event_session' => $event_session->id(),
                'event_session_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.event_session.revision_revert', [
                'event_session' => $event_session->id(),
                'event_session_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.event_session.revision_delete', ['event_session' => $event_session->id(), 'event_session_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['event_session_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
