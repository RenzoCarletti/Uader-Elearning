<?php

namespace Drupal\inscripcion_automatica\EventSubscriber;

  use Drupal\state_machine\Event\WorkflowTransitionEvent;
  use Drupal\Core\Language\LanguageManagerInterface;
  use Drupal\Core\StringTranslation\StringTranslationTrait;
  use Symfony\Component\EventDispatcher\EventSubscriberInterface;
  use Drupal\Core\Controller\ControllerBase;
  use Drupal\moodle_rest\Services\RestFunctions;
  use Drupal\Core\Mail\MailManagerInterface;
  use Drupal\node\Entity\Node;
  
  /**
   * Éste modulo se va a encargar de inscribir a una persona a el moodle vinculado
   * Como?
   * Cuando el usuario encargado de completar un pedido confirma el pago "Fulfillment accepted"
   * va a activar éste evento, que pide los datos del carro de compras, tanto como el usuario que lo solicitó
   * con las funciones heredadas de moodle vinculamos y asignamos, abajo se aclara que sector hace que
   */
  class ActionInscripcion implements EventSubscriberInterface {
    
    use StringTranslationTrait;
    /**
     * The language manager.
     *
     * @var \Drupal\Core\Language\LanguageManagerInterface
     */
    protected $languageManager;

    /**
     * The mail manager.
     *
     * @var \Drupal\Core\Mail\MailManagerInterface
     */
    protected $mailManager;
    
    /**
     * Constructs a new OrderFulfillmentSubscriber object.
     *
     * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
     *   The language manager.
     * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
     *   The mail manager.
     */
    public function __construct(
      LanguageManagerInterface $language_manager,
      MailManagerInterface $mail_manager
    ) {
      $this->languageManager = $language_manager;
      $this->mailManager = $mail_manager;
    }
    /**
     * {@inheritdoc}
     */
    
    public static function getSubscribedEvents() {
      $events = [
        'commerce_order.fulfill.pre_transition' => ['eventoInscripcion', -100],
        'commerce_order.completed.pre_transition' => ['cambioestado', -99],
     ];
      return $events;
    }
    
    /**
     * Envia un correo
     *
     * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
     *   The transition event.
     */
     
    public function eventoInscripcion(WorkflowTransitionEvent $event) {
      $database=\Drupal::database();
      
      $order = $event->getEntity();
      
      /*
     * Seteo los datos de la orden
     */
     $customer = $order->getCustomer();
    
     /*
     * Se setean los datos del usuario en moodle, para despues con el foreach ir asignando en cada curso
     * un rol de estudiante.
     */
      $userconf = [];
      $variacion3=$order->bundle();
      foreach ($order->getItems() as $order_item) {
       $product_variation = $order_item->getPurchasedEntity();
       $variationfield = $product_variation->get('product_id')->getValue();

       //$node= Node::load($variationfield[0]["target_id"]);
       //$curso=$node->get('curso')->getValue();

       $query = $database->select('commerce_product__field_curso','m')
       ->fields('m',['field_curso_target_id'])
       ->condition('m.entity_id',$variationfield[0]["target_id"],'=');
       $result = $query->execute()->fetchAll();
        if(empty($result)){
          continue;
          //error por si el result tiene producto vacio o curso vacio
        }
       $userconf['enrolments'] = [0=> [
         'roleid' => 5,
         'userid' => intval($customer->moodle_user_id->getString()),
         'courseid' => intval($result[0]->field_curso_target_id),
      ]];
      // Si descomentamos lo de abajo con el debugger deberiamos poder ver los datos
      // de la página, para verificar que el rest funciona correcto y podemos
      // utilizar las funciones importadas
      // $variable1=$rest->requestFunction('core_webservice_get_site_info');
      $this->getRestClient()->requestFunction('enrol_manual_enrol_users',$userconf);
     }

    }

    public function getRestClient() {
      if (empty($this->rest)) {
        $this->rest = \Drupal::service('moodle_rest.rest_ws');
      }    
      return $this->rest;
    }
  }
  