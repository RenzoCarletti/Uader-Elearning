<?php

namespace Drupal\alterar_links\Routing;


use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */ 
//    public static function getSubscribedEvents() {
//     $events[RoutingEvents::ALTER] = 'alterRoutes';
//    return $events;
//  }

    protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('moodle_rest_user.course_list')) {
        $route->setPath('/mis-cursos/{user}');
    }
    if ($route = $collection->get('user.edit')) {
        $route->setPath('/editar/{user}');
    }
    if ($route = $collection->get('entity.commerce_order.user_view')) {
        $route->setPath('/mis-pedidos');
    }

 }
}