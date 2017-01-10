<?php
namespace Foldy;

use Foldy\Exceptions\Exception;

class Response
{
    const CONTENT_TYPE_PLAIN_TEXT = 'text';
    const CONTENT_TYPE_JSON = 'json';

    public $headers = [];
    public $contentType;
    public $content;

    protected function __construct(DIContainer $di)
    {
        $this->di = $di;
        $this->contentType = self::CONTENT_TYPE_JSON;
    }

    public static function create(DIContainer $di):Response
    {
        return new static($di);
    }


    public static function setDefaultContentType(string $content_type)
    {

    }

    public function getHeader(string $key)
    {
        return $this->headers[$key] ?? null;
    }

    public function setHeader(string $key, $value)
    {
        $this->headers[$key] = $value;
    }

    public function getBody():string
    {
        switch ($this->contentType) {
            case self::CONTENT_TYPE_JSON:
                return json_encode($this->content, JSON_UNESCAPED_UNICODE);
            case self::CONTENT_TYPE_PLAIN_TEXT:
                return $this->content;
            default:
                throw new Exception("unknown content type: {$this->contentType}");
        }
    }

    protected function getDefaultContentTypeHeaderValue():string
    {
        switch ($this->contentType) {
            case self::CONTENT_TYPE_JSON:
                return "application/json";
            case self::CONTENT_TYPE_PLAIN_TEXT:
                return "plain/text";
            default:
                throw new Exception("unknown content type: {$this->contentType}");
        }
    }

    protected function sendBody()
    {

        echo $this->getBody();
    }

    protected function sendHeaders()
    {
        $content_type_header_sent = false;
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) == 'content-type') {
                $content_type_header_sent = true;
            }
            if (is_array($v)) {
                foreach ($v as $vv) {
                    header("$k: $vv");
                }
            } else {
                header("$k: $v");
            }
        }
        if (!$content_type_header_sent) {
            $content_type_value = $this->getDefaultContentTypeHeaderValue();
            if ($content_type_value) {
                header("Content-Type: " . $content_type_value);
            }
        }
    }

    public function send()
    {
        $this->sendHeaders();
        $this->sendBody();
        die;
    }
}