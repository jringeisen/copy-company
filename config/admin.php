<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Email Addresses
    |--------------------------------------------------------------------------
    |
    | These email addresses have access to the admin area of the application.
    | Users must be logged in with one of these email addresses to access
    | admin routes and features.
    |
    */

    'emails' => array_filter(
        array_map('trim', explode(',', env('ADMIN_EMAILS', '')))
    ),

];
