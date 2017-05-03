<?php

namespace EventEspresso\WaitList\domain\services\forms;

use DomainException;
use EE_Error;
use EE_Event;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Registry;
use EE_Wait_Lists;
use EED_Wait_Lists;
use EEH_HTML;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;
use EventEspresso\WaitList\domain\Constants;
use InvalidArgumentException;
use LogicException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class EventEditorWaitListMetaBoxFormHandler
 * an admin form for controlling an event's wait list settings.
 * appears in an event editor sidebar metabox
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class EventEditorWaitListMetaBoxFormHandler extends FormHandler
{


    /**
     * @var EE_Event $event
     */
    protected $event;



    /**
     * Form constructor.
     *
     * @param EE_Event    $event
     * @param EE_Registry $registry
     * @throws DomainException
     * @throws InvalidDataTypeException
     * @throws InvalidArgumentException
     */
    public function __construct(EE_Event $event, EE_Registry $registry)
    {
        $this->event = $event;
        parent::__construct(
            esc_html__('Event Wait List Settings', 'event_espresso'),
            esc_html__('Event Wait List Settings', 'event_espresso'),
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
     * @throws EE_Error
     * @throws LogicException
     */
    public function generate()
    {
        $this->setForm(
            new EventEditorWaitListMetaBoxForm($this->event)
        );
        return $this->form();
    }



    /**
     * takes the generated form and displays it along with ony other non-form HTML that may be required
     * returns a string of HTML that can be directly echoed in a template
     *
     * @return string
     * @throws LogicException
     * @throws EE_Error
     */
    public function display()
    {
        // inject some additional subsections with HTML that's for display only
        $this->form(true)->add_subsections(
            array(
                'view_wait_list_link' => new EE_Form_Section_HTML(
                    EEH_HTML::br() . $this->waitListRegCountDisplay() . EEH_HTML::br(2)
                ),
            ),
            'wait_list_spaces'
        );
        return parent::display();
    }



    /**
     * returns HTML for displaying Wait List Reg Count
     * that links to the reg admin list table filtered for that reg status and event
     *
     * @return string
     * @throws EE_Error
     */
    public function waitListRegCountDisplay()
    {
        $html = EEH_HTML::span(
            '',
            '',
            'dashicons dashicons-groups ee-icon-color-ee-purple ee-icon-size-20'
        );
        $html .= ' ' . EED_Wait_Lists::wait_list_registrations_list_table_link($this->event);
        $html .= ' : ' . EED_Wait_Lists::waitListRegCount($this->event);
        return $html;
    }



    /**
     * handles processing the form submission
     * returns true or false depending on whether the form was processed successfully or not
     *
     * @param array $form_data
     * @return bool
     * @throws LogicException
     * @throws InvalidFormSubmissionException
     * @throws EE_Error
     */
    public function process($form_data = array())
    {
        // process form
        $valid_data = (array)parent::process($form_data);
        if (empty($valid_data)) {
            return false;
        }
        $wait_list_spaces = absint($valid_data['wait_list_spaces']);
        $this->event->update_extra_meta(Constants::SPACES_META_KEY, $wait_list_spaces);
        $this->event->update_extra_meta(
            Constants::AUTO_PROMOTE_META_KEY,
            filter_var(
                $valid_data['auto_promote_registrants'],
                FILTER_VALIDATE_BOOLEAN
            )
        );
        $manual_control_spaces = absint($valid_data['manual_control_spaces']);
        // manual control spaces can't be more than the total number of spaces in the wait list
        $manual_control_spaces = $wait_list_spaces > 0
            ? min($wait_list_spaces, $manual_control_spaces)
            : $manual_control_spaces;
        $this->event->update_extra_meta(Constants::MANUAL_CONTROL_SPACES_META_KEY, $manual_control_spaces);
        $this->event->update_extra_meta(
            Constants::REG_COUNT_META_KEY,
            EE_Wait_Lists::waitListRegCount($this->event)
        );
        // mark event as having a waitlist if number of spaces available is positive
        $this->event->set('EVT_allow_overflow', $wait_list_spaces > 0);
        return false;
    }



}
// End of file EventEditorWaitListMetaBoxFormHandler.php
// Location: EventEspresso\Constants/EventEditorWaitListMetaBoxFormHandler.php