<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Workspace ID
    |--------------------------------------------------------------------------
    | IBM CloudでのWORKSPACE IDを指定します。
    */
    'workspace_id' => env('WATSON_WORKSPACEID'),
    /*
    |--------------------------------------------------------------------------
    | Default UserName/Password
    |--------------------------------------------------------------------------
    | IBM Cloudで取得したサービス資格情報を指定します。
    */
    'user_name'   => env('WATSON_USER_NAME'),
    'password'    => env('WATSON_PASSWORD'),
    'icf_user'    => env('ICF_USER'),
    'icf_password'=> env('ICF_PASSWORD')];
