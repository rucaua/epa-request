<?php

namespace rucaua\epa\request;


/**
 * It encapsulates the $_SERVER, $_POST, $_GET variables. Provide methods to parse URI, get headers etc.
 */
interface RequestInterface
{
    public function getHeaders(): array;

    /**
     * @return string
     * @throws InvalidRequestException
     */
    public function getUrl(): string;


    /**
     * @return string
     * @throws InvalidRequestException
     */
    public function getScriptUrl(): string;


    /**
     * @return string
     * @throws InvalidRequestException
     */
    public function getBaseUrl(): string;


    public function getParams(): array;


    public function getPath(): array;


    public function getData(): array;
}