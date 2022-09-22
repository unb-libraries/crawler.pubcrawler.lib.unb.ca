<?php

namespace PubCrawler\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Robo\Common\ConfigAwareTrait;
use Robo\Robo;
use Robo\Symfony\ConsoleIO;
use Robo\Tasks;

/**
 * Defines a base class for all Pubcrawler Robo commands.
 */
class PubCrawlerCommands extends Tasks {

  use ConfigAwareTrait;

  const PUBCRAWLER_CONFIG_FILENAME = 'pubcrawler.yml';

  /**
   * The timestamp the command was started.
   *
   * @var int
   */
  protected int $commandStartTime;

  /**
   * Should the command display its total runtime when complete?
   *
   * @var bool
   */
  protected bool $displayCommandRunTime = FALSE;

  /**
   * The path to the application root.
   *
   * @var string
   */
  protected string $appRoot;

  /**
   * The current user's configured home directory.
   *
   * @var string
   */
  protected string $userHomeDir;

  /**
   * The IO to use for input/output.
   */
  protected ConsoleIO $pubCrawlIo;

  /**
   * The current user's configured username.
   *
   * @var string
   */
  protected string $userName;

  /**
   * PubCrawlCommands constructor.
   */
  public function __construct() {
    $this->setLocalConfig();
    $this->setLocalUserDetails();
  }

  /**
   * Sets up the local application configuration.
   */
  protected function setLocalConfig() : void {
    $this->appRoot = realpath(__DIR__ . "/../../../../");
    $this->config = Robo::createConfiguration(
      [$this->appRoot . '/' . self::PUBCRAWLER_CONFIG_FILENAME]
    );
  }

  /**
   * Sets up the local user details.
   */
  protected function setLocalUserDetails() : void {
    $this->userName = get_current_user();
    $this->userHomeDir = $_SERVER['HOME'];
  }

  /**
   * Sets the running user's details and credentials.
   *
   * @hook pre-init
   */
  public function setCommandStartTime() : void {
    $this->commandStartTime = time();
  }

  /**
   * Displays the command's total run time.
   *
   * @hook post-command
   */
  public function displayCommandRunTime($result, CommandData $commandData) : void {
    if ($this->displayCommandRunTime) {
      date_default_timezone_set('UTC');
      $start = new \DateTime("@$this->commandStartTime");
      $end = new \DateTime();
      $diff = $start->diff($end);
      $run_string = $diff->format('%H:%I:%S');
      $this->say("Command run time: $run_string");
    }
  }

  /**
   * Sets up the application's IO.
   *
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use for input/output.
   */
  protected function setPubCrawlIo(ConsoleIO $io) : void {
    $this->pubCrawlIo = $io;
  }

  /**
   * Enable this command's total run time display upon completion.
   */
  protected function enableCommandRunTimeDisplay() : void {
    $this->displayCommandRunTime = TRUE;
  }

  /**
   * Disables this command's total run time display upon completion.
   */
  protected function disableCommandRunTimeDisplay() : void {
    $this->displayCommandRunTime = FALSE;
  }

  /**
   * Warns, prompts the user for and conditionally exits the script.
   *
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use when printing the statement.
   * @param string $prompt
   *   The prompt to display to the user.
   */
  protected function warnConfirmExitDestructiveAction(ConsoleIO $io, string $prompt) {
    if (
      $this->warnConfirmDestructiveAction(
        $io,
        $prompt
      ) !== TRUE
    ) {
      exit(0);
    }
  }

  /**
   * Determines if the user wishes to proceed with a destructive action.
   *
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use when printing the statement.
   * @param string $prompt
   *   The prompt to display to the user.
   *
   * @return bool
   *   TRUE if the user wishes to continue. False otherwise.
   */
  protected function warnConfirmDestructiveAction(ConsoleIO $io, string $prompt) : bool {
    $this->warnDestructiveAction($io);
    return ($io->confirm($prompt, FALSE));
  }

  /**
   * Warns the user that a destructive action is about to be performed.
   *
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use when printing the statement.
   */
  protected function warnDestructiveAction(ConsoleIO $io) : void {
    $io->warning('Destructive, Irreversible Actions Ahead!');
  }

}
