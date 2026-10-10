<?php

/*
 * ModuleRouteServiceProvider supplies /api/modules/raonslab-travel_lab and
 * api.modules.raonslab-travel_lab. prefixes. Each scoped route file owns its
 * named routes and authentication/permission middleware. Integration owns
 * this entry point; implementation Requests must not replace shared routes.
 */
require __DIR__.'/catalog.php';
require __DIR__.'/workflow.php';
require __DIR__.'/support.php';
require __DIR__.'/campaigns.php';
