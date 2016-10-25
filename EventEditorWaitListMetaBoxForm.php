<?php
namespace EventEspresso\WaitList;

use EE_Admin_Two_Column_Layout;
use EE_Form_Section_Proper;
use EE_Text_Input;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;

defined( 'ABSPATH' ) || exit;



/**
 * Class EventEditorWaitListMetaBoxForm
 * Description
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class EventEditorWaitListMetaBoxForm extends FormHandler  {


	/**
	 * @var \EE_Event $event
	 */
	protected $event;



	/**
	 * Form constructor.
	 *
	 * @param \EE_Event    $event
	 * @param \EE_Registry $registry
	 */
	public function __construct( \EE_Event $event, \EE_Registry $registry ) {
		$this->event = $event;
		parent::__construct(
			esc_html__( 'Event Wait List Settings', 'event_espresso' ),
			esc_html__( 'Event Wait List Settings', 'event_espresso' ),
			'event_wait_list_settings',
			'',
			FormHandler::DO_NOT_SETUP_FORM,
			$registry
		);
	}



	/**
	 * creates and returns the actual form
	 *
	 * @return EE_Form_Section_Proper
	 * @throws \EE_Error
	 */
	public function generate() {
		$this->setForm(
			new EE_Form_Section_Proper(
				array(
					'name'            => 'event_wait_list_settings',
					'html_id'         => 'event_wait_list_settings',
					'layout_strategy' => new \EE_Div_Per_Section_Layout(),
					'subsections'     => array(
						'wait_list_spaces' => new EE_Text_Input(
							array(
								'html_label_text' => esc_html__( 'Wait List Spaces', 'event_espresso' ),
								'html_help_text'  => esc_html__(
									'number of registrants the wait list accepts before the event is completely sold out.',
									'event_espresso'
								),
								'other_html_attributes' => ' size="4"',
								'html_class'      => 'ee-numeric',
								'default'         => absint( $this->event->get_extra_meta( 'ee_wait_list_spaces', true ) ),
								'required'        => false
							)
						),
					)
				)
			)
		);
	}



	/**
	 * handles processing the form submission
	 * returns true or false depending on whether the form was processed successfully or not
	 *
	 * @param array $form_data
	 * @return bool
	 * @throws \LogicException
	 * @throws \EventEspresso\core\exceptions\InvalidFormSubmissionException
	 * @throws \EE_Error
	 */
	public function process($form_data = array() ) {
		// process form
		$valid_data = (array) parent::process( $form_data );
		if ( empty( $valid_data ) ) {
			return false;
		}
		// \EEH_Debug_Tools::printr( $valid_data, '$valid_data', __FILE__, __LINE__ );
		$this->event->update_extra_meta( 'ee_wait_list_spaces', absint( (int) $valid_data['wait_list_spaces'] ) );
		// $meta = $this->event->get_extra_meta('ee_wait_list_spaces', true );
		// \EEH_Debug_Tools::printr( $meta, '$meta', __FILE__, __LINE__ );
		// die();
		return false;
	}



}
// End of file EventEditorWaitListMetaBoxForm.php
// Location: EventEspresso\WaitList/EventEditorWaitListMetaBoxForm.php