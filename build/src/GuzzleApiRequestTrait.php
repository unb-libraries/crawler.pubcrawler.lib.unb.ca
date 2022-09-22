<?php

namespace PubCrawler;

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;

/**
 * Provides methods to query HTTP endpoints with Guzzle.
 */
trait GuzzleApiRequestTrait {

  /**
   * The cached guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $guzzleCachedClient;

  /**
   * The un-cached guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $guzzleClient;

  /**
   * Initializes the cached and uncached guzzle clients.
   */
  protected function initGuzzleClients() : void {
    $this->initGuzzleClient();
    $this->initCachedGuzzleClient();
  }

  /**
   * Initializes the uncached guzzle client.
   */
  protected function initGuzzleClient() : void {
    $this->guzzleClient = new Client();
  }

  /**
   * Initializes the cached guzzle client.
   *
   * @TODO Doctrine caching deprecated. Migrate away.
   */
  protected function initCachedGuzzleClient() : void {
    $stack = HandlerStack::create();
    $stack->push(
      new CacheMiddleware(
        new PrivateCacheStrategy(
          new DoctrineCacheStorage(
            new FilesystemCache('/guzzle_cache/')
          )
        )
      ),
      'cache'
    );
    $this->guzzleCachedClient = new Client(['handler' => $stack]);
  }

}
