<?php

namespace rucaua\epa\request;

enum ServerParam:string
{
    case CONTENT_TYPE = 'CONTENT_TYPE';
    case REQUEST_METHOD = 'REQUEST_METHOD';
    case QUERY_STRING = 'QUERY_STRING';
    case ORIG_PATH_INFO = 'ORIG_PATH_INFO';
    case ORIG_SCRIPT_NAME = 'ORIG_SCRIPT_NAME';
    case SCRIPT_NAME = 'SCRIPT_NAME';
    case PHP_SELF = 'PHP_SELF';
    case DOCUMENT_ROOT = 'DOCUMENT_ROOT';
    case SCRIPT_FILENAME = 'SCRIPT_FILENAME';
    case REQUEST_URI = 'REQUEST_URI';
}
