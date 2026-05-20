<?php

// define the inclusion guard needed by the php script that will be pulled in
define('IN_MIGRATION', true);

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
  // All PDO calls need to be wrapped in a try/catch block that will report error
  //   and terminate execution of the migration script
  //--------------------
  // All DDL changes should be completed before any data migration work
  //   - DDL scripts force a commit of data changes before execution and thus
  //     cannot be rolled back
  //--------------------
  // All data migration updates should be wrapped in an inner try/catch block
  //   - Final step in try block is to commit the changes
  //   - Catch block executes a rollback to undo all data changes
  //--------------------------------------------------------------------------------

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

  $pdo = open_pdo_connection();
  verify_pdo_connection($pdo);

  $old_version = current_version($pdo);
  $cur_version = $old_version;
  $tgt_version = '1.1.0';

  // If cur_version is null, this is the initial install
  if (is_null($cur_version)) 
  {
    //   Simply build all tables/views.  There is no data to migrate.
    run_sql_script($pdo,"build_{$tgt_version}");
    add_version_to_history($pdo, $tgt_version, 'Initial Setup');
    return [true, "Schema has been populated with version $tgt_version tables/views."];
  }

  // If cur_version is target version, nothing to do
  if($cur_version == $tgt_version) {
    return [true, "No migration necessary.  Already at version $tgt_version"];
  }

  // Handle DDL changes first
  //   No need to wrap in a transaction as there is no rollback of DDL changes

  run_sql_script($pdo, "build_1.1.0");

  // Now migrate data from old tables to new
  //   wrap all migrations in transaction to allow rollback

  try {
    $pdo->beginTransaction();

    $migrations = [
      '1.1.0' => function (PDO $pdo) {
        // simple data copy
        run_sql_script($pdo,'migrate_1.0.0_to_1.1.0');
        // replace question map to structure map
        require_once('./_config_scripts/content_map.php');
        populate_content_map($pdo);
      },
      // future data migration steps will go here... for example:
      // '1.1.1' => function(PDO $pdo) {
      //   // next step
      // },
      // '1.2.0' => function(PDO $pdo) {
      //   // next step
      // },
      // '1.3.0' => function(PDO $pdo) {
      //   // next step
      // },
    ];

    foreach ($migrations as $version => $migration) {
      if (version_compare($cur_version, $version, '<')) {
        $migration($pdo);
        add_version_to_history($pdo, $tgt_version, "Migrate $cur_version to $version");
        $cur_version = $version;
      }
    }

    $pdo->commit();
  }
  catch(Exception $e) 
  {
    $pdo->rollback();
    $drop_all = file_get_contents(__DIR__.'/drop_1.1.0.sql');
    $pdo->exec($drop_all);
    throw new Exception($e->getMessage() . "\nMigration changes rolled back!\n");
  }

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
 * Wrapper around the PDO execution of SQL script files
 * @param PDO $pdo 
 * @param string $sql_file 
 * @return void 
 */
function run_sql_script(PDO $pdo, string $sql_file)
{
  $sql_commands = require __DIR__ . "/_config_scripts/$sql_file.php";

  foreach($sql_commands as [$line,$cmd]) {
    try {
      $pdo->exec($cmd);
    }
    catch(PDOException $e) {
      $cmd = str_replace("\n","\n  ",$cmd);
      throw new Exception( 
        "MySQL exception:\n  " . $e->getMessage() . "\n\nSource: $sql_file.sql [line $line]\n\n  $cmd\n",
        0, 
        $e
      );
    }
  }
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