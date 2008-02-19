<?php
	/* $Id$ */

	if (!extension_loaded('onphp')) {
		echo 'Trying to load onPHP extension.. ';
		
		if (!@dl('onphp.so')) {
			echo "failed.\n";
		} else {
			echo "done.\n";
		}
	}
	
	$config = dirname(__FILE__).'/config.inc.php';
	
	require is_readable($config) ? $config : $config.'.tpl';
	
	$reporter = php_sapi_name() == 'cli' ? new TextReporter() : new HtmlReporter();
	
	$test = new GroupTest('onPHP-'.ONPHP_VERSION);
	
	foreach ($testPathes as $testPath)
		foreach (glob($testPath.'*Test'.EXT_CLASS, GLOB_BRACE) as $file)
			$test->addTestFile($file);
	
	// meta, DB and DAOs ordered tests portion
	if (isset($dbs) && $dbs) {
		Singleton::getInstance('DBTestPool', $dbs)->connect();
		
		// build stuff from meta
		
		$metaDir = ONPHP_TEST_PATH.'meta'.DIRECTORY_SEPARATOR;
		$path = ONPHP_META_PATH.'bin'.DIRECTORY_SEPARATOR.'build.php';
		
		$_SERVER['argv'][0] = $path;
		
		$_SERVER['argv'][1] = $metaDir.'config.inc.php';
		
		$_SERVER['argv'][2] = $metaDir.'config.meta.xml';
		
		$_SERVER['argv'][] = '--force';
		$_SERVER['argv'][] = '--no-schema-check';
		$_SERVER['argv'][] = '--drop-stale-files';
		
		require $path;
		
		// provide paths to autogenerated stuff
		set_include_path(
			get_include_path().PATH_SEPARATOR
			.ONPHP_META_AUTO_BUSINESS_DIR.PATH_SEPARATOR
			.ONPHP_META_AUTO_DAO_DIR.PATH_SEPARATOR
			.ONPHP_META_AUTO_PROTO_DIR.PATH_SEPARATOR
			
			.ONPHP_META_DAO_DIR.PATH_SEPARATOR
			.ONPHP_META_BUSINESS_DIR.PATH_SEPARATOR
			.ONPHP_META_PROTO_DIR
		);
		
		// provide fake spooked class
		class Spook extends IdentifiableObject {/*_*/}
		
		$daoTest = new DAOTest();
		
		$test->addTestClass($daoTest);
		
		$out = MetaConfiguration::me()->getOutput();
		
		foreach (DBTestPool::me()->getPool() as $connector => $db) {
			DBPool::me()->setDefault($db);
			
			$out->
				info('Using ')->
				info(get_class($db), true)->
				infoLine(' connector.');
			
			try {
				$daoTest->drop();
			} catch (DatabaseException $e) {
				// previous shutdown was clean
			}
			
			$daoTest->create()->fill(false);
			
			MetaConfiguration::me()->checkIntegrity();
			$out->newLine();
			
			$daoTest->drop();
		}
		
		DBPool::me()->dropDefault();
	}
	
	if ($daoWorkers)
		foreach ($daoWorkers as $worker) {
			echo "Processing with {$worker}\n";
			Cache::dropWorkers();
			Cache::setDefaultWorker($worker);
			$test->run($reporter);
			echo "\n";
		}
	else
		$test->run($reporter);
?>