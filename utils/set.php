<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

class Set
{
  private array $members = [];

  public function add(int|string $value) : void {
    $this->members[$value] = 1;
  }

  public function remove(int|string $value) : void {
    unset($this->members[$value]);
  }

  public function contains(int|string $value) : bool {
    return isset($this->members[$value]);
  }

  public function count() : int {
    return count($this->members);
  }

  public function clear() : void {
    $this->members = [];
  }

  public function values() : array {
    return array_keys($this->members);
  }
}