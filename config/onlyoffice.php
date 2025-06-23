<?php

return [
    'FILE_SIZE_MAX' => 5242880,
    'STORAGE_PATH' => "",
    'ALONE' => TRUE,

    'DOC_SERV_VIEW' => [".pdf", ".djvu", ".xps"],
    'DOC_SERV_EDITED' => [".docx", ".xlsx", ".csv", ".pptx", ".txt", ".doc"],
    'DOC_SERV_CONVERT' => [".docm", ".doc", ".dotx", ".dotm", ".dot", ".odt", ".fodt", ".ott", ".xlsm", ".xls", ".xltx", ".xltm", ".xlt", ".ods", ".fods", ".ots", ".pptm", ".ppt", ".ppsx", ".ppsm", ".pps", ".potx", ".potm", ".pot", ".odp", ".fodp", ".otp", ".rtf", ".mht", ".html", ".htm", ".xml", ".epub", ".fb2"],

    'DOC_SERV_TIMEOUT' => "120000",
    'DOC_SERV_SITE_URL' => env('ONLYOFFICE_DOCS_SERVER_URL'),
    'DOC_SERV_API_URL' => "web-apps/apps/api/documents/api.js",
    'DOC_SERV_CONVERTER_URL' => "ConvertService.ashx",
    'DOC_SERV_PRELOADER_URL' => "web-apps/apps/api/documents/cache-scripts.html",
    'DOC_SERV_COMMAND_URL' => "coauthoring/CommandService.ashx",
    'DOC_EXAMPLE_URL' => "http://10.0.14.98/inoffice2_onlyoffice/application/libraries/onlyoffice",

    'DOC_SERV_JWT_SECRET' => "",
    'DOC_SERV_JWT_HEADER' => "Authorization",

    'MOBILE_REGEX' => "android|avantgo|playbook|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|symbian|treo|up\\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino",
    'ExtsSpreadsheet' => [
        ".xls", ".xlsx", ".xlsm",
        ".xlt", ".xltx", ".xltm",
        ".ods", ".fods", ".ots", ".csv"
    ],
    'ExtsPresentation' => [
        ".pps", ".ppsx", ".ppsm",
        ".ppt", ".pptx", ".pptm",
        ".pot", ".potx", ".potm",
        ".odp", ".fodp", ".otp"
    ],
    'ExtsDocument' => [
        ".doc", ".docx", ".docm",
        ".dot", ".dotx", ".dotm",
        ".odt", ".fodt", ".ott", ".rtf", ".txt",
        ".html", ".htm", ".mht", ".xml",
        ".pdf", ".djvu", ".fb2", ".epub", ".xps"
    ],
];
