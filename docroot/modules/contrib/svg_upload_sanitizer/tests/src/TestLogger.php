<?php

namespace Drupal\Tests\svg_upload_sanitizer;

use Psr\Log\LoggerInterface;

/**
 * Class TestLogger.
 *
 * @package Drupal\Tests\svg_upload_sanitizer
 *
 * @internal
 */
final class TestLogger implements LoggerInterface {

  private $logs;

  public function __construct() {
    $this->clear();
  }

  /**
   * @return array
   */
  public function getLogs($level = false) {
    return false === $level ? $this->logs : $this->logs[$level];
  }

  public function clear() {
    $this->logs = [
      'emergency' => [],
      'alert' => [],
      'critical' => [],
      'error' => [],
      'warning' => [],
      'notice' => [],
      'info' => [],
      'debug' => [],
    ];
  }

  public function log($level, $message, array $context = []) {
    $this->logs[$level][] = $message;
  }

  public function emergency($message, array $context = []) {
    $this->log('emergency', $message, $context);
  }

  public function alert($message, array $context = []) {
    $this->log('alert', $message, $context);
  }

  public function critical($message, array $context = []) {
    $this->log('critical', $message, $context);
  }

  public function error($message, array $context = []) {
    $this->log('error', $message, $context);
  }

  public function warning($message, array $context = []) {
    $this->log('warning', $message, $context);
  }

  public function notice($message, array $context = []) {
    $this->log('notice', $message, $context);
  }

  public function info($message, array $context = []) {
    $this->log('info', $message, $context);
  }

  public function debug($message, array $context = []) {
    $this->log('debug', $message, $context);
  }

}
