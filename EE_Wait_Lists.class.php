<?php
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\loaders\Loader;
use EventEspresso\core\services\loaders\LoaderInterface;

if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit();
}
// define the plugin directory path and URL
define('EE_WAIT_LISTS_BASENAME', plugin_basename(EE_WAIT_LISTS_PLUGIN_FILE));
define('EE_WAIT_LISTS_PATH', plugin_dir_path(__FILE__));
define('EE_WAIT_LISTS_URL', plugin_dir_url(__FILE__));
define('EE_WAIT_LISTS_ADMIN', EE_WAIT_LISTS_PATH . 'admin' . DS . 'wait_lists' . DS);



/**
 * Class  EE_Wait_Lists
 *
 * @package               Event Espresso
 * @subpackage            eea-wait-lists
 * @author                Brent Christensen
 */
Class  EE_Wait_Lists extends EE_Addon
{


    /**
     * @var LoaderInterface $loader
     */
    private static $loader;



    /**
     * EE_Wait_Lists constructor.
     *
     * @param LoaderInterface $loader
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public function __construct(LoaderInterface $loader = null)
    {
        EE_Wait_Lists::$loader = $loader;
        parent::__construct();
    }



    /**
     * @return LoaderInterface
     */
    public static function loader()
    {
        if (! EE_Wait_Lists::$loader instanceof LoaderInterface) {
            EE_Wait_Lists::$loader = EE_Registry::instance()->create('EventEspresso\core\services\loaders\Loader');
        }
        return EE_Wait_Lists::$loader;
    }



    /**
     * this is not the place to perform any logic or add any other filter or action callbacks
     * this is just to bootstrap your addon; and keep in mind the addon might be DE-registered
     * in which case your callbacks should probably not be executed.
     * EED_Wait_Lists is the place for most filter and action callbacks (relating
     * the the primary business logic of your addon) to be placed
     *
     * @throws \EE_Error
     */
    public static function register_addon()
    {
        // register addon via Plugin API
        EE_Register_Addon::register(
            'Wait_Lists',
            array(
                'version'          => EE_WAIT_LISTS_VERSION,
                'plugin_slug'      => 'eea_wait_lists',
                'min_core_version' => EE_WAIT_LISTS_CORE_VERSION_REQUIRED,
                'main_file_path'   => EE_WAIT_LISTS_PLUGIN_FILE,
                'namespace'        => array(
                    'FQNS' => 'EventEspresso\WaitList',
                    'DIR'  => __DIR__,
                ),
                'module_paths'     => array(
                    EE_WAIT_LISTS_PATH . 'EED_Wait_Lists.module.php',
                    EE_WAIT_LISTS_PATH . 'EED_Wait_Lists_Messages.module.php',
                ),
                'message_types'    => array(
                    'waitlist_can_register' => array(
                        'mtfilename'                                       => 'EE_Waitlist_Can_Register_message_type.class.php',
                        'autoloadpaths'                                    => array(
                            EE_WAIT_LISTS_PATH . 'messages/',
                        ),
                        'messengers_to_activate_with'                      => array('email'),
                        'messengers_to_validate_with'                      => array('email'),
                        'force_activation'                                 => true,
                        'messengers_supporting_default_template_pack_with' => array('email'),
                        'base_path_for_default_templates'                  => EE_WAIT_LISTS_PATH . 'messages/templates/',
                        'base_path_for_default_variation'                  => EE_WAIT_LISTS_PATH . 'messages/templates/variations/',
                        'base_url_for_default_variation'                   => EE_WAIT_LISTS_URL . 'messages/templates/variations/',
                    ),
                ),
                // if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
                'pue_options'      => array(
                    'pue_plugin_slug' => 'eea-wait-lists',
                    'plugin_basename' => EE_WAIT_LISTS_BASENAME,
                    'checkPeriod'     => '24',
                    'use_wp_update'   => false,
                ),
            )
        );
    }



    /**
     * Register things that have to happen early in loading.
     */
    public function after_registration()
    {
        $this->_register_custom_shortcode_library();
        add_action(
            'FHEE__EE_Messages_Base__get_valid_shortcodes',
            array(__CLASS__, 'modify_valid_shortcodes'),
            10,
            2
        );
    }



    /**
     * Takes care of registering the custom shortcode library for this add-on
     */
    protected function _register_custom_shortcode_library()
    {
        //ya intentionally using closures here.  If client code want's this library to not be registered there's
        //facility for deregistering via the provided api.  This forces client code to use that api.
        add_action(
            'EE_Brewing_Regular___messages_caf',
            function () {
                EE_Register_Messages_Shortcode_Library::register(
                    'recipient_waitlist_shortcode_library',
                    array(
                        'name'                    => 'recipient_waitlist',
                        'autoloadpaths'           => EE_WAIT_LISTS_PATH . 'messages/shortcodes/',
                        'msgr_validator_callback' => array(__CLASS__, 'messenger_validator_callback'),
                    )
                );
            },
            20
        );
        //make sure the shortcode library is deregistered if this add-on is deregistered.
        add_action(
            'AHEE__EE_Register_Addon__deregister__after',
            function ($addon_name) {
                if ($addon_name === 'Wait_Lists') {
                    EE_Register_Messages_Shortcode_Library::deregister('recipient_waitlist_shortcode_library');
                }
            }
        );
    }



    /**
     * Callback on `FHEE__EE_Messages_Base__get_valid_shortcodes` that is used to ensure the new shortcode library is
     * registered with the appropriate message type as a valid library.
     * Also using this to remove shortcodes we don't want exposed for the new message type.
     *
     * @param array           $valid_shortcodes Existing array of valid shortcodes
     * @param EE_message_type $message_type
     * @return array
     */
    public static function modify_valid_shortcodes($valid_shortcodes, $message_type)
    {
        if ($message_type instanceof EE_WaitList_Can_Register_message_type) {
            $valid_shortcodes['attendee'][] = 'recipient_waitlist';
            $shortcode_libraries_to_remove = array(
                'primary_registration_details',
                'primary_registration_list',
                'question_list',
                'attendee_list',
                'attendee',
                'event_list',
            );
            array_walk(
                $shortcode_libraries_to_remove,
                function ($shortcode_library_to_remove) use (&$valid_shortcodes) {
                    $key_to_remove = array_search(
                        $shortcode_library_to_remove,
                        $valid_shortcodes['attendee'],
                        true
                    );
                    if ($key_to_remove !== false) {
                        unset($valid_shortcodes['attendee'][$key_to_remove]);
                    }
                }
            );
        }
        return $valid_shortcodes;
    }



    /**
     * Callback set (on registering a shortcode library) that handles the validation of this new library.
     *
     * @param array        $validator_config
     * @param EE_messenger $messenger
     * @return array
     */
    public static function messenger_validator_callback($validator_config, EE_messenger $messenger)
    {
        if ($messenger->name !== 'email') {
            return $validator_config;
        }
        array_push(
            $validator_config['content']['shortcodes'],
            'recipient_waitlist',
            'event',
            'event_meta',
            'ticket_list'
        );
        return $validator_config;
    }

}
// End of file EE_Wait_Lists.class.php
// Location: wp-content/plugins/eea-wait-lists/EE_Wait_Lists.class.php
