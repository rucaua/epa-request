<?php

declare(strict_types=1);

namespace rucaua\epa\request;

use JetBrains\PhpStorm\NoReturn;

class Request implements RequestInterface
{

//    TODO find more suitable place for it
    const PHP_INPUT = "php://input";

    private array $headers = [];
    private ?array $params = null;
    private ?array $data = null;
    private ?RequestType $type = null;
    private ?string $scriptUrl = null;
    private ?array $path = null;
    private ?string $version = null;
    private ?ContentType $contentType = null;


    public function getHeaders(): array
    {
        if (empty($this->headers)) {
            foreach (getallheaders() as $name => $value) {
                $this->headers[(string)$name] = (string)$value;
            }
        }
        return $this->headers;
    }


    public function getRewriteUrl(): ?string
    {
        return $this->headers[Header::X_REWRITE_URL->value] ?? null;
    }

    public function getServerURI(): ?string
    {
        if ($uri = $_SERVER[ServerParam::REQUEST_URI->value] ?? null) {
            if ($uri !== '' && $uri[0] !== '/') {
                $uri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $uri);
            }
            return $uri;
        }
        return null;
    }


    /**
     * @return string|null URL before processed by PHP
     */
    public function getOriginalURI(): ?string
    {
        if ($uri = $_SERVER[ServerParam::ORIG_PATH_INFO->value] ?? null) {
            if (!empty($_SERVER[ServerParam::QUERY_STRING->value])) {
                $uri .= '?' . $_SERVER[ServerParam::QUERY_STRING->value];
            }
            return $uri;
        }
        return null;
    }


    public function getUrl(): string
    {
        return $this->getRewriteUrl() ?? $this->getServerURI() ?? $this->getOriginalURI() ??
            throw new InvalidRequestException('Unable to determine the request URI.');
    }


    /**
     * @return void
     * @throws InvalidRequestException
     */
    public function parse(): void
    {
        $pathInfo = $this->getUrl();

        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }
        $pathInfo = urldecode($pathInfo);
        $scriptUrl = $this->getScriptUrl();
        $baseUrl = $this->getBaseUrl();
        if (str_starts_with($pathInfo, $scriptUrl)) {
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
        } elseif ($baseUrl === '' || str_starts_with($pathInfo, $baseUrl)) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        } elseif (isset($_SERVER[ServerParam::PHP_SELF->value]) && str_starts_with(
                $_SERVER[ServerParam::PHP_SELF->value],
                $scriptUrl
            )) {
            $pathInfo = substr($_SERVER[ServerParam::PHP_SELF->value], strlen($scriptUrl));
        } else {
            throw new InvalidRequestException('Unable to determine the path info of the current request.');
        }

        if (strncmp($pathInfo, '/', 1) === 0) {
            $pathInfo = substr($pathInfo, 1);
        }
        $this->path = explode('/', $pathInfo);
        $this->version = array_shift($this->path);
    }


    /**
     * @throws InvalidRequestException
     */
    public function getPath(): array
    {
        if ($this->path === null) {
            $this->parse();
        }
        return $this->path;
    }


    /**
     * @throws InvalidRequestException
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->parse();
        }
        return $this->version;
    }


    public function getParams(): array
    {
        if ($this->params === null) {
            $this->params = $_GET;
        }
        return $this->params;
    }


    /**
     * TODO add the ability to add custom ContentType enum from config
     * @return ContentType
     */
    public function getContentType(): ContentType
    {
        if ($this->contentType === null) {
            $rawContentType = $this->headers[Header::CONTENT_TYPE->value] ?? $_SERVER[ServerParam::CONTENT_TYPE->value] ?? '';
            if (($pos = strpos($rawContentType, ';')) !== false) {
                $rawContentType = substr($rawContentType, 0, $pos);
            }
            $this->contentType = ContentType::tryFrom($rawContentType) ?? ContentType::UNKNOWN;
        }
        return $this->contentType;
    }


    public function getData(): array
    {
        if ($this->data === null) {
            /** TODO move logic to Parser objects and add ability to add custom ones from config */
            switch ($this->getContentType()) {
                case ContentType::APPLICATION_JSON:
                    $this->data = json_decode(file_get_contents(self::PHP_INPUT), true);
                    break;
                case ContentType::APPLICATION_X_WWW_FORM_URLENCODED:
                    parse_str(file_get_contents(self::PHP_INPUT), $this->data);
                    break;
                case ContentType::MULTIPART_FORM_DATA:
//                    TODO implement Parser
                default:
                    return $_POST;
            }
        }

        return $this->data;
    }



    #[NoReturn] public function getFilterFromQuery(): array
    {
        /* TODO create some FilterObject (or simple array???) from query params to filter data.*/
        return [];
    }


    public function getType(): RequestType
    {
        if ($this->type === null) {
            $this->type = RequestType::from($_SERVER[ServerParam::REQUEST_METHOD->value]);
        }
        return $this->type;
    }


    public function getScriptUrl(): string
    {
        if ($this->scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (isset($_SERVER[ServerParam::SCRIPT_NAME->value]) && basename(
                    $_SERVER[ServerParam::SCRIPT_NAME->value]
                ) === $scriptName) {
                $this->scriptUrl = $_SERVER[ServerParam::SCRIPT_NAME->value];
            } elseif (isset($_SERVER[$_SERVER[ServerParam::PHP_SELF->value]]) && basename(
                    $_SERVER[$_SERVER[ServerParam::PHP_SELF->value]]
                ) === $scriptName) {
                $this->scriptUrl = $_SERVER[$_SERVER[ServerParam::PHP_SELF->value]];
            } elseif (isset($_SERVER[ServerParam::ORIG_SCRIPT_NAME->value]) && basename(
                    $_SERVER[ServerParam::ORIG_SCRIPT_NAME->value]
                ) === $scriptName) {
                $this->scriptUrl = $_SERVER[ServerParam::ORIG_SCRIPT_NAME->value];
            } elseif (isset($_SERVER[$_SERVER[ServerParam::PHP_SELF->value]]) && ($pos = strpos(
                    $_SERVER[$_SERVER[ServerParam::PHP_SELF->value]],
                    '/' . $scriptName
                )) !== false) {
                $this->scriptUrl = substr($_SERVER[ServerParam::SCRIPT_NAME->value], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER[ServerParam::DOCUMENT_ROOT->value])
                && str_starts_with($scriptFile, $_SERVER[ServerParam::DOCUMENT_ROOT->value])
            ) {
                $this->scriptUrl = str_replace([$_SERVER[ServerParam::DOCUMENT_ROOT->value], '\\'],
                    ['', '/'],
                    $scriptFile);
            } else {
                throw new InvalidRequestException('Unable to determine the entry script URL.');
            }
        }

        return $this->scriptUrl;
    }


    public function getBaseUrl(): string
    {
        return rtrim(dirname($this->getScriptUrl()), '\\/');
    }


    /**
     * @throws InvalidRequestException
     */
    public function getScriptFile(): string
    {
        return $_SERVER[ServerParam::DOCUMENT_ROOT->value] ?? throw new InvalidRequestException(
            'Unable to determine the entry script file path.'
        );
    }
}