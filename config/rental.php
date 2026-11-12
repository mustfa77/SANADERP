<?php

/*
|--------------------------------------------------------------------------
| Rental rules
|--------------------------------------------------------------------------
*/

return [
    /*
    | Monthly contract returned early: a worker back before this hour is not
    | charged for the return day (24-hour clock).
    */
    'return_cutoff_hour' => 17,
];
