<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Exception;

/**
 * Thrown by request analyzer, when there is no portal matching the given URL.
 */
class UrlMatchNotFoundException extends \Exception
{
    /**
     * @param string[] $portalUrls
     * @param string $url
     */
    public function __construct(private $url, array $portalUrls = [])
    {
        $message = 'There exists no portal for the URL "' . $this->url . '"';

        if (!empty($portalUrls)) {
            $message .= ', the URL should begin with one of the following Portal URLs: "' . \implode('", "', $portalUrls) . '"';
        }

        parent::__construct($message, 0);
    }

    /**
     * Returns the url for which no portal exists.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
