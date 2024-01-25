<?php

namespace rucaua\epa\request;

enum ContentType:string
{
    case UNKNOWN = '';
    case TEXT_HTML = 'text/html';
    case APPLICATION_JSON = 'application/json';
    case MULTIPART_FORM_DATA = 'multipart/form-data';
    case APPLICATION_X_WWW_FORM_URLENCODED = 'application/x-www-form-urlencoded';
}
