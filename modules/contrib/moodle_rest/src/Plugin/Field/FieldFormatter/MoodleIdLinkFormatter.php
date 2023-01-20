<?php

namespace Drupal\moodle_rest\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Moodle ID' formatter.
 *
 * @FieldFormatter(
 *   id = "moodle_id_link",
 *   label = @Translation("Moodle link"),
 *   field_types = {
 *     "moodle_id"
 *   }
 * )
 */
class MoodleIdLinkFormatter extends FormatterBase {

  /**
   * The Moodle REST settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $moodleSettings;

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setMoodleSettings($container->get('config.factory')->get('moodle_rest.settings'));
    return $plugin;
  }

  /**
   * Set Moodle settings.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   The Moodle REST settings.
   */
  public function setMoodleSettings(ImmutableConfig $settings) {
    $this->moodleSettings = $settings;
  }

  /**
   * Get Moodle settings.
   */
  public function getMoodleSettings() {
    if (is_null($this->moodleSettings)) {
      $this->moodleSettings = \Drupal::config('moodle_rest.settings');
    }

    return $this->moodleSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['link_text'] = 'On Moodle';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to use for link'),
      '#default_value' => $this->getSetting('link_text'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getFieldSettings();
    $url_base = $this->getMoodleSettings()->get('url');

    foreach ($items as $delta => $item) {
      switch ($settings['type']) {
        case 'course':
          $elements[$delta] = [
            '#type' => 'link',
            '#title' => $this->getSetting('link_text'),
            '#url' => Url::fromUri($url_base . '/course/view.php?id=' . (int) $item->value),
          ];
          break;

        case 'user':
          $elements[$delta] = [
            '#type' => 'link',
            '#title' => $this->getSetting('link_text'),
            '#url' => Url::fromUri($url_base . '/user/view.php?id=' . (int) $item->value),
          ];
          break;

        default:
          $output = (int) $item->value;
          $elements[$delta] = ['#markup' => $output];
      }
    }

    return $elements;
  }

}
