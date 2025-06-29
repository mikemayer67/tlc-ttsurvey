<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

class Strings {

  static private $cache = array();
  static private $xref = array();

  static public function get_string(int $id) 
  {
    $rval = self::$cache[$id] ?? null;
    if(isset($rval)) { return $rval; }

    $rval = MySQLSelectValue('select str from tlc_tt_strings where id=?','i',$id);

    // we don't store falsy values in the database string dictionary
    if(!$rval) { return null; }

    self::$cache[$id] = $rval;
    self::$xref[$rval] = $id;

    return $rval;
  }

  static public function get_strings(array $ids)
  {
    $rval = array();
    $missing = array();
    foreach($ids as $id) {
      $s = self::$cache[$id] ?? null;
      if(isset($s)) {
        $rval[$id] = $s;
      } else {
        $missing[] = $id;
      }
    }
    if($missing) {
      $missing = implode(',',array_map('intval',$missing));
      $rows = MySQLSelectRows("select id,str from tlc_tt_strings where id in ($missing)");
      foreach($rows as $row) {
        $id = $row['id'];
        $s = $row['str'];
        $rval[$id] = $s;
        self::$cache[$id] = $s;
        self::$xref[$s] = $id;
      }
    }
    return $rval;
  }

  static public function get_id(string|null $s, bool $create=true)
  {
    // we don't store falsy values in the database string dictionary
    if(!$s) { return null; }

    $rval = self::$xref[$s] ?? null;
    if(isset($rval)) { return $rval; }

    $h = hex2bin(hash('sha256',$s));
    if ($h === false) {
      $s = substr(addslashes($s),0,256);
      internal_error("Invalid hash for string: '$s'");
    }

    $rval = MySQLSelectValue('select id from tlc_tt_strings where str_hash=?','s',$h);

    if($rval) { 
      // 0 isn't a valid string dictionary index, so we don't need to worry about that
      self::$cache[$rval] = $s;
      self::$xref[$s] = $rval;
      return $rval;
    }
    if(!$create) {
      return null;
    }

    // not found, let's add it to the dictionary now
    $rc = MySQLExecute('insert into tlc_tt_strings (str) values (?)','s', $s);
    if(!$rc) {
      $s = substr(addslashes($s),0,256);
      internal_error("Failed to create string dictionary entry for '$s'");
    }
    $rval = MySQLInsertID();

    self::$cache[$rval] = $s;
    self::$xref[$s] = $rval;
    return $rval;
  }
};

function strings_find($s)           { return Strings::get_id($s,false);  }
function strings_find_or_create($s) { return Strings::get_id($s,true);   }
function strings_get($id)           { return Strings::get_string($id);   }

function strings_bulk_lookup(...$args) 
{
  $ids = count($args) === 1 && is_array($args[0]) ? $args[0] : $args;
  $ids = array_map('intval', $ids);
  return Strings::get_strings($ids); 
}
