<?php

namespace PubCrawler;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

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
   */
  protected function initCachedGuzzleClient() : void {
    $stack = HandlerStack::create();
    $stack->push(
      new CacheMiddleware(
        new PrivateCacheStrategy(
          new Psr6CacheStorage(
            new FilesystemAdapter('pubcrawler', 0, '/guzzle_cache')
          )
        )
      ),
      'cache'
    );
    $this->guzzleCachedClient = new Client(['handler' => $stack]);
  }

}
