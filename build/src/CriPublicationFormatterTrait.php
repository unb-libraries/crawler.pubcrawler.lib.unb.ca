<?php

namespace PubCrawler;

use PubCrawler\RsyncFilesTrait;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Provides methods to format publication data to CRI output specifications.
 */
trait CriPublicationFormatterTrait {

  use RsyncFilesTrait;

  /**
   * Renders scraped citations to CRI output specifications.
   *
   * @param string[] $publications
   *   The scraped publications to render.
   * @param string $path
   *   The target path to render in.
   *
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  protected function writeCriPublications(
    array $publications,
    string $path
  ) : void {
    // Recover CRI affiliation id.
    $cri_id = $this->config->get('pubcrawler.collections.cri.af-id');
    // Capture timestamp.
    $datestamp = date('F j, Y');
    // Sort and Separate current year from past publications.
    $publications_year = $publications_past = [];
    $years = array_column($publications, 'year');
    $citations = array_column($publications, 'citation');
    // Sort by year and alpha, case insensitive.
    array_multisort($years, SORT_DESC, $citations, SORT_ASC, SORT_NATURAL|SORT_FLAG_CASE, $publications);
    $year = substr($datestamp, -4);
    foreach ($publications as $publication) {
      // Only add if publication has a direct CRI affiliation.
      //if (in_array($cri_id, $publication['af_ids'])) {
        if ($publication['year'] == $year) {
          $publications_year[] = $publication;
        }
        else {
          $publications_past[] = $publication;
        }
      //}
    }
    // Setup Twig.
    $loader = new FilesystemLoader(__DIR__ . '/../templates/cri');
    $options = array(
      'strict_variables' => false,
      'debug' => false,
      'cache'=> false
    );
    $twig = new Environment($loader, $options);

    // Render Twig.
    $output_files = [
      'publications-embed.html.twig'
    ];

    foreach ($output_files as $output_file) {
      $this->say("Writing $output_file");
      $file_output_path = $path . '/'. 'index.html';
      $this->say("Writing $file_output_path");
      file_put_contents(
        $file_output_path,
        $twig->render($output_file, [
          'datestamp' => $datestamp,
          'publications_year' => $publications_year,
          'publications_past' => $publications_past
        ])
      );
    }

    // Copy ancillary files to target path.
    self::rsyncCopyFiles(__DIR__ . '/../dist/cri/', $path . '/');
  }

}
