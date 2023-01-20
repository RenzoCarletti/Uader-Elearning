<?php

namespace Drupal\eca_misc\Plugin\ECA\Event;

use Drupal\block_content\BlockContentEvents;
use Drupal\block_content\Event\BlockContentGetDependencyEvent;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Drupal\layout_builder\Event\PrepareLayoutEvent;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\locale\LocaleEvent;
use Drupal\locale\LocaleEvents;

/**
 * Plugin implementation of the ECA Events for Drupal core.
 *
 * @EcaEvent(
 *   id = "drupal",
 *   deriver = "Drupal\eca_misc\Plugin\ECA\Event\DrupalCoreEventDeriver"
 * )
 */
class DrupalCoreEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [];
    if (class_exists(BlockContentEvents::class)) {
      $actions['block_content_get_dependency'] = [
        'label' => 'Block content get dependency',
        'event_name' => BlockContentEvents::BLOCK_CONTENT_GET_DEPENDENCY,
        'event_class' => BlockContentGetDependencyEvent::class,
      ];
    }
    if (class_exists(FileUploadSanitizeNameEvent::class)) {
      $actions['file_upload_sanitize_name_event'] = [
        'label' => 'Sanitize file name',
        'event_name' => FileUploadSanitizeNameEvent::class,
        'event_class' => FileUploadSanitizeNameEvent::class,
      ];
    }
    if (class_exists(RenderEvents::class)) {
      $actions['select_page_display_variant'] = [
        'label' => 'Select page display mode',
        'event_name' => RenderEvents::SELECT_PAGE_DISPLAY_VARIANT,
        'event_class' => PageDisplayVariantSelectionEvent::class,
      ];
    }
    if (class_exists(ResourceTypeBuildEvents::class)) {
      $actions['build'] = [
        'label' => 'Build resource type',
        'event_name' => ResourceTypeBuildEvents::BUILD,
        'event_class' => ResourceTypeBuildEvent::class,
      ];
    }
    if (class_exists(LayoutBuilderEvents::class)) {
      $actions['prepare_layout'] = [
        'label' => 'Prepare layout builder element',
        'event_name' => LayoutBuilderEvents::PREPARE_LAYOUT,
        'event_class' => PrepareLayoutEvent::class,
      ];
      $actions['section_component_build_render_array'] = [
        'label' => 'Build render array',
        'event_name' => LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY,
        'event_class' => SectionComponentBuildRenderArrayEvent::class,
      ];
    }
    if (class_exists(LocaleEvents::class)) {
      $actions['save_translation'] = [
        'label' => 'Save translated string',
        'event_name' => LocaleEvents::SAVE_TRANSLATION,
        'event_class' => LocaleEvent::class,
      ];
    }
    return $actions;
  }

}
