<?php

namespace PubCrawler\Robo\Plugin\Commands;

use PubCrawler\CriPublicationFormatterTrait;
use PubCrawler\Robo\Plugin\Commands\PubCrawlerCommands;
use PubCrawler\RsyncFilesTrait;
use PubCrawler\ScopusPublicationScraperTrait;
use PubCrawler\TemporaryDirectoryTrait;
use Robo\Symfony\ConsoleIO;

/**
 * Provides scraping/rendering of publication metadata from online collections.
 */
class PubCrawlerScraperCommands extends PubCrawlerCommands {

  use CriPublicationFormatterTrait;
  use RsyncFilesTrait;
  use ScopusPublicationScraperTrait;
  use TemporaryDirectoryTrait;

  const ERROR_NO_COLLECTIONS_FOUND = 'No collections were defined to scrape.';
  const ERROR_OUTPUT_PATH_UNSET = 'The output path has not been set in configuration.';

  /**
   * The path to write the rendered output.
   *
   * @var string
   */
  protected string $renderedOutputPath;

  /**
   * The collection currently being scraped.
   *
   * @var string[]
   */
  protected array $curScrapeCollection;

  /**
   * The ID of the collection currently being scraped.
   *
   * @var string
   */
  protected string $curScrapeCollectionId;

  /**
   * The path to the current collection's temporary dir.
   *
   * @var string
   */
  protected string $curScrapeCollectionTempDir;

  /**
   * The list of collections to scrape.
   *
   * @var string[]
   */
  protected array $scraperCollections;

  /**
   * Scrapes the data from all defined collections and renders them.
   *
   * @command pubcrawler:scrape
   * @aliases scrape
   */
  public function scrapeAndRenderCollections(ConsoleIO $io) : void {
    $this->setPubCrawlIo($io);
    $this->initCollectionsScrape();

    foreach ($this->scraperCollections as $this->curScrapeCollectionId => $this->curScrapeCollection) {
      $this->io()->title($this->curScrapeCollection['name']);
      $this->createItemTemporaryDir();

      // Get publications from the defined scraper.
      $publications = $this->{$this->curScrapeCollection['scraper']}(
        $this->curScrapeCollection['uri'],
        getenv($this->curScrapeCollection['api-key-env-secret'])
      );

      // Write HTML via the defined writer.
      $this->{$this->curScrapeCollection['writer']}(
        $publications,
        $this->curScrapeCollectionTempDir
      );

      $this->copyTemporaryFilesToOutput();
    }
  }

  /**
   * Initializes and sets up the scraper run.
   *
   * @throws \Exception
   */
  protected function initCollectionsScrape()  : void {
    $this->setOutputDir();
    $this->setScraperCollections();
  }

  /**
   * Sets the rendered output path from config.
   *
   * @throws \Exception
   */
  private function setOutputDir() : void {
    $this->renderedOutputPath = $this->config->get('pubcrawler.output.dir');
    if (empty($this->renderedOutputPath )) {
      throw new \Exception(self::ERROR_OUTPUT_PATH_UNSET);
    }
  }

  /**
   * Sets the collections to be scraped from config.
   *
   * @throws \Exception
   */
  private function setScraperCollections() : void {
    $this->scraperCollections = $this->config->get('pubcrawler.collections');
    if (empty($this->scraperCollections)) {
      throw new \Exception(self::ERROR_NO_COLLECTIONS_FOUND);
    }
  }

  /**
   * Creates a temporary directory for processing the current collection.
   */
  protected function createItemTemporaryDir() : void {
    $this->curScrapeCollectionTempDir = $this->tempdir();
  }

  /**
   * Copies the current rendered collection from its temporary path to output.
   */
  protected function copyTemporaryFilesToOutput() : void {
    $collection_dir = $this->renderedOutputPath . '/' . $this->curScrapeCollectionId;
    exec("mkdir -p $collection_dir");
    // Copy the rendered output to the target dir.
    self::rsyncCopyFiles(
      $this->curScrapeCollectionTempDir . '/',
      $collection_dir . '/'
    );
  }

}
