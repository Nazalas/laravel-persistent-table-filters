<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the table used to store saved filters.
    |
    */
    'table_name' => 'table_filters',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of your User model. Defaults to the
    | standard Laravel auth provider model.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Max Filters Per User Per Resource
    |--------------------------------------------------------------------------
    |
    | Limit how many saved filters a user can have per resource.
    | Set to null for no limit.
    |
    */
    'max_per_user_per_resource' => 20,
];
