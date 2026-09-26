<?php

return [

    /*
     * The AWS region used to sign requests.
     * IAM is global, so any region works.
     */
    'region' => env('IAM_AUDIT_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),

    /*
     * A named profile from ~/.aws/credentials (optional).
     */
    'profile' => env('IAM_AUDIT_PROFILE'),

    /*
     * An access key and secret (optional).
     * Prefer a profile or an IAM role when you can.
     */
    'key' => env('IAM_AUDIT_KEY'),
    'secret' => env('IAM_AUDIT_SECRET'),

];
