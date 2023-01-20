<?php

namespace Drupal\moodle_rest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class MoodleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'moodle_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'moodle_rest.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Status message is in the form so it's just displayed and calculated for
    // the form as is, rather than setting on submission too. Cheating with the
    // CSS classes probably could be done better.
    $url = $this->config('moodle_rest.settings')->get('url');
    $token = $this->config('moodle_rest.settings')->get('wstoken');
    if (!empty($url) && !empty($token)) {
      $server = \Drupal::service('moodle_rest.rest_functions');
      $release = $server->getSiteInfoRelease();
      if ($release == 'error') {
        $form['status'] = [
          '#markup' => '<div class="messages messages--error">' .
           $this->t('Failed to connect with Moodle Server') .
           '</div>'
        ];
      }
      elseif ($release == 'unknown') {
        $form['status'] = [
          '#markup' => '<div class="messages messages--warning">' .
          $this->t('Server contactable but access to Moodle site information denied. This is normal if your user does not have access to the <pre>core_webservice_get_site_info</pre> function.') .
          '</div>'
        ];
      }
      else {
        $form['status'] = [
          '#markup'=> '<div class="messages messages--status">' .
          $this->t("Server: %sitename<br/>\nVersion: %release<br/>\nUser: %username<br/>\nFunctions: %functions", [
            '%sitename' => $server->getSiteInfoSitename(),
            '%release' => $release,
            '%username' => $server->getSiteInfoUsername(),
            '%functions' => implode(', ', array_column($server->getSiteInfoFunctions(), 'name')),
          ]) .
          '</div>'
        ];
      } 
    }

    $form['moodle'] = [
      '#title' => 'Moodle settings',
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['moodle']['url'] = [
      '#type' => 'textfield',
      '#title' => 'Moodle Url',
      '#default_value' => $url,
      '#description' => $this->t('Moodle Url'),
    ];

    $form['moodle']['wstoken'] = [
      '#type' => 'textfield',
      '#title' => 'Moodle Token',
      '#default_value' => $token, 
      '#description' => $this->t('Moodle Token'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('moodle_rest.settings')->set('url', $form_state->getValue('url'))->set('wstoken', $form_state->getValue('wstoken'))->save();
    parent::submitForm($form, $form_state);
  }

}
