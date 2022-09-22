<?php

namespace PubCrawler;

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;

use PubCrawler\GuzzleApiRequestTrait;

/**
 * Provides methods to retrieve publication data from Elsevier/Scopus.
 */
trait ScopusPublicationScraperTrait {

  use GuzzleApiRequestTrait;

  /**
   * The API key to use when scraping.
   *
   * @var string
   */
  protected string $curScrapeScopusApiKey;

  /**
   * The current URI being scraped.
   *
   * @var string
   */
  protected string $curScrapeScopusUri;

  /**
   * The URI to use when scraping individual publication metadata.
   *
   * @var string
   */
  protected string $curScrapeScopusPublicationUri = "https://api.elsevier.com/content/abstract/scopus_id/%s?apiKey=%s&httpAccept=application/json";

  /**
   * The Scopus publication IDs found in the current scrape.
   *
   * @var string[]
   */
  protected array $curScrapeScopusPublicationIds;

  /**
   * The current Scopus publication ID being scraped.
   *
   * @var string
   */
  protected string $curScrapeScopusPublicationId;

  /**
   * The current Scopus publication data being scraped.
   *
   * @var string[]
   */
  protected array $curScrapeScopusPublicationData;

  /**
   * The Scopus publications found in the current scrape.
   *
   * @var string[]
   */
  protected array $curScrapeScopusPublications;

  /**
   * Scrapes publications from the given Scopus search URI.
   *
   * @param string $search_uri
   *   The URI to query, with a single printf format string for the API key.
   * @param string $api_key
   *   The API key to use when querying the Scopus endpoint.
   *
   * @return string[]
   *   The publications returned by the given search URI.
   */
  protected function scrapeScopusPublications(
    string $search_uri,
    string $api_key
  ) : array {
    $this->initGuzzleClients();
    $this->initScopusScrape($search_uri, $api_key);
    $this->curScrapeScopusPublications = [];
    $this->curScrapeScopusPublicationIds = [];
    $this->setScopusCurPublicationIds();
    $this->setScopusCurPublications();
    return $this->curScrapeScopusPublications;
  }

  /**
   * Initializes the properties needed to query the Scopus search API.
   *
   * @param string $search_uri
   *   The URI to query, with a single printf format string for the API key.
   * @param string $api_key
   *   The API key to use when querying the Scopus endpoint.
   */
  private function initScopusScrape(
    string $search_uri,
    string $api_key
  ) : void {
    $this->curScrapeScopusApiKey = $api_key;
    $this->curScrapeScopusUri = sprintf(
      $search_uri,
      $api_key
    );
  }

  /**
   * Sets the Scopus IDs found within all pages of the search URI.
   */
  private function setScopusCurPublicationIds() : void {
    while ($this->curScrapeScopusUri != '') {
      $this->setScopusPublicationIdsFromPage();
    }
  }

  /**
   * Sets the Scopus publication IDs found within the current search page.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function setScopusPublicationIdsFromPage() : void {
    $this->say("Querying $this->curScrapeScopusUri...");
    $response = $this->guzzleClient->get($this->curScrapeScopusUri);
    $page_data = json_decode(
      $response->getBody(),
      JSON_PRETTY_PRINT
    );
    $this->curScrapeScopusUri = self::getNextLinkFromPageData($page_data);
    $scraped_ids = self::getScopusPublicationIdsFromPageData($page_data);
    if (!empty($scraped_ids)) {
      $this->addScrapedIds($scraped_ids);
    }
  }

  /**
   * Retrieves a 'next page' URI, if it exists, from search result page data.
   *
   * @param array $data
   *   The current page data.
   *
   * @return string
   *   The 'next page' URI, if it exists. Returns an empty string otherwise.
   */
  private static function getNextLinkFromPageData(array $data) : string {
    $links = array_column(
      $data['search-results']['link'],
      '@ref'
    );
    // Get key of 'next' link (if available).
    $next_key = array_search('next', $links);
    if ($next_key) {
      return $data['search-results']['link'][$next_key]['@href'];
    }
    return "";
  }

  /**
   * Retrieves Scopus publication IDs from search result page data.
   *
   * @param array $data
   *   The current page data.
   *
   * @return string[]
   *   An array of Scopus publication IDs found within the page data.
   */
  private static function getScopusPublicationIdsFromPageData(array $data) : array {
    $ids = [];
    foreach ($data['search-results']['entry'] as $entry) {
      $ids[] = self::getScopusIdFromIdentifier($entry['dc:identifier']);
    }
    return $ids;
  }

  /**
   * Transforms a prefixed Scopus ID into an unprefixed one.
   *
   * @param string $id
   *   The ID to transform.
   *
   * @return string
   *   The unprefixed ID.
   */
  private static function getScopusIdFromIdentifier(string $id) : string {
    return substr($id, strpos($id, ":") + 1);
  }

  /**
   * Adds Scopus IDs to the current scrape's Scopus publication IDs list.
   *
   * @param array $ids
   *   The Scopus publication IDs to add to the list.
   */
  private function addScrapedIds(array $ids) : void {
    $this->curScrapeScopusPublicationIds = array_merge(
      $this->curScrapeScopusPublicationIds,
      $ids
    );
  }

  /**
   * Retrieves/sets publication metadata from all scraped publications.
   */
  private function setScopusCurPublications() : void {
    $this->curScrapeScopusUri = '';
    foreach ($this->curScrapeScopusPublicationIds as $this->curScrapeScopusPublicationId) {
      $this->setScopusPublicationPageUri();
      $this->setScopusCurPublication();
    }
  }

  /**
   * Sets the publication metadata URI for the current scraped publication.
   */
  private function setScopusPublicationPageUri() : void {
    $this->curScrapeScopusUri = sprintf(
      $this->curScrapeScopusPublicationUri,
      $this->curScrapeScopusPublicationId,
      $this->curScrapeScopusApiKey
    );
  }

  /**
   * Retrieves/sets publication metadata from the current scraped publication.
   */
  private function setScopusCurPublication() : void {
    $this->say("Querying $this->curScrapeScopusUri...");
    // Individual Items Rarely/Do Not Change. Cache to avoid API queries.
    $response = $this->guzzleCachedClient->get($this->curScrapeScopusUri);
    $this->curScrapeScopusPublicationData = json_decode(
      $response->getBody(),
      JSON_PRETTY_PRINT
    );
    $this->curScrapeScopusPublications[] = $this->getPublicationMetadataFromFullData();
  }

  /**
   * Constructs a useful summary of publication metadata from its full metadata.
   *
   * @return string[]
   *   A standardized associative array of the publication summary metadata.
   */
  private function getPublicationMetadataFromFullData() : array {
    $citation_data = $this->curScrapeScopusPublicationData;
    $doi = $citation_data['abstracts-retrieval-response']['item']['bibrecord']['item-info']['itemidlist']['ce:doi']
      ?? NULL;
    $citation = $citation_data['abstracts-retrieval-response']['item']['bibrecord']['head']
      ?? NULL;
    $citation_full = '';
    $abstract = '';

    // If citation data is valid...
    if ($citation) {
      // Get UNIQUE authors (authors have an entry per affiliation).
      $authors = !empty($citation['author-group'])
        ? array_column($citation['author-group'], 'author') : NULL;
      $authors = $authors ? array_column($authors, '0') : NULL;
      // Get author names.
      $names = !empty($authors[0]['ce:indexed-name'])
        ? array_column($authors, 'ce:indexed-name') : NULL;
      // De-duplicate authors (one entry per affiliation).
      $names = (!empty($names) and (count($names) > 1)) ? array_unique($names)
        : $names;
      // Get title.
      $title = $citation['citation-title'] ?? NULL;
      // Get abstract.
      $abstract = $citation['abstracts'] ?? NULL;
      // Get publication year.
      $year = $citation['source']['publicationyear']['@first'] ?? NULL;
      // Get source title.
      $source = $citation['source']['sourcetitle'] ?? NULL;
      // Get volume.
      $volume = $citation['source']['volisspag']['voliss']['@volume'] ?? NULL;
      // Get issue.
      $issue = $citation['source']['volisspag']['voliss']['@issue'] ?? NULL;
      // Get page range.
      $firstp = $citation['source']['volisspag']['pagerange']['@first'] ?? NULL;
      $lastp = $citation['source']['volisspag']['pagerange']['@last'] ?? NULL;
      // Get pages as backup.
      $pages = $citation['source']['volisspag']['pages'] ?? NULL;
      // Prepare citation elements.
      // Author names.
      $c_names = !empty($names) ? implode(', ', $names) : NULL;
      $c_names = ($c_names and $year) ? "$c_names ($year)."
        : $c_names;
      $c_names = $c_names ? "$c_names " : $c_names;
      // Title.
      $c_title = $title ? trim($title) : NULL;
      // Only add period if title valid and last character is not punctuation.
      if ($c_title and !preg_match("/[.!?,;:]$/", $c_title)) {
        $c_title .= '.';
      }
      // Always add space if valid.
      $c_title = $c_title ? "$c_title " : NULL;
      // Publication.
      $c_pub = $source ?? NULL;
      $c_pub = ($c_pub and $volume) ? "$c_pub, $volume" : $c_pub;
      $c_pub = ($c_pub and $issue) ? "$c_pub($issue)" : $c_pub;
      $c_pub = ($c_pub and $firstp) ? "$c_pub, $firstp" : $c_pub;
      $c_pub = ($c_pub and $lastp) ? "$c_pub-$lastp" : $c_pub;
      $c_pub = ($c_pub and $pages and !$firstp and !$lastp) ? "$c_pub $pages"
        : $c_pub;
      $c_pub = $c_pub ? "$c_pub." : NULL;
      // Build citation.
      $citation_full = ($c_title and $c_names) ? "$c_names$c_title" : $c_title;
      $citation_full = ($citation_full and $c_pub) ? "$citation_full$c_pub"
        : $citation_full;
    }

    // Return results.
    return [
      'scopus_id' => $this->curScrapeScopusPublicationId,
      'doi' => $doi,
      'citation' => $citation_full,
      'abstract' => $abstract,
    ];
  }

}
