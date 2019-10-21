<?php
/**
 * LaraClassified - Classified Ads Web Application
 * Copyright (c) BedigitCom. All Rights Reserved
 *
 * Website: http://www.bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from Codecanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

namespace App\Helpers;


class DBTool
{
	/**
	 * Get PDO Connexion
	 *
	 * @param array $database
	 * @return \PDO
	 */
	public static function getPDOConnexion($database = [])
	{
		$error = null;
		
		// Retrieve Database Parameters from the /.env file,
		// If they are not set during the function call.
		if (empty($database)) {
			$database = DBTool::getDatabaseConnectionInfo();
		}
		
		// Database Parameters
		$driver = isset($database['driver']) ? $database['driver'] : 'mysql';
		$host = isset($database['host']) ? $database['host'] : '';
		$port = isset($database['port']) ? $database['port'] : '';
		$username = isset($database['username']) ? $database['username'] : '';
		$password = isset($database['password']) ? $database['password'] : '';
		$database = isset($database['database']) ? $database['database'] : '';
		$charset = isset($database['charset']) ? $database['charset'] : 'utf8';
		$socket = isset($database['socket']) ? $database['socket'] : '';
		$options = isset($database['options']) ? $database['options'] : [
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_EMULATE_PREPARES   => true,
			\PDO::ATTR_CURSOR             => \PDO::CURSOR_FWDONLY,
		];
		
		try {
			// Get the Connexion's DSN
			if (empty($socket)) {
				$dsn = $driver . ':host=' . $host . ';port=' . $port . ';dbname=' . $database . ';charset=' . $charset;
			} else {
				$dsn = $driver . ':unix_socket=' . $database['socket'] . ';dbname=' . $database . ';charset=' . $charset;
			}
			// Connect to the Database Server
			$pdo = new \PDO($dsn, $username, $password, $options);
			
			return $pdo;
			
		} catch (\PDOException $e) {
			$error = "<pre><strong>ERROR:</strong> Can't connect to the database server. " . $e->getMessage() . "</pre>";
		} catch (\Exception $e) {
			$error = "<pre><strong>ERROR:</strong> The database connection failed. " . $e->getMessage() . "</pre>";
		}
		
		die($error);
	}
	
	/**
	 * Database Connection Info
	 *
	 * @return mixed
	 */
	public static function getDatabaseConnectionInfo()
	{
		$config = DBTool::getLaravelDatabaseConfig();
		$defaultDatabase = $config['connections'][$config['default']];
		
		// Database Parameters
		$database['driver'] = $defaultDatabase['driver'];
		$database['host'] = $defaultDatabase['host'];
		$database['port'] = (int)$defaultDatabase['port'];
		$database['socket'] = $defaultDatabase['unix_socket'];
		$database['username'] = $defaultDatabase['username'];
		$database['password'] = $defaultDatabase['password'];
		$database['database'] = $defaultDatabase['database'];
		$database['charset'] = $defaultDatabase['charset'];
		$database['options'] = [
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_EMULATE_PREPARES   => true,
			\PDO::ATTR_CURSOR             => \PDO::CURSOR_FWDONLY,
		];
		
		return $database;
	}
	
	/**
	 * @return array
	 */
	public static function getLaravelDatabaseConfig()
	{
		return (array)include realpath(__DIR__ . '/../../config/database.php');
	}
	
	/**
	 * Get full table name by adding the DB prefix
	 *
	 * @param $name
	 * @return string
	 */
	public static function rawTable($name)
	{
		$config = DBTool::getLaravelDatabaseConfig();
		$defaultDatabase = $config['connections'][$config['default']];
		$databasePrefix = $defaultDatabase['prefix'];
		
		return $databasePrefix . $name;
	}
	
	/**
	 * Close PDO Connexion
	 *
	 * @param $pdo
	 */
	public static function closePDOConnexion(&$pdo)
	{
		$pdo = null;
	}
	
	/**
	 * Get full table name by adding the DB prefix
	 *
	 * @param $name
	 * @return string
	 */
	public static function table($name)
	{
		return \DB::getTablePrefix() . $name;
	}
	
	/**
	 * Quote a value with astrophe to inject to an SQL statement
	 *
	 * @param $value
	 * @return mixed
	 */
	public static function quote($value)
	{
		return \DB::getPdo()->quote($value);
	}
	
	/**
	 * Get the MySQL version
	 *
	 * @param null $pdo
	 * @return int|mixed
	 */
	public static function getMySqlVersion($pdo = null)
	{
		$version = 0;
		
		try {
			if (empty($pdo)) {
				$pdo = \DB::connection()->getPdo();
			}
			
			if ($pdo instanceof \PDO) {
				$version = $pdo->query('select version()')->fetchColumn();
				
				$tmp = [];
				preg_match('/^[0-9\.]+/', $version, $tmp);
				if (isset($tmp[0])) {
					$version = $tmp[0];
				}
			}
		} catch (\Exception $e) {}
		
		return $version;
	}
	
	/**
	 * Check if the entered value is the MySQL minimal version
	 *
	 * @param $min
	 * @return bool
	 */
	public static function isMySqlMinVersion($min)
	{
		// Get the MySQL version
		$version = DBTool::getMySqlVersion();
		
		return (version_compare($version, $min) >= 0);
	}
	
	/**
	 * Import SQL File
	 *
	 * @param $pdo
	 * @param $sqlFile
	 * @param null $tablePrefix
	 * @param null $InFilePath
	 * @return bool
	 */
	public static function importSqlFile($pdo, $sqlFile, $tablePrefix = null, $InFilePath = null)
	{
		try {
			
			if (!$pdo instanceof \PDO) {
				return false;
			}
			
			// Enable LOAD LOCAL INFILE
			$pdo->setAttribute(\PDO::MYSQL_ATTR_LOCAL_INFILE, true);
			
			$errorDetect = false;
			
			// Temporary variable, used to store current query
			$tmpLine = '';
			
			// Read in entire file
			$lines = file($sqlFile);
			
			// Loop through each line
			foreach ($lines as $line) {
				// Skip it if it's a comment
				if (substr($line, 0, 2) == '--' || trim($line) == '') {
					continue;
				}
				
				// Read & replace prefix
				$line = str_replace(['<<prefix>>', '<<InFilePath>>'], [$tablePrefix, $InFilePath], $line);
				
				// Add this line to the current segment
				$tmpLine .= $line;
				
				// If it has a semicolon at the end, it's the end of the query
				if (substr(trim($line), -1, 1) == ';') {
					try {
						// Perform the Query
						$pdo->exec($tmpLine);
					} catch (\PDOException $e) {
						echo "<br><pre>Error performing Query: '<strong>" . $tmpLine . "</strong>': " . $e->getMessage() . "</pre>\n";
						$errorDetect = true;
					}
					
					// Reset temp variable to empty
					$tmpLine = '';
				}
			}
			
			// Check if error is detected
			if ($errorDetect) {
				return false;
			}
			
		} catch (\Exception $e) {
			echo "<br><pre>Exception => " . $e->getMessage() . "</pre>\n";
			return false;
		}
		
		return true;
	}
	
	/**
	 * Perform MySQL Database Backup
	 *
	 * @param $pdo
	 * @param string $tables
	 * @param string $filePath
	 * @return bool
	 */
	public static function backupDatabaseTables($pdo, $tables = '*', $filePath = '/')
	{
		try {
			
			if (!$pdo instanceof \PDO) {
				return false;
			}
			
			// Get all of the tables
			if ($tables == '*') {
				$tables = [];
				$query = $pdo->query('SHOW TABLES');
				while ($row = $query->fetch_row()) {
					$tables[] = $row[0];
				}
			} else {
				$tables = is_array($tables) ? $tables : explode(',', $tables);
			}
			
			if (empty($tables)) {
				return false;
			}
			
			$out = '';
			
			// Loop through the tables
			foreach ($tables as $table) {
				$query = $pdo->query('SELECT * FROM ' . $table);
				$numColumns = $query->field_count;
				
				// Add DROP TABLE statement
				$out .= 'DROP TABLE ' . $table . ';' . "\n\n";
				
				// Add CREATE TABLE statement
				$query2 = $pdo->query('SHOW CREATE TABLE ' . $table);
				$row2 = $query2->fetch_row();
				$out .= $row2[1] . ';' . "\n\n";
				
				// Add INSERT INTO statements
				for ($i = 0; $i < $numColumns; $i++) {
					while ($row = $query->fetch_row()) {
						$out .= "INSERT INTO $table VALUES(";
						for ($j = 0; $j < $numColumns; $j++) {
							$row[$j] = addslashes($row[$j]);
							$row[$j] = preg_replace("/\n/us", "\\n", $row[$j]);
							if (isset($row[$j])) {
								$out .= '"' . $row[$j] . '"';
							} else {
								$out .= '""';
							}
							if ($j < ($numColumns - 1)) {
								$out .= ',';
							}
						}
						$out .= ');' . "\n";
					}
				}
				$out .= "\n\n\n";
			}
			
			// Save file
			\File::put($filePath, $out);
			
		} catch (\Exception $e) {
			echo "<br><pre>Exception => " . $e->getMessage() . "</pre>\n";
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check If a MySQL function exists
	 *
	 * @param $name
	 * @return bool
	 */
	public static function checkIfMySQLFunctionExists($name)
	{
		$cacheId = 'checkIfMySQLFunctionExists.' . $name;
		$exists = \Cache::rememberForever($cacheId, function () use ($name) {
			// Get the app's database name
			$schema = config('database.connections.' . config('database.default', 'mysql') . '.database');
			
			// Check with method #1
			try {
				$sql = 'SHOW FUNCTION STATUS;';
				$entries = \DB::select(\DB::raw($sql));
				$entries = collect($entries)->whereStrict('Db', $schema)->whereStrict('Name', $name);
				$exists = !$entries->isEmpty();
			} catch (\Exception $e) {
				$exists = false;
			}
			
			// Check with method #2
			if (!$exists) {
				try {
					$sql = 'SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE="FUNCTION" AND ROUTINE_SCHEMA="' . $schema . '"';
					$entries = \DB::select(\DB::raw($sql));
					$entries = collect($entries)->whereStrict('ROUTINE_NAME', $name);
					$exists = !$entries->isEmpty();
				} catch (\Exception $e) {
					$exists = false;
				}
			}
			
			return $exists;
		});
		
		return $exists;
	}
	
	/**
	 * Check If a MySQL Distance Calculation function exists
	 *
	 * @param $name
	 * @return bool
	 */
	public static function checkIfMySQLDistanceCalculationFunctionExists($name)
	{
		if (request()->ajax()) {
			return false;
		}
		
		if (DBTool::isMySqlMinVersion('5.7.6')) {
			// The 'ST_Distance_Sphere' function is included in MySQL 5.7.6+
			if ($name == 'ST_Distance_Sphere') {
				return true;
			}
		} else {
			/*
			If the MySQL version is < 5.7.6,
			and (by surprise) the admin user has selected the 'MySQL 5.7 Spherical Calculation (ST_Distance_Sphere)'
			that not exists under MySQL 5.7.5 and lower as 'Distance Calculation Formula',
			then set 'Haversine' as the default 'Distance Calculation Formula'.
			*/
			if ($name == 'ST_Distance_Sphere') {
				$name = 'haversine';
				\Config::set('settings.listing.distance_calculation_formula', $name);
			}
		}
		
		// Check If the MySQL function exists
		return DBTool::checkIfMySQLFunctionExists($name);
	}
	
	/**
	 * Create the MySQL Distance Calculation function, If doesn't exist,
	 * Using the Haversine or the Orthodromy formula
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function createMySQLDistanceCalculationFunction($name = 'haversine')
	{
		if (request()->ajax()) {
			return false;
		}
		
		if (DBTool::isMySqlMinVersion('5.7.6')) {
			if ($name == 'ST_Distance_Sphere') {
				return true;
			}
		}
		
		if ($name == 'haversine') {
			return DBTool::createMySQLHaversineFunction();
		}
		
		if ($name == 'orthodromy') {
			return DBTool::createMySQLOrthodromyFunction();
		}
		
		return false;
	}
	
	/**
	 * Get MySQL Point Property Functions (related to the MySQL version)
	 *
	 * @param $property
	 * @return string
	 */
	public static function getMySQLPointPropertyFunc($property)
	{
		$properties = ['X', 'Y'];
		
		if (in_array($property, $properties)) {
			if (DBTool::isMySqlMinVersion('5.7.6')) {
				$property = 'ST_' . $property;
			}
		}
		
		return $property;
	}
	
	/*
	========================================================================================================================
	Haversine Formula
	=================
	The haversine formula is an equation important in navigation,
	giving great-circle distances between two points on a sphere from their longitudes and latitudes.
	
	FORMULA
	=======
	a = sin²(Δφ/2) + cos φ1 ⋅ cos φ2 ⋅ sin²(Δλ/2)
	c = 2 ⋅ atan2( √a, √(1−a) )
	d = R ⋅ c
	Where: φ (Phi) is latitude, λ (Lambda) is longitude, R is Earth's radius (Radius = 6371 km (3959 mi));
	Note that angles need to be in radians to pass to trig functions!
	-----
	3959 * acos(cos(radians('.$lat.')) * cos(radians(a.lat)) * cos(radians(a.lon) - radians('.$lon.')) + sin(radians('.$lat.')) * sin(radians(a.lat)))) as distance
	
	JavaScript
	==========
	var R = 6371e3; // metres (Calculation: 6371 km x 1000 = 6371000 m (metres) || 3959mi x 1760 = 6967840 yd (yards))
	var φ1 = lat1.toRadians();
	var φ2 = lat2.toRadians();
	var Δφ = (lat2-lat1).toRadians();
	var Δλ = (lon2-lon1).toRadians();
	
	var a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
			Math.cos(φ1) * Math.cos(φ2) *
			Math.sin(Δλ/2) * Math.sin(Δλ/2);
	var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
	
	var d = R * c;
	
	SOURCES
	=======
	http://www.movable-type.co.uk/scripts/latlong.html
	https://developers.google.com/maps/solutions/store-locator/clothing-store-locator#findnearsql
	========================================================================================================================
	*/
	/**
	 * Create the MySQL Haversine function
	 *
	 * This is a polyfill of the MySQL 'ST_Distance_Sphere' function using the Haversine formula.
	 * ---------------
	 * Unit Conversion
	 * ---------------
	 * The general rule for units is that the output length units are the same as the input length units.
	 *
	 * The OSM way geometry data has length units of degrees of latitude and longitude (SRID=4326).
	 * Therefore, the output units from 'ST_Distance' will also have length units of degrees, which are not really useful.
	 *
	 * There are several things you can do:
	 * - Use 'ST_Distance_Sphere' for fast/approximate distances in metres
	 * - Use 'ST_Distance_Spheroid' for accuracy distances in metres
	 * - Convert the lat/long geometry data types to geography, which automatically makes 'ST_Distance' and other functions to use linear units of metres
	 *
	 * More Info: https://stackoverflow.com/questions/13222061/unit-of-return-value-of-st-distance
	 * ---------------
	 *
	 * USAGE
	 * - Results in Km   : (haversine(POINT(lon1, lat1), POINT(lon2, lat2)) / 1000) AS distance
	 * - Results in Miles: ((haversine(POINT(lon1, lat1), POINT(lon2, lat2)) / 1000) * 0.62137119) AS distance
	 * Where
	 * - X / 1000        => Meters To Km
	 * - X * 0.621371192 => Km To Miles
	 *
	 * @return bool
	 */
	public static function createMySQLHaversineFunction()
	{
		try {
			
			// Drop the function, If exists
			$sql = 'DROP FUNCTION IF EXISTS haversine;';
			\DB::statement($sql);
			
			// Point Property Functions
			$ptX = DBTool::getMySQLPointPropertyFunc('X');
			$ptY = DBTool::getMySQLPointPropertyFunc('Y');
			
			// Create the function
			$sql = 'CREATE FUNCTION haversine (point1 POINT, point2 POINT)
	RETURNS FLOAT
	NO SQL DETERMINISTIC
	COMMENT "Returns the distance in degrees on the Earth between two known points of latitude and longitude."
BEGIN
	DECLARE R INTEGER DEFAULT 6371000;
	DECLARE lat1 FLOAT;
	DECLARE lat2 FLOAT;
	DECLARE latDelta FLOAT;
	DECLARE lonDelta FLOAT;
	DECLARE a FLOAT;
	DECLARE c FLOAT;
	DECLARE d FLOAT;
	
	SET lat1 = RADIANS(' . $ptY . '(point1));
	SET lat2 = RADIANS(' . $ptY . '(point2));
	SET latDelta = RADIANS(' . $ptY . '(point2) - ' . $ptY . '(point1));
	SET lonDelta = RADIANS(' . $ptX . '(point2) - ' . $ptX . '(point1));
	
	SET a = SIN(latDelta / 2) * SIN(latDelta / 2) + COS(lat1) * COS(lat2) * SIN(lonDelta / 2) * SIN(lonDelta / 2);
	SET c = 2 * ATAN2(SQRT(a), SQRT(1-a));
	SET d = R * c;
	
	RETURN FLOOR(d);
END;';
			
			\DB::statement($sql);
			
			return true;
			
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Get the MySQL Haversine formula as SQL string
	 *
	 * NOTE: Replace 6371 (the '$R' value) with 3958.756 if you want the answer in miles.
	 *
	 * @param $point1
	 * @param $point2
	 * @return string
	 */
	public static function haversineSql($point1, $point2)
	{
		// Point Property Functions
		$ptX = DBTool::getMySQLPointPropertyFunc('X');
		$ptY = DBTool::getMySQLPointPropertyFunc('Y');
		
		// Earth's Radius (6371 km OR 3958.756 mi)
		$R = 6371; // in Km
		if (isMilesUsingCountry(config('country.code'))) {
			$R = 3958.756; // in Miles
		}
		
		$lat1 = 'RADIANS(' . $ptY . '(' . $point1 . '))';
		$lat2 = 'RADIANS(' . $ptY . '(' . $point2 . '))';
		$latDelta = 'RADIANS(' . $ptY . '(' . $point2 . ') - ' . $ptY . '(' . $point1 . '))';
		$lonDelta = 'RADIANS(' . $ptX . '(' . $point2 . ') - ' . $ptX . '(' . $point1 . '))';
		
		$a = 'SIN(' . $latDelta . '/2) * SIN(' . $latDelta . '/2) + COS(' . $lat1 . ') * COS(' . $lat2 . ') * SIN(' . $lonDelta . '/2) * SIN(' . $lonDelta . '/2)';
		$c = '2 * ATAN2(SQRT(' . $a . '), SQRT(1-' . $a . '))';
		$formula = $R . ' * ' . $c;
		
		// Get the Distance calculation SQL query
		$sql = '(' . $formula . ') AS distance';
		
		return $sql;
	}
	
	/*
	========================================================================================================================
	Orthodromy Formula
	==================
	An orthodromic or great-circle route on the Earth's surface is the shortest possible real way between any two points.
	
	FORMULA
	=======
	Ortho(A, B) = R x acos[[cos(LatA) x cos(LatB) x cos(LongB-LongA)] + [sin(LatA) x sin(LatB)]]
	
	Where: R is the radius of the Earth (Radius = 6371 km (3959 mi))
	
	NOTE
	====
	The Geonames lat & lon data are in Decimal Degrees (wgs84)
	Decimal Degrees to Radians = RADIANS(DecimalDegrees) or DecimalDegrees * Pi/180
	
	SOURCES
	=======
	https://fr.wikipedia.org/wiki/Orthodromie
	http://www.lion1906.com/Pages/english/orthodromy_and_co.html
	========================================================================================================================
	*/
	/**
	 * Create the MySQL Orthodromy function
	 *
	 * This is a polyfill of the MySQL 'ST_Distance_Sphere' function using the Orthodromy formula.
	 * ---------------
	 * Unit Conversion
	 * ---------------
	 * The general rule for units is that the output length units are the same as the input length units.
	 *
	 * The OSM way geometry data has length units of degrees of latitude and longitude (SRID=4326).
	 * Therefore, the output units from 'ST_Distance' will also have length units of degrees, which are not really useful.
	 *
	 * There are several things you can do:
	 * - Use 'ST_Distance_Sphere' for fast/approximate distances in metres
	 * - Use 'ST_Distance_Spheroid' for accuracy distances in metres
	 * - Convert the lat/long geometry data types to geography, which automatically makes 'ST_Distance' and other functions to use linear units of metres
	 *
	 * More Info: https://stackoverflow.com/questions/13222061/unit-of-return-value-of-st-distance
	 * ---------------
	 *
	 * USAGE
	 * - Results in Km   : (orthodromy(POINT(lon1, lat1), POINT(lon2, lat2)) / 1000) AS distance
	 * - Results in Miles: ((orthodromy(POINT(lon1, lat1), POINT(lon2, lat2)) / 1000) * 0.62137119) AS distance
	 * Where
	 * - X / 1000        => Meters To Km
	 * - X * 0.621371192 => Km To Miles
	 *
	 * @return bool
	 */
	public static function createMySQLOrthodromyFunction()
	{
		try {
			
			// Drop the function, If exists
			$sql = 'DROP FUNCTION IF EXISTS orthodromy;';
			\DB::statement($sql);
			
			// Point Property Functions
			$ptX = DBTool::getMySQLPointPropertyFunc('X');
			$ptY = DBTool::getMySQLPointPropertyFunc('Y');
			
			// Create the function
			$sql = 'CREATE FUNCTION orthodromy (point1 POINT, point2 POINT)
	RETURNS FLOAT
	NO SQL DETERMINISTIC
	COMMENT "Returns the distance in degrees on the Earth between two known points of latitude and longitude."
BEGIN
	DECLARE R FLOAT UNSIGNED DEFAULT 6371000;
	DECLARE lat1 FLOAT;
	DECLARE lat2 FLOAT;
	DECLARE lonDelta FLOAT;
	DECLARE a FLOAT;
	DECLARE c FLOAT;
	DECLARE d FLOAT;
 
	SET lat1 = RADIANS(' . $ptY . '(point1));
	SET lat2 = RADIANS(' . $ptY . '(point2));
	SET lonDelta = RADIANS(' . $ptX . '(point2) - ' . $ptX . '(point1));
	
	SET c = ACOS((COS(lat1) * COS(lat2) * COS(lonDelta)) + (SIN(lat1) * SIN(lat2)));
	SET d = R * c;
 
	RETURN FLOOR(d);
END;';
			
			\DB::statement($sql);
			
			return true;
			
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Get the MySQL Orthodromy formula as SQL string
	 *
	 * NOTE: Replace 6371 (the '$R' value) with 3958.756 if you want the answer in miles.
	 *
	 * @param $point1
	 * @param $point2
	 * @return string
	 */
	public static function orthodromySql($point1, $point2)
	{
		// Point Property Functions
		$ptX = DBTool::getMySQLPointPropertyFunc('X');
		$ptY = DBTool::getMySQLPointPropertyFunc('Y');
		
		// Earth's Radius (6371 km OR 3958.756 mi)
		$R = 6371; // in Km
		if (isMilesUsingCountry(config('country.code'))) {
			$R = 3958.756; // in Miles
		}
		
		$lat1 = 'RADIANS(' . $ptY . '(' . $point1 . '))';
		$lat2 = 'RADIANS(' . $ptY . '(' . $point2 . '))';
		$lonDelta = 'RADIANS(' . $ptX . '(' . $point2 . ') - ' . $ptX . '(' . $point1 . '))';
		
		$c = 'ACOS((COS(' . $lat1 . ') * COS(' . $lat2 . ') * COS(' . $lonDelta . ')) + (SIN(' . $lat1 . ') * SIN(' . $lat2 . ')))';
		$formula = $R . ' * ' . $c;
		
		// Get the Distance calculation SQL query
		$sql = '(' . $formula . ') AS distance';
		
		return $sql;
	}
}
