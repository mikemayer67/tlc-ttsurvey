<?php

// define the inclusion guard needed by the php script that will be pulled in
define('IN_MIGRATION', true);

//--------------------------------------------------------------------------------
// IMPORTANT NOTE
//   Be sure to back up your data before running this script.  While every effort
//   was made to make sure it won't corrupt your data, bugs happen.
//
//   You will want a way to revcover all your data if things go south.
//
//   The following example shows one way to do this.  Note that you will need
//     to fill in all the placeholders with your particulars.  Note too that
//     you may need to provide fully qualified path to mysql and mysqldump if
//     they are not on your path or if the wrong version is on your path.
//--------------------------------------------------------------------------------
// TABLES=$(mysql -h <<$HOST>> -u <<$USER>> -p -N -B \
//   -e <<SELECT table_name
//       FROM information_schema.tables
//       WHERE table_schema = '$DB'
//         AND table_name NOT LIKE 'bkp\_%'>>)
// 
// mysqldump -h <<$HOST>> -u <<$USER>> -p <<$DB>> ${=TABLES} | tee <<$DUMPFILE>>
//--------------------------------------------------------------------------------

/**
 * Orchestrates the migration from current version to the target version
 * @return array [bool:success, string:status message]
 * Raises Exception or PDOException on most failures
 */
function handle_migration() : array
{
  //--------------------------------------------------------------------------------
  // Notes for all versions
  //--------------------------------------------------------------------------------
  // All PDO calls should be wrapped in a try/catch block that will report error
  //   and terminate execution of the migration script
  //--------------------
  // All DDL changes should be completed before any data migration work
  //   - DDL scripts force a commit of data changes before execution and thus
  //     cannot easily be rolled back
  //   - If the DDL change will result in lost data, an extra step or steps
  //     should be taken prior to the DDL change to "park" any data to be
  //     retained.  This should then be recovered during the data migration phase.
  //     (None of version updates below currently need to do this.)
  //--------------------
  // All data migration updates should be wrapped in an inner try/catch block
  //   - Final step in try block is to commit the changes
  //   - Catch block executes a rollback to undo all data changes
  //--------------------------------------------------------------------------------

  $pdo = open_pdo_connection();
  verify_pdo_connection($pdo);

  $old_version = current_version($pdo);
  $cur_version = $old_version;
  $tgt_version = '1.1.1';

  // If cur_version is null, this is the initial install
  if (is_null($cur_version)) 
  {
    //   Simply build all tables/views.  There is no data to migrate.
    run_pdo_script($pdo,'1.1.0','build');
    run_pdo_script($pdo,'1.1.1','upgrade');
    add_version_to_history($pdo, $tgt_version, 'Initial Setup');
    return [true, "Schema has been populated with version $tgt_version tables/views."];
  }

  // If cur_version is target version, nothing to do
  if($cur_version == $tgt_version) {
    return [true, "No migration necessary.  Already at version $tgt_version"];
  }

  //--------------------------------------------------------------------------------
  // Notes for version 1.1.0
  //--------------------------------------------------------------------------------
  // - Prior verions was 1.0.0
  // - New table/view prefix (tlc_srv_)
  //   - v1.0.0 tables had tlc_tt_prefix 
  //   - v1.0.0 tables can remain untouched, sitting alongside the v1.1.0 tables
  // - Most tables remain unchanged otehr than prefix
  //   - data can be copied unchanged from v1.0.0 to v1.1.1 tables
  // - String table has been removed
  //   - all string ID columns are replaced with varchar columns
  //   - string ID data is replaced with corresponding string from stringn table
  // - Question map table is being replaced with more generic structure map table
  //   - sections are replaced with more generic containers
  //   - containers may be either a section or a question group
  //   - new map table adds content type column
  //   - map data migration requires more than simple query/insert commands
  //--------------------------------------------------------------------------------

  if(version_compare($cur_version,'1.0.0') < 0) {
    return [false, "Schema migration requires at least version 1.0.0"];
  }

  $new_version = '1.1.0';
  if(version_compare($cur_version, $new_version) < 0 ) 
  {
    // Build the new v1.1.0 tables (tlc_srv_...);
    run_pdo_script($pdo,$new_version,'build');

    // Migrate data from old tables to new
    try {
      // wrap all migrations in transaction to allow rollback
      $pdo->beginTransaction();

      // simple data copy
      run_pdo_script($pdo, $new_version,'migrate_data');

      // replace question map to structure map
      run_php_script($pdo, $new_version, 'content_map');

      // update version table
      add_version_to_history($pdo, $new_version, "Migrate $cur_version to $new_version");

      $pdo->commit();

      $cur_version = $new_version;
    } 
    catch (Exception $e) 
    {
      $pdo->rollback();
      run_sql_script($pdo, $new_version, 'drop');
      throw new Exception($e->getMessage() . "\nMigration changes rolled back!\n");
    }
  }

  //--------------------------------------------------------------------------------
  // Notes for version 1.1.1
  //--------------------------------------------------------------------------------
  // - Prior version was 1.1.0
  // - Adds nullable question_id column to tlc_srv_survey_options
  //   - NULL        = option is for general use by any question
  //   - question_id = option is only intended for use by the indicated question
  // - An attempt will be made to automatically migrate the existing option data
  //   - It will assume the following options are general use:
  //     - All 1 word options (i.e., no internal whitespace)
  //     - All options that are currently used by more than 1 question
  //   - All other options will be assumed to be tied to a particular question
  // - It may be necessary to manually update some of the options
  //   - Sorry about that, but I cannot possibly write a fool-proof migration here
  //--------------------------------------------------------------------------------

  $new_version = '1.1.1';
  if(version_compare($cur_version,$new_version) < 0)
  {
    // Update the schema tables/views 
    run_pdo_script($pdo, $new_version,'upgrade');

    try {
      // wrap all migrations in transaction to allow rollback
      $pdo->beginTransaction();

      // add add question_id to question specific options
      run_php_script($pdo, $new_version, 'survey_options');

      // update version table
      add_version_to_history($pdo, $new_version , "Migrate $cur_version to $new_version");

      $pdo->commit();

      $cur_version = $new_version;
    } 
    catch (Exception $e) 
    {
      $pdo->rollback();
      throw new Exception($e->getMessage() . "\nMigration changes rolled back!\n");
    }
  }

  // Done with all incremental updates

  return [true, "Migration from version $old_version to version $tgt_version complete"];
}

/**
 * Constructs a new PDO object using values in the apps config file
 * @return PDO 
 */
function open_pdo_connection() : PDO
{
  $config_file = __DIR__.'/../tlc-ttsurvey.ini';
  $config = parse_ini_file($config_file);
  assert($config, "Failed to read config file: $config_file");

  $host     = $config['mysql_host'];
  $schema   = $config['mysql_schema'];
  $charset  = $config['mysql_charset'];
  if(key_exists('mysql_admin_username',$config)) {
    $username = $config['mysql_admin_username'];
    $password = $config['mysql_admin_password'];
  } else {
    $username = $config['mysql_username'];
    $password = $config['mysql_password'];
  }

  $dsn = "mysql:dbname=$schema;host=$host;charset=$charset";
  $pdo = new PDO($dsn,$username,$password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  return $pdo;
}

/**
 * Verifies that the PDO connection has create/rename/drop permission.
 *   Raise an exception otherwise.
 * @param mixed $pdo 
 * @return void 
 */
function verify_pdo_connection($pdo)
{
  $testTable = 'tlc_srv_migration_test_' . time();

  try {
    // Try CREATE
    $pdo->exec("CREATE TABLE $testTable (id INT)");

    // Try RENAME (optional but useful)
    $renamedTable = $testTable . '_renamed';
    $pdo->exec("RENAME TABLE $testTable TO $renamedTable");

    // Try DROP
    $pdo->exec("DROP TABLE $renamedTable");
  }
  catch (PDOException $e) {
    throw new Exception(
      "Missing permission to modify tables/views\n".
      "Update tlc-ttsurvey.ini to include mysql_admin_username/mysql_admin_password credentials.\n"
    );
  }
}

/**
 * Determines the currently avail
 * @param PDO $pdo 
 * @return null|string: current version
 */
function current_version(PDO $pdo) : ?string
{
  $versions = [];
  // query both version tables (which may or may not exist)
  foreach( ['tlc_tt','tlc_srv'] as $prefix ) {
    try { 
      $rows = $pdo->query("select version from {$prefix}_version_history")->fetchAll(PDO::FETCH_COLUMN);
      if($rows) { $versions = array_merge($versions,$rows); }
    }
    catch(PDOException $e) { /* missing table is acceptable, just move on */ }
  }

  if(!$versions) { return null; }

  // sort all versions and return the newest
  usort($versions,'version_compare');
  return end($versions);
}

/**
 * Updates the current version history table
 * @param PDO $pdo 
 * @param string $version 
 * @param string $description 
 * @return void 
 */
function add_version_to_history(PDO $pdo, string $version, string $description)
{
  $s = $pdo
    ->prepare('insert into tlc_srv_version_history (version,change_description) values (?,?)');
  $s->execute([$version,$description]);
}

/**
 * Wrapper around the PDO execution of a PDO script
 *   A PDO script contains an a list of array containing a line number and SQL command
 * @param PDO $pdo 
 * @param string $version
 * @param string $pdo_script
 * @return void 
 */
function run_pdo_script(PDO $pdo, string $version, string $pdo_script)
{
  $sql_commands = require __DIR__ . "/v$version/$pdo_script.php";

  foreach($sql_commands as [$line,$cmd]) {
    try {
      $pdo->exec($cmd);
    }
    catch(PDOException $e) {
      $cmd = str_replace("\n","\n  ",$cmd);
      throw new Exception( 
        "MySQL exception:\n  " . $e->getMessage() . "\n\nSource: v$version/$pdo_script.php [line $line]\n\n  $cmd\n",
        0, 
        $e
      );
    }
  }
}

/**
 * Wrapper around the PDO execution of the content of a SQL script
 * @param PDO $pdo 
 * @param string $version 
 * @param string $sql_script 
 * @return void 
 */
function run_sql_script(PDO $pdo, string $version, string $sql_script)
{
  $sql = file_get_contents(__DIR__ . '/v$version/sql_script.sql');
  $pdo->exec($sql);
}


/**
 * Wrapper around the execution of a PHP support script 
 *   The script must define a function named run_script that takes a single 
 *   PDO instance as its argument.
 * @param PDO $pdo 
 * @param string $version 
 * @param string $php_script 
 * @return void 
 */
function run_php_script(PDO $pdo, string $version, string $php_script)
{
  // The imported script must set $config_script
  //   which must be of the form $config_script = function(PDO $pdo)
  require(__DIR__ . "/v$version/$php_script.php");
  $config_script($pdo);
}

// We're good to go. Do It...

try {
  [$success,$msg] = handle_migration();
  print("\n$msg\n\n");
  exit($success ? 0 : 1);
}
catch(Exception $e) {
  print("\nMigration Failed!\n\n".$e->getMessage()."\n");
  exit(1);
}

?>