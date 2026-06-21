<?php

namespace Wpae\Http;


class Request
{
    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getRawContent()
    {
        return $this->content;
    }

    public function getJsonParams()
    {
        if(!empty($this->content) && $this->content !== "null") {

            $jsonDecodedContent = json_decode($this->content, true);

            if (is_null($jsonDecodedContent)) {
                throw new \Exception('Invalid JON Provided');
            }

            return $jsonDecodedContent;
        }
    }

    public function get($element, $default = null)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- generic request wrapper; nonce verification is the caller's responsibility
        if(isset($_GET[$element])) {
            return sanitize_text_field( wp_unslash( $_GET[$element] ) );
        } else {
            return $default;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }
}