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
        'URL' => 'Varchar(1024)',
        'Service' => 'Varchar',
        'Action' => 'Varchar',
        'Count' => 'Int'
    );

    private static $summary_fields = array(
        'Service',
        'Action',
        'Count'
    );
}

class URLStatisticsAdmin extends ModelAdmin {
    private static $managed_models = array(
        'URLStatistics'
    );

    private static $url_segment = 'social-url-admin';

    private static $menu_title = 'Social URLs';

}
