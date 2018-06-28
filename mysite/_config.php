<?php

global $project;
$project = 'mysite';

global $database;
$database = '';

require_once 'conf/ConfigureFromEnv.php';

// Set the site locale
i18n::set_locale('en_US');

CMSMenu::remove_menu_item('CMSPagesController');
CMSMenu::remove_menu_item('AssetAdmin');
CMSMenu::remove_menu_item('CommentAdmin');

SS_Report::add_excluded_reports([
    'BrokenFilesReport',
    'BrokenLinksReport',
    'BrokenRedirectorPagesReport',
    'BrokenVirtualPagesReport',
    'RecentlyEditedReport',
    'EmptyPagesReport',
]);
