<?php

namespace Drupal\eca_misc\Plugin\ECA\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * Plugin implementation of the ECA Events for routing.
 *
 * @EcaEvent(
 *   id = "routing",
 *   deriver = "Drupal\eca_misc\Plugin\ECA\Event\RoutingEventDeriver"
 * )
 */
class RoutingEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'alter' => [
        'label' => 'Alter route',
        'event_name' => RoutingEvents::ALTER,
        'event_class' => RouteBuildEvent::class,
      ],
      'dynamic' => [
        'label' => 'Allow new routes',
        'event_name' => RoutingEvents::DYNAMIC,
        'event_class' => RouteBuildEvent::class,
      ],
      'finished' => [
        'label' => 'Route building finished',
        'event_name' => RoutingEvents::FINISHED,
        'event_class' => Event::class,
      ],
    ];
  }

}
