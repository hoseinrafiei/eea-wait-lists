<?php

use EventEspresso\WaitList\EventEditorWaitListMetaBoxForm;
use EventEspresso\WaitList\WaitListEventsCollection;
use EventEspresso\WaitList\WaitListMonitor;


defined( 'EVENT_ESPRESSO_VERSION' ) || exit;



/**
 * Class EED_Wait_Lists
 * module class for controlling event registration wait lists
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         1.0.0
 */
class EED_Wait_Lists extends EED_Module {


	/**
	 * @var \Events_Admin_Page $admin_page
	 */
	protected static $admin_page;


	/**
	 * @var WaitListMonitor $wait_list_monitor
	 */
	protected static $wait_list_monitor;


	/**
	 * @var EventEditorWaitListMetaBoxForm $wait_list_settings_form
 */
	protected static $wait_list_settings_form;



	/**
	 * set_hooks - for hooking into EE Core, other modules, etc
	 *
	 * @return void
	 */
	public static function set_hooks() {
        EE_Config::register_route('join', 'EED_Wait_Lists', 'process_wait_list_form_for_event', 'wait_list');
        add_filter(
			'FHEE__EventEspresso_modules_ticket_selector_DisplayTicketSelector__displaySubmitButton__html',
			array( 'EED_Wait_Lists', 'add_wait_list_form_for_event' ),
			10, 2
		);
        add_action('wp_enqueue_scripts', array('EED_Wait_Lists', 'enqueue_styles_and_scripts'));
        \EED_Wait_Lists::set_shared_hooks();
    }



	/**
	 * set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 * @return void
	 */
	public static function set_hooks_admin() {
		add_filter(
			'FHEE__Extend_Events_Admin_Page__page_setup__page_config',
			array( 'EED_Wait_Lists', 'setup_page_config' ),
			1, 2
		);
		add_filter(
			'FHEE__Events_Admin_Page___insert_update_cpt_item__event_update_callbacks',
			array( 'EED_Wait_Lists', 'event_update_callbacks' )
		);
        add_action(
            'AHEE__Events_Admin_Page___generate_publish_box_extra_content__event_editor_overview_add',
            array('EED_Wait_Lists', 'event_editor_overview_add')
        );
        add_action(
            'wp_ajax_process_wait_list_form_for_event',
            array('EED_Wait_Lists', 'process_wait_list_form_for_event')
        );
        add_action(
            'wp_ajax_nopriv_process_wait_list_form_for_event',
            array('EED_Wait_Lists', 'process_wait_list_form_for_event')
        );
        \EED_Wait_Lists::set_shared_hooks();
	}



    /**
     * hooks set by both set_hooks() and set_hooks_admin()
     *
     * @return void
     */
    protected static function set_shared_hooks()
    {
        add_action(
            'AHEE__EE_Registration__set_status__after_update',
            array('EED_Wait_Lists', 'registration_status_update'),
            10, 3
        );
        add_filter(
            'FHEE_EE_Event__perform_sold_out_status_check__spaces_remaining',
            array('EED_Wait_Lists', 'event_spaces_available'),
            10, 2
        );
        add_filter(
            'FHEE_EE_Event__total_available_spaces__spaces_available',
            array('EED_Wait_Lists', 'event_spaces_available'),
            10, 2
        );
    }



	/**
	 *    run - initial module setup
	 *    this method is primarily used for activating resources in the EE_Front_Controller thru the use of filters
	 *
	 * @access    public
	 * @var            WP $WP
	 * @return    void
	 */
	public function run( $WP ) {
		// TODO: Implement run() method.
	}



	/**
	 * @return \EventEspresso\WaitList\WaitListMonitor
	 * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
	 * @throws \EventEspresso\core\exceptions\InvalidEntityException
	 * @throws \EE_Error
	 */
	public static function getWaitListMonitor() {
        // if not already generated, create a wait list monitor object
		if ( ! self::$wait_list_monitor instanceof WaitListMonitor) {
			self::$wait_list_monitor = new WaitListMonitor( new WaitListEventsCollection() );
		}
		return self::$wait_list_monitor;
	}



    /**
     * @return \EventEspresso\WaitList\EventEditorWaitListMetaBoxForm
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \DomainException
     */
	public static function getEventEditorWaitListMetaBoxForm() {
        // if not already generated, create a wait list monitor object
		if ( ! self::$wait_list_settings_form instanceof EventEditorWaitListMetaBoxForm) {
			self::$wait_list_settings_form = new EventEditorWaitListMetaBoxForm(
                EED_Wait_Lists::$admin_page->get_event_object(),
                EE_Registry::instance()
            );
		}
		return self::$wait_list_settings_form;
	}



	/**************************** FRONTEND FUNCTIONALITY ***************************/



    /**
     * enqueue_styles_and_scripts
     *
     * @return void
     */
    public static function enqueue_styles_and_scripts()
    {
        // load css
        wp_register_style(
            'wait_list',
            EE_WAIT_LISTS_URL . 'assets/wait_list.css',
            array(),
            EE_WAIT_LISTS_VERSION
        );
        wp_enqueue_style('wait_list');
        // load JS
        wp_register_script(
            'wait_list',
            EE_WAIT_LISTS_URL . 'assets/wait_list.js',
            array('espresso_core'),
            EE_WAIT_LISTS_VERSION,
            true
        );
        wp_enqueue_script('wait_list');
    }



    /**
     * @param string    $html
     * @param \EE_Event $event
     * @return string
     */
	public static function add_wait_list_form_for_event( $html = '', \EE_Event $event ) {
        try {
            return $html . \EED_Wait_Lists::getWaitListMonitor()->getWaitListFormForEvent($event);
        } catch (Exception $e) {
            EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
        }
        return $html;
	}



	/**
	 * process_wait_list_form_for_event
	 */
	public static function process_wait_list_form_for_event() {
        try {
            $event_id = isset($_REQUEST['event_id']) ? absint($_REQUEST['event_id']) : 0;
            \EED_Wait_Lists::getWaitListMonitor()->processWaitListFormForEvent($event_id);
        } catch (Exception $e) {
            EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
        }
        if (defined('DOING_AJAX') && DOING_AJAX) {
            echo 'AJAX';
            exit();
        }
        \EE_Error::get_notices(false, true);
        wp_safe_redirect(filter_input(INPUT_SERVER, 'HTTP_REFERER'));
        exit();
	}



    /**************************** SPLIT FUNCTIONALITY ***************************/



    /**
     * increment or decrement the wait list reg count for an event when a registration's status changes to or from RWL
     *
     * @param \EE_Registration $registration
     * @param                  $old_STS_ID
     * @param                  $new_STS_ID
     */
    public static function registration_status_update(EE_Registration $registration, $old_STS_ID, $new_STS_ID)
    {
        // \EEH_Debug_Tools::printr(__FUNCTION__, __CLASS__, __FILE__, __LINE__, 2);
        try {
            \EED_Wait_Lists::getWaitListMonitor()->registrationStatusUpdate($registration, $old_STS_ID, $new_STS_ID);
        } catch (Exception $e) {
            EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
        }
        // \EEH_Debug_Tools::printr(__FUNCTION__, 'END ', __FILE__, __LINE__, 2);
    }



    /**
     * @param int            $spaces_available
     * @param \EE_Event      $event
     * @return int
     */
    public static function event_spaces_available($spaces_available, \EE_Event $event)
    {
        // \EEH_Debug_Tools::printr(__FUNCTION__, __CLASS__, __FILE__, __LINE__, 2);
        // if there's nothing left, then there's nothing left
        if ($spaces_available < 1) {
            return $spaces_available;
        }
        try {
            return \EED_Wait_Lists::getWaitListMonitor()->adjustEventSpacesAvailable(
                $spaces_available,
                $event
            );
        } catch (Exception $e) {
            EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
        }
        return $spaces_available;
    }



    /**************************** ADMIN FUNCTIONALITY ****************************/



    /**
	 * callback for FHEE__Extend_Events_Admin_Page__page_setup__page_config
	 *
	 * @param array              $page_config current page config.
	 * @param \Events_Admin_Page $admin_page
	 * @return array
	 * @since  1.0.0
	 */
	public static function setup_page_config( array $page_config, \Events_Admin_Page $admin_page ) {
		EED_Wait_Lists::$admin_page = $admin_page;
		$page_config['edit']['metaboxes'] = array_merge(
			$page_config['edit']['metaboxes'],
			array( array( 'EED_Wait_Lists', 'add_event_wait_list_meta_box' ) )
		);
		return $page_config;
	}



	/**
	 * @return void
	 */
	public static function add_event_wait_list_meta_box() {
		add_meta_box(
			'event-wait-list-mbox',
			esc_html__( 'Event Wait List', 'event_espresso' ),
			array( 'EED_Wait_Lists', 'event_wait_list_meta_box' ),
			EVENTS_PG_SLUG,
			'side',
			'high'
		);
	}



    /**
     * callback that adds a link to the Event Editor Publish metabox
     * to view registrations on the wait list for the event
     */
    public static function event_editor_overview_add()
    {
        try {
            echo \EEH_HTML::div(
                EED_Wait_Lists::getEventEditorWaitListMetaBoxForm()->waitListRegCountDisplay(),
                '', 'misc-pub-section'
            );
        } catch (Exception $e) {
            EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
        }
    }



	/**
	 * callback that adds the main "event_wait_list_meta_box" meta_box
	 * calls non static method below
	 */
	public static function event_wait_list_meta_box() {
		try {
            echo EED_Wait_Lists::getEventEditorWaitListMetaBoxForm()->display();
        } catch ( Exception $e ) {
			EE_Error::add_error( $e->getMessage(), __FILE__, __FUNCTION__, __LINE__ );
		}
	}



	/**
	 * @param array $event_update_callbacks
	 * @return array
	 */
	public static function event_update_callbacks( array $event_update_callbacks) {
		$event_update_callbacks = array_merge(
			$event_update_callbacks,
			array( array( 'EED_Wait_Lists', 'update_event_wait_list_settings' ) )
		);
		return $event_update_callbacks;
	}



	/**
	 * @param \EE_Event $event
	 * @param array     $form_data
	 */
	public static function update_event_wait_list_settings(EE_Event $event, array $form_data) {
		try {
			$wait_list_settings_form = new EventEspresso\WaitList\EventEditorWaitListMetaBoxForm(
				$event,
				EE_Registry::instance()
			);
			$wait_list_settings_form->process($form_data);
		} catch ( Exception $e ) {
			EE_Error::add_error( $e->getMessage(), __FILE__, __FUNCTION__, __LINE__ );
		}
	}



    /**
     * @param \EE_Event $event
     * @return int
     * @throws \EE_Error
     */
    public static function waitListRegCount(\EE_Event $event)
    {
        return absint($event->get_extra_meta('ee_wait_list_registration_count', true));
    }



}
// End of file EED_Wait_Lists.module.php
// Location: wp-content/plugins/eea-wait-lists/EED_Wait_Lists.module.php