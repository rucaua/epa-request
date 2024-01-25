<?php

namespace rucaua\epa\request;

enum Header:string
{
    case CONTENT_TYPE = 'Content-Type';
    case X_REWRITE_URL = 'X-Rewrite-Url';
}
