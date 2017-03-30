<?php
use EventEspresso\WaitList\WaitList;
use EventEspresso\WaitList\WaitListCheckoutMonitor;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListCheckoutMonitorTest
 * Unit tests for the WaitListCheckoutMonitor class
 * PLZ NOTE: not yet able to test methods that utilize EE_Checkout
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListCheckoutMonitorTest extends EE_UnitTestCase
{

    /**
     * @var WaitListCheckoutMonitor $wait_list_checkout_monitor
    */
    protected $wait_list_checkout_monitor;



    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        $this->wait_list_checkout_monitor = new WaitListCheckoutMonitor();
    }



    /**
     * @group WaitListCheckoutMonitor
     * @throws EE_Error
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testAllowRegPayment()
    {
        $registration = EE_Registration::new_instance(
          array(
              'EVT_ID'          => 1,
              'TKT_ID'          => 2,
              'TXN_ID'          => 3,
              'STS_ID'          => EEM_Registration::status_id_pending_payment,
              'REG_count'       => 1,
              'REG_group_size'  => 1,
              'REG_final_price' => 10.00,
          )
        );
        $registration->save();
        //first confirm that this guy can't pay
        $this->assertFalse($this->wait_list_checkout_monitor->allowRegPayment(false, $registration));
        // now add meta data to indicate that this guy was on the waitlist
        $registration->add_extra_meta(
            WaitList::REG_SIGNED_UP_META_KEY,
            current_time('mysql', true),
            true
        );
        // then try again
        $this->assertTrue($this->wait_list_checkout_monitor->allowRegPayment(false, $registration));
    }

}
// End of file WaitListCheckoutMonitorTest.php
// Location: /tests/testcases/WaitListCheckoutMonitorTest.php