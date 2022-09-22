<?php

namespace PubCrawler;

/**
 * Provides methods to transfer files using rsync.
 */
trait RsyncFilesTrait {

  /**
   * Copies files using rsync from one path to another.
   *
   * @param string $src
   *   The source path.
   * @param string $dst
   *   The destination path.
   * @param bool $quiet
   *   TRUE if the copy should not output transfer progress. Defaults to FALSE.
   */
  public static function rsyncCopyFiles(
    string $src,
    string $dst,
    bool $quiet = FALSE
  ) : void {
    if (!$quiet) {
      passthru("rsync -a --inplace --no-compress $src $dst");
    }
    else {
      exec("rsync -a --inplace --no-compress $src $dst");
    }
  }

}
