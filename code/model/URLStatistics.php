<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A data class representing the info for a action on a Social media URL
 */

class URLStatistics extends DataObject
{
    private static $singular_name = 'Social Action';
    private static $plural_name = 'Social Action';

    private static $db = array(
        'Service' => "Varchar",
        'Action' => 'Varchar',
        'Count' => 'Int'
    );

    private static $summary_fields = array(
        'Service',
        'Action',
        'Count'
    );

    private static $has_one = array(
        'URL' => 'SocialURL'
    );
}
