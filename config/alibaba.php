<?php

return [
    // AccessKey ID
    //
    //AccessKey Secret
    //
    'sts' => [
        "access_key_id" => env('ALI_BA_BA_ACCESS_KEY_ID', 'LTAI4GFK9zuQR7NshcTaozRg'),
        "access_ke_secret" =>  env('ALI_BA_BA_ACCESS_KEY_SECRET', 'UQqcgxhB6cTk0JbKbtUobTso22NNuQ'),
        "role_arn" => env('ALI_BA_BA_ROLE_ARN', 'acs:ram::1944641761273715:role/wxgtoogu'),
        "region_id" => env('ALI_BA_BA_REGION_ID', 'cn-chengdu'),
        "bucket" => env('ALI_BA_BA_BUCKET', 'wxgtoogu'),
    ],
    'oss' => [
        "end_point" => env("OSS_END_POINT", "oss-cn-chengdu.aliyuncs.com")
    ]
];
