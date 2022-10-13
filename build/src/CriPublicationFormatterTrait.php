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
    // Sort and Separate current year from past publications.
    $publications_year = $publications_past = [];
    $titles = array_column($publications, 'title');
    array_multisort($titles, SORT_ASC, $publications);
    $year = date('Y');
    foreach ($publications as $publication) {
      if ($publication['year'] == $year) {
        $publications_year[] = $publication;
      }
      else {
        $publications_past[] = $publication;
      }
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
      $file_output_path = $path . '/'.
        str_replace(
          '.twig',
          '',
          $output_file
        );
      $this->say("Writing $file_output_path");
      file_put_contents(
        $file_output_path,
        $twig->render($output_file,
          ['publications' => $publications,
          'publications_year' => $publications_year,
          'publications_past' => $publications_past
        ])
      );
    }

    // Copy ancillary files to target path.
    self::rsyncCopyFiles(__DIR__ . '/../dist/cri/', $path . '/');
  }

}
