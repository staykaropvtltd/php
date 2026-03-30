<?php

require_once __DIR__ . '/functions.php';

$GLOBALS['app_config'] = require __DIR__ . '/config.php';

bootstrap_storage();
start_app_session();
auto_login_from_cookie();

$siteContent = load_site_content();
$siteSettings = get_site_settings($siteContent);
