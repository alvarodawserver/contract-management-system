<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Formalization Period
    |--------------------------------------------------------------------------
    |
    | Number of months a new contract has to be formalized. The deadline is
    | stored on each contract, so it can be edited individually and changing
    | this value never moves the deadline of existing contracts.
    |
    */

    'formalization_months' => (int) env('CONTRACT_FORMALIZATION_MONTHS', 4),

];
