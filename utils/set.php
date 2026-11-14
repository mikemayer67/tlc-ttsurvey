<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

/**
 * Provides a barebones proper set class
 *   Eliminates the need for pseudo-sets via associative arrays
 */
class Set implements \Countable, \IteratorAggregate
{
  // ok.. the guts uses an associative array, but that is an internal design decision
  private array $members = [];

  public function __construct(iterable $values = [])
  {
    foreach($values as $value) { $this->add($value); }
  }

  public function getIterator() : \Traversable {
    return new \ArrayIterator($this->values());
  }

  /**
   * Adds the specified value or values to the set
   *   All values must be integers or strings
   * @param int|string|iterable $to_add
   */
  public function add(int|string|iterable $to_add) : void {
    if(is_iterable($to_add)) {
      foreach($to_add as $value) {
        // use the add method so that we don't need to explicitly validate value type
        $this->add($value);
      }
    } else {
      $this->members[$to_add] = 1;
    }
  }

  /**
   * Removes the specified value or values from the set (if present)
   * @param int|string $to_remove 
   */
  public function remove(int|string|iterable $to_remove) : void {
    if(is_iterable($to_remove)) {
      foreach($to_remove as $value) { 
        // use the remove method so that we don't need to explicitly validate value type
        $this->remove($value);
      }
    } else {
      unset($this->members[$to_remove]);
    }
  }

  /**
   * Returns whether or not the specified value is currently in the set
   * @param int|string $value 
   * @return bool 
   */
  public function contains(int|string $value) : bool {
    return isset($this->members[$value]);
  }

  /**
   * Returns the number of values currently in the set
   * @note can also be accessed using the php built-in count() function
   * @return int 
   */
  public function count() : int { return count($this->members); }

  /**
   * Removes all values from the set
   * @return void 
   */
  public function clear() : void { $this->members = []; }

  /**
   * Returns a list of all values in the set in the order they were introduced
   * @return array 
   */
  public function values() : array {
    return array_keys($this->members);
  }

  /**
   * Creates a new Set as the union of the current set with another
   * @note the other set can be any iterable entity
   * @param iterable $other 
   * @return Set 
   */
  public function union(iterable $other) : Set
  {
    $new_set = new Set($this->values());
    $new_set->add($other);
    return $new_set;
  }

  /**
   * Creates a new Set by removing all elements of another set from the current one
   * @note the other set can be any iterable entity
   * @param iterable $other 
   * @return Set 
   */
  public function subtract(iterable $other) : Set
  {
    $new_set = new Set($this->values());
    $new_set->remove($other);
    return $new_set;
  }

  /**
   * Creates a new Set containing the elements common to both this and the other set
   * @note the other set can be any iterable entity
   * @param iterable $other 
   * @return Set 
   */
  public function intersect(iterable $other) : Set
  {
    if(! $other instanceof Set) {
      $other = new Set($other);
    }
    $new_set = new Set();
    foreach($this as $value) {
      if($other->contains($value)) {
        $new_set->add($value);
      }
    }
    return $new_set;
  }
}