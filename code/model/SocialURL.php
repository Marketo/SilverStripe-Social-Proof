<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A data class representing a Social Media URL
 */

class SocialURL extends DataObject
{
    private static $singular_name = 'Social URL';
    private static $plural_name = 'Social URLs';

    private static $db = array(
        'URL' => 'Varchar(1024)',
        'Active' => 'Boolean'
    );

    private static $summary_fields = array(
        'URL',
        'Active'
    );

    private static $defaults = array(
        'Active' => 1
    );

    private static $has_many = array(
        'Statistics' => 'URLStatistics'
    );
}

class SocialURLAdmin extends ModelAdmin {
    private static $managed_models = array(
        'SocialURL'
    );

    private static $url_segment = 'social-url-admin';

    private static $menu_title = 'Social URLs';

}
