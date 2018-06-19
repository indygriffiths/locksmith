<?php

class DomainAdmin extends ModelAdmin {
    private static $menu_title = 'Monitored Domains';

    private static $url_segment = 'domains';

    private static $managed_models = [
        'Domain'
    ];
}