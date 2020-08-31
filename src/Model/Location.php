<?php


namespace CommonsBooking\Model;

use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Timeframe;

class Location extends CustomPost
{
    /**
     * Returns bookable timeframes for location.
     * 
     * @TODO: should support $args 
     *
     * @return array
     */
    public function getBookableTimeframes()
    {
        return Timeframe::get([$this->ID], [], [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID], NULL, TRUE);
    }

   
    /**
     * getBookableTimeframesByItem
     * 
     * returns bookable timeframes for a given itemID 
     *
     * @param  mixed $itemId
     * @return array 
     */
    public function getBookableTimeframesByItem($itemId)
    {
        return Timeframe::get([$this->ID], [$itemId], [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID]);
    }

    /**
     * formattedAddress
     * 
     * Returns the location address including location name in multiple lanes with <br> line breaks
     * 
     * @TODO: turn this into a user-configurable template. 
     * E.g. a textarea "location format" in the backend that gets run through CB::get():
     * {{location_street}}<br>{{location_postcode}} {{location_city}}
     * 
     *
     * @return string
     */
    public function formattedAddress()
    {
        
        $location = array (
            CB::get('location', 'name', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_street', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_postcode', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_city', $this->post->ID),
        );

        return implode('<br>', $location);

    }


    /**
     * formattedAddressOneLine
     * 
     * Returns the formatted Location address in one line, separated by comma
     * 
     * @TODO: Do not return tags (,) if values are empty. This applies to  formattedAddress(), too
     *
     * @return void
     */
    public function formattedAddressOneLine()
    {
        return sprintf(
            '%s, %s %s',
            CB::get('location', CB_METABOX_PREFIX . 'location_street', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_postcode', $this->post->ID),
            CB::get('location', CB_METABOX_PREFIX . 'location_city', $this->post->ID)
        );
    }

    /**
     * formattedContactInfo
     * 
     * Returns formatted location contact info with info text
     * 
     * @TODO: do not add any text in here, any text should be in the backend email text field! 
     * @TODO: in cb1, we had: location info that could be hidden until a successful booking. no longer important? 
     * @TODO: "pickup instructions" and "contact information" fulfill the same purpouse? retire one of them?   
     * 
     * @return string
     */
    public function formattedContactInfo()
    {   
        $contact = array();
        if ( !empty( CB::get( 'location', CB_METABOX_PREFIX . 'location_contact') ) ) {
            $contact[] = "<br>"; // needed for email template
            $contact[] = __( 'Please contact the contact persons at the location directly if you have any questions regarding collection or return:', 'commonsbooking' );
            $contact[] = nl2br(CB::get('location',  CB_METABOX_PREFIX . 'location_contact'));
        }

        return implode('<br>', $contact);

    }
    /**
     * formattedContactInfoOneLine
     * 
     * Returns formatted location contact info
     * 
     * @return string
     */
    public function formattedContactInfoOneLine()
    {   
        return CB::get( 'location', CB_METABOX_PREFIX . 'location_contact');
    }
    /**
     * Return Location pickup instructions
     * 
     * 
     * @return string
     */
    public function pickupInstructions()
    {   
        return CB::get( 'location', CB_METABOX_PREFIX . 'location_pickupinstructions');
    }
}
