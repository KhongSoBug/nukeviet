<?php

/**
 * @Project NUKEVIET 3.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2012 VINADES.,JSC. All rights reserved
 * @Createdate 2-1-2010 22:42
 */

define( 'NV_ADMIN', true );
require_once 'mainfile.php';

$file_config_temp = NV_TEMP_DIR . '/config_' . md5( $global_config['sitekey'] ) . '.php';

$dirs = nv_scandir( NV_ROOTDIR . '/language', '/^([a-z]{2})/' );

$languageslist = array();

foreach( $dirs as $file )
{
	if( is_file( NV_ROOTDIR . '/language/' . $file . '/install.php' ) )
	{
		$languageslist[] = $file;
	}
}

require_once NV_ROOTDIR . '/modules/users/language/' . NV_LANG_DATA . '.php';
require_once NV_ROOTDIR . '/language/' . NV_LANG_DATA . '/global.php';
require_once NV_ROOTDIR . '/language/' . NV_LANG_DATA . '/install.php';
require_once NV_ROOTDIR . '/install/template.php';
require_once NV_ROOTDIR . '/includes/core/admin_functions.php';

if( is_file( NV_ROOTDIR . '/' . $file_config_temp ) )
{
	require_once NV_ROOTDIR . '/' . $file_config_temp;
	//Bat dau phien lam viec cua MySQL
	require_once NV_ROOTDIR . '/includes/class/db.class.php';
}

$contents = '';
$step = $nv_Request->get_int( 'step', 'post,get', 1 );

$maxstep = $nv_Request->get_int( 'maxstep', 'session', 1 );

if( $step <= 0 or $step > 7 )
{
	Header( 'Location: ' . NV_BASE_SITEURL . 'install/index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&step=1' );
	exit();
}

if( $step > $maxstep and $step > 2 )
{
	$step = $maxstep;
	Header( 'Location: ' . NV_BASE_SITEURL . 'install/index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&step=' . $step );
	exit();
}

if( file_exists( NV_ROOTDIR . '/' . NV_CONFIG_FILENAME ) and $step < 7 )
{
	Header( 'Location: ' . NV_BASE_SITEURL . 'index.php' );
	exit();
}
if( empty( $sys_info['supports_rewrite'] ) )
{
	if( isset( $_COOKIE['supports_rewrite'] ) and $_COOKIE['supports_rewrite'] == md5( $global_config['sitekey'] ) )
	{
		$sys_info['supports_rewrite'] = 'rewrite_mode_apache';
	}
}
if( $step == 1 )
{
	if( $step < 2 )
	{
		$nv_Request->set_Session( 'maxstep', 2 );
	}

	$title = $lang_module['select_language'];

	$contents = nv_step_1();
}
elseif( $step == 2 )
{
	// Tu dong nhan dang Remove Path
	if( $nv_Request->isset_request( 'tetectftp', 'post' ) )
	{
		$ftp_server = nv_unhtmlspecialchars( $nv_Request->get_title( 'ftp_server', 'post', '', 1 ) );
		$ftp_port = intval( $nv_Request->get_title( 'ftp_port', 'post', '21', 1 ) );
		$ftp_user_name = nv_unhtmlspecialchars( $nv_Request->get_title( 'ftp_user_name', 'post', '', 1 ) );
		$ftp_user_pass = nv_unhtmlspecialchars( $nv_Request->get_title( 'ftp_user_pass', 'post', '', 1 ) );

		if( ! $ftp_server or ! $ftp_user_name or ! $ftp_user_pass )
		{
			die( 'ERROR|' . $lang_module['ftp_error_empty'] );
		}

		if( ! defined( 'NV_FTP_CLASS' ) ) require NV_ROOTDIR . '/includes/class/ftp.class.php';
		if( ! defined( 'NV_BUFFER_CLASS' ) ) require NV_ROOTDIR . '/includes/class/buffer.class.php';

		$ftp = new NVftp( $ftp_server, $ftp_user_name, $ftp_user_pass, array( 'timeout' => 10 ), $ftp_port );

		if( ! empty( $ftp->error ) )
		{
			$ftp->close();
			die( 'ERROR|' . ( string )$ftp->error );
		}
		else
		{
			$list_valid = array( NV_CACHEDIR, NV_DATADIR, 'images', 'includes', 'js', 'language', NV_LOGS_DIR, 'modules', NV_SESSION_SAVE_PATH, 'themes', NV_TEMP_DIR, NV_UPLOADS_DIR );

			$ftp_root = $ftp->detectFtpRoot( $list_valid, NV_ROOTDIR );

			if( $ftp_root === false )
			{
				$ftp->close();
				die( 'ERROR|' . ( empty( $ftp->error ) ? $lang_module['ftp_error_detect_root'] : ( string )$ftp->error ) );
			}

			$ftp->close();
			die( 'OK|' . $ftp_root );
		}

		$ftp->close();
		die( 'ERROR|' . $lang_module['ftp_error_detect_root'] );
	}

	// Danh sach cac file can kiem tra quyen ghi
	$array_dir = array( NV_SESSION_SAVE_PATH, NV_LOGS_DIR, NV_LOGS_DIR . '/data_logs', NV_LOGS_DIR . '/dump_backup', NV_LOGS_DIR . '/error_logs', NV_LOGS_DIR . '/error_logs/errors256', NV_LOGS_DIR . '/error_logs/old', NV_LOGS_DIR . '/error_logs/tmp', NV_LOGS_DIR . '/ip_logs', NV_LOGS_DIR . '/ref_logs', NV_LOGS_DIR . '/voting_logs', NV_CACHEDIR, NV_UPLOADS_DIR, NV_TEMP_DIR, NV_FILES_DIR, NV_FILES_DIR . '/css', NV_DATADIR, NV_DATADIR . '/ip_files' );

	// Them vao cac file trong thu muc data va file cau hinh tam
	$array_file_data = nv_scandir( NV_ROOTDIR . '/' . NV_DATADIR, '/^([a-zA-Z0-9\-\_\.]+)\.([a-z0-9]{2,6})$/' );
	foreach( $array_file_data as $file_i )
	{
		$array_dir[] = NV_DATADIR . '/' . $file_i;
	}
	$array_dir[] = $file_config_temp;

	// Them vao file .htaccess va web.config
	if( ! empty( $sys_info['supports_rewrite'] ) )
	{
		if( $sys_info['supports_rewrite'] == 'rewrite_mode_apache' )
		{
			$array_dir[] = '.htaccess';
		}
		else
		{
			$array_dir[] = 'web.config';
		}
	}

	// Cau hinh FTP
	$ftp_check_login = 0;
	$global_config['ftp_server'] = $nv_Request->get_string( 'ftp_server', 'post', 'localhost' );
	$global_config['ftp_port'] = $nv_Request->get_int( 'ftp_port', 'post', 21 );
	$global_config['ftp_user_name'] = $nv_Request->get_string( 'ftp_user_name', 'post', '' );
	$global_config['ftp_user_pass'] = $nv_Request->get_string( 'ftp_user_pass', 'post', '' );
	$global_config['ftp_path'] = $nv_Request->get_string( 'ftp_path', 'post', '/' );

	$array_ftp_data = array(
		'ftp_server' => $global_config['ftp_server'],
		'ftp_port' => $global_config['ftp_port'],
		'ftp_user_name' => $global_config['ftp_user_name'],
		'ftp_user_pass' => $global_config['ftp_user_pass'],
		'ftp_path' => $global_config['ftp_path'],
		'error' => ''
	);

	// CHMOD bang FTP
	$modftp = $nv_Request->get_int( 'modftp', 'post', 0 );
	if( $modftp )
	{
		if( ! empty( $global_config['ftp_server'] ) and ! empty( $global_config['ftp_user_name'] ) and ! empty( $global_config['ftp_user_pass'] ) )
		{
			// set up basic connection
			$conn_id = ftp_connect( $global_config['ftp_server'], $global_config['ftp_port'], 10 );

			// login with username and password
			$login_result = ftp_login( $conn_id, $global_config['ftp_user_name'], $global_config['ftp_user_pass'] );
			if( ( ! $conn_id ) || ( ! $login_result ) )
			{
				$ftp_check_login = 3;
				$array_ftp_data['error'] = $lang_module['ftp_error_account'];
			}
			elseif( ftp_chdir( $conn_id, $global_config['ftp_path'] ) )
			{
				$check_files = array( NV_CACHEDIR, NV_DATADIR, 'images', 'includes', 'index.php', 'robots.txt', 'js', 'language', NV_LOGS_DIR, 'mainfile.php', 'modules', NV_SESSION_SAVE_PATH, 'themes', NV_TEMP_DIR );

				$list_files = ftp_nlist( $conn_id, '.' );

				$a = 0;
				foreach( $list_files as $filename )
				{
					$filename = basename( $filename );
					if( in_array( $filename, $check_files ) )
					{
						++$a;
					}
				}

				if( $a == sizeof( $check_files ) )
				{
					$ftp_check_login = 1;
					nv_chmod_dir( $conn_id, NV_DATADIR, true );
					nv_chmod_dir( $conn_id, NV_TEMP_DIR, true );
					nv_save_file_config();
					nv_chmod_dir( $conn_id, NV_TEMP_DIR, true );
				}
				else
				{
					$ftp_check_login = 2;
					$array_ftp_data['error'] = $lang_module['ftp_error_path'];
				}
			}
			else
			{
				$ftp_check_login = 2;
				$array_ftp_data['error'] = $lang_module['ftp_error_path'];
			}
			$global_config['ftp_check_login'] = $ftp_check_login;
		}
	}

	// Kiem tra quyen ghi doi voi nhung file tren
	$nextstep = 1;
	$array_dir_check = array();
	foreach( $array_dir as $dir )
	{
		if( $ftp_check_login == 1 )
		{
			if( ! is_dir( NV_ROOTDIR . '/' . $dir ) and $dir != $file_config_temp )
			{
				ftp_mkdir( $conn_id, $dir );
			}

			if( ! is_writable( NV_ROOTDIR . '/' . $dir ) )
			{
				if( substr( $sys_info['os'], 0, 3 ) != 'WIN' ) ftp_chmod( $conn_id, 0777, $dir );
			}
		}

		if( $dir == $file_config_temp and ! file_exists( NV_ROOTDIR . '/' . $file_config_temp ) and is_writable( NV_ROOTDIR . '/' . NV_TEMP_DIR ) )
		{
			file_put_contents( NV_ROOTDIR . '/' . $file_config_temp, '', LOCK_EX );
		}

		if( is_file( NV_ROOTDIR . '/' . $dir ) )
		{
			if( is_writable( NV_ROOTDIR . '/' . $dir ) )
			{
				$array_dir_check[$dir] = $lang_module['dir_writable'];
			}
			else
			{
				$array_dir_check[$dir] = $lang_module['dir_not_writable'];
				$nextstep = 0;
			}
		}
		elseif( is_dir( NV_ROOTDIR . '/' . $dir ) )
		{
			if( is_writable( NV_ROOTDIR . '/' . $dir ) )
			{
				$array_dir_check[$dir] = $lang_module['dir_writable'];
			}
			else
			{
				$array_dir_check[$dir] = $lang_module['dir_not_writable'];
				$nextstep = 0;
			}
		}
		else
		{
			$array_dir_check[$dir] = $lang_module['dir_noexit'];
			$nextstep = 0;
		}
	}

	if( ! nv_save_file_config( $db_config, $global_config ) and $ftp_check_login == 1 )
	{
		ftp_chmod( $conn_id, 0777, $file_config_temp );
	}

	if( $ftp_check_login > 0 )
	{
		ftp_close( $conn_id );
	}

	if( $nextstep )
	{
		$i = 0;
		for( $ip_file = 0; $ip_file <= 255; $ip_file++ )
		{
			if( file_put_contents( NV_ROOTDIR . '/' . NV_DATADIR . '/ip_files/' . $ip_file . '.php', "<?php\n\n\$ranges = array();\n\n?>", LOCK_EX ) )
			{
				++$i;
			}
		}

		if( $i != 256 )
		{
			$nextstep = 0;
			$array_dir_check[NV_DATADIR . '/ip_files'] = sprintf( $lang_module['dir_not_writable_ip_files'], NV_DATADIR . '/ip_files' );
		}
	}

	if( $step < 3 and $nextstep == 1 )
	{
		$nv_Request->set_Session( 'maxstep', 3 );
	}

	$title = $lang_module['check_chmod'];
	$contents = nv_step_2( $array_dir_check, $array_ftp_data, $nextstep );
}
elseif( $step == 3 )
{
	if( $step < 4 )
	{
		$nv_Request->set_Session( 'maxstep', 4 );
	}
	$title = $lang_module['license'];

	if( file_exists( NV_ROOTDIR . '/install/licenses_' . NV_LANG_DATA . '.html' ) )
	{
		$license = file_get_contents( NV_ROOTDIR . '/install/licenses_' . NV_LANG_DATA . '.html' );
	}
	else
	{
		$license = file_get_contents( NV_ROOTDIR . '/install/licenses.html' );
	}

	$contents = nv_step_3( $license );
}
elseif( $step == 4 )
{
	$nextstep = 1;
	$title = $lang_module['check_server'];

	$array_resquest = array();
	$array_resquest_key = array( 'php_support', 'mysql_support', 'opendir_support', 'gd_support', 'mcrypt_support', 'session_support', 'fileuploads_support' );

	foreach( $array_resquest_key as $key )
	{
		$array_resquest[$key] = ( $sys_info[$key] ) ? $lang_module['compatible'] : $lang_module['not_compatible'];

		if( ! $sys_info[$key] )
		{
			$nextstep = 0;
		}
	}

	if( $step < 5 and $nextstep == 1 )
	{
		$nv_Request->set_Session( 'maxstep', 5 );
	}

	$array_suport = array();
	$array_support['supports_rewrite'] = ( empty( $sys_info['supports_rewrite'] ) ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['safe_mode'] = ( $sys_info['safe_mode'] ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['register_globals'] = ( ini_get( 'register_globals' ) == '1' || strtolower( ini_get( 'register_globals' ) ) == 'on' ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['magic_quotes_runtime'] = ( ini_get( 'magic_quotes_runtime' ) == '1' || strtolower( ini_get( 'magic_quotes_runtime' ) ) == 'on' ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['magic_quotes_gpc'] = ( ini_get( 'magic_quotes_gpc' ) == '1' || strtolower( ini_get( 'magic_quotes_gpc' ) ) == 'on' ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['magic_quotes_sybase'] = ( ini_get( 'magic_quotes_sybase' ) == '1' || strtolower( ini_get( 'magic_quotes_sybase' ) ) == 'on' ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['output_buffering'] = ( ini_get( 'output_buffering' ) == '1' || strtolower( ini_get( 'output_buffering' ) ) == 'on' ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['session_auto_start'] = ( ini_get( 'session.auto_start' ) == '1' || strtolower( ini_get( 'session.auto_start' ) ) == 'on' ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['display_errors'] = ( ini_get( 'display_errors' ) == '1' || strtolower( ini_get( 'display_errors' ) ) == 'on' ) ? $lang_module['not_compatible'] : $lang_module['compatible'];
	$array_support['allowed_set_time_limit'] = ( $sys_info['allowed_set_time_limit'] ) ? $lang_module['compatible'] : $lang_module['not_compatible'];
	$array_support['zlib_support'] = ( $sys_info['zlib_support'] ) ? $lang_module['compatible'] : $lang_module['not_compatible'];
	$array_support['zip_support'] = ( extension_loaded( 'zip' ) ) ? $lang_module['compatible'] : $lang_module['not_compatible'];

	$contents = nv_step_4( $array_resquest, $array_support, $nextstep );
}
elseif( $step == 5 )
{
	$nextstep = 0;
	$db_config['dbport'] = '';
	$db_config['error'] = '';
	$db_config['dbhost'] = $nv_Request->get_string( 'dbhost', 'post', $db_config['dbhost'] );
	$db_config['dbname'] = $nv_Request->get_string( 'dbname', 'post', $db_config['dbname'] );
	$db_config['dbuname'] = $nv_Request->get_string( 'dbuname', 'post', $db_config['dbuname'] );
	$db_config['dbpass'] = $nv_Request->get_string( 'dbpass', 'post', $db_config['dbpass'] );
	$db_config['prefix'] = $nv_Request->get_string( 'prefix', 'post', 'nv3' );
	$db_config['db_detete'] = $nv_Request->get_int( 'db_detete', 'post', '0' );
	$db_config['num_table'] = 0;
	$db_config['create_db'] = 1;

	if( ! empty( $db_config['dbhost'] ) and ! empty( $db_config['dbname'] ) and ! empty( $db_config['dbuname'] ) and ! empty( $db_config['prefix'] ) )
	{
		$db_config['dbuname'] = preg_replace( array( '/[^a-z0-9]/', '/[\_]+/', '/^[\_]+/', '/[\_]+$/' ), array( '_', '_', '', '' ), strtolower( $db_config['dbuname'] ) );

		$db_config['dbname'] = preg_replace( array( '/[^a-z0-9]/', '/[\_]+/', '/^[\_]+/', '/[\_]+$/' ), array( '_', '_', '', '' ), strtolower( $db_config['dbname'] ) );

		$db_config['prefix'] = preg_replace( array( '/[^a-z0-9]/', '/[\_]+/', '/^[\_]+/', '/[\_]+$/' ), array( '_', '_', '', '' ), strtolower( $db_config['prefix'] ) );

		if( substr( $sys_info['os'], 0, 3 ) == 'WIN' and $db_config['dbhost'] == 'localhost' )
		{
			$db_config['dbhost'] = '127.0.0.1';
		}

		$db = new sql_db( $db_config );

		if( empty( $db->connect ) )
		{
			$db_config['error'] = 'Could not connect to data server';
		}
		else
		{
			$db_config['dbsystem'] = $db_config['dbname'];
			$tables = array();
			$db->exec( 'ALTER DATABASE `' . $db_config['dbname'] . '` DEFAULT CHARACTER SET `utf8` COLLATE `'.$db_config['collation'].'`' );
			$result = $db->query( "SHOW TABLE STATUS LIKE '" . $db_config['prefix'] . "\_%'" );
			$num_table = intval( $result->rowCount() );

			if( $num_table > 0 )
			{
				if( $db_config['db_detete'] == 1 )
				{
					while( $item = $result->fetch() )
					{
						$db->exec( 'DROP TABLE `' . $item['name'] . '`' );
					}
					$num_table = 0;
				}
				else
				{
					$db_config['error'] = $lang_module['db_err_prefix'];
				}
			}

			$db_config['num_table'] = $num_table;

			if( $num_table == 0 )
			{
				nv_save_file_config();
				$db_config['error'] = '';
				$sql_create_table = array();

				//cai dat du lieu cho he thong
				require_once NV_ROOTDIR . '/install/data.php';

				foreach( $sql_create_table as $query )
				{
					try
					{
						$db->exec( $query );
					}
					catch (PDOException $e)
					{
						$nv_Request->set_Session( 'maxstep', 1 );
						//die( $query );
						$db_config['error'] = $e->getMessage();
						break;
					}
				}

				//Cai dat du lieu cho cac module
				if( empty( $db_config['error'] ) )
				{
					define( 'NV_IS_MODADMIN', true );

					$module_name = 'modules';
					$lang_module['modules'] = '';
					$lang_module['vmodule_add'] = '';
					$lang_module['autoinstall'] = '';
					$lang_global['mod_modules'] = '';

					define( 'NV_UPLOAD_GLOBALTABLE', $db_config['prefix'] . '_upload' );
					require_once NV_ROOTDIR . '/' . NV_ADMINDIR . '/modules/functions.php';

					$module_name = '';

					require_once NV_ROOTDIR . '/includes/sqldata.php';

					$modules_exit = nv_scandir( NV_ROOTDIR . '/modules', $global_config['check_module'] );

					//cai dat du lieu cho ngon ngu
					$sql_create_table = nv_create_table_sys( NV_LANG_DATA );

					foreach( $sql_create_table as $query )
					{
						try
						{
							$db->exec( $query );
						}
						catch (PDOException $e)
						{
							$nv_Request->set_Session( 'maxstep', 1 );
							$db_config['error'] = $e->getMessage();
							//die( $query );
							break;
						}
					}

					$sql = 'SELECT * FROM `' . $db_config['prefix'] . '_' . NV_LANG_DATA . '_modules` ORDER BY `weight` ASC';
					$result = $db->query( $sql );
					while( $row = $result->fetch() )
					{
						$setmodule = $row['title'];

						if( in_array( $row['module_file'], $modules_exit ) )
						{
							$sm = nv_setup_data_module( NV_LANG_DATA, $setmodule );

							if( $sm != 'OK_' . $setmodule )
							{
								die( 'error set module: ' . $setmodule );
							}
						}
						else
						{
							$db->exec( 'DELETE FROM `' . $db_config['prefix'] . '_' . NV_LANG_DATA . '_modules` WHERE `title`=' . $db->quot( $setmodule ) );
						}
					}

					//cai dat du lieu mau
					$filesavedata = NV_LANG_DATA;
					$lang_data = NV_LANG_DATA;

					if( ! file_exists( NV_ROOTDIR . '/install/data_' . $lang_data . '.php' ) )
					{
						$filesavedata = 'en';
					}

					include_once NV_ROOTDIR . '/install/data_' . $filesavedata . '.php' ;

					foreach( $sql_create_table as $query )
					{
						try
						{
							$db->exec( $query );
						}
						catch (PDOException $e)
						{
							$nv_Request->set_Session( 'maxstep', 1 );
							//die( $query );
							$db_config['error'] = $e->getMessage();
							break;
						}
					}

					//xoa du lieu tai bang nv3_vi_modules
					$db->exec( "DELETE FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modules` WHERE `module_file` NOT IN ('" . implode( "', '", $modules_exit ) . "')" );

					//xoa du lieu tai bang nv3_setup_modules
					$db->exec( "DELETE FROM `" . $db_config['prefix'] . "_setup_modules` WHERE `module_file` NOT IN ('" . implode( "', '", $modules_exit ) . "')" );

					//xoa du lieu tai bang nv3_vi_blocks
					$db->exec( "DELETE FROM `" . $db_config['prefix'] . "_" . $lang_data . "_blocks_weight` WHERE `bid` in (SELECT `bid` FROM `" . $db_config['prefix'] . "_" . $lang_data . "_blocks_groups` WHERE `module` NOT IN (SELECT `title` FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modules`))" );

					//xoa du lieu tai bang nv3_vi_blocks_groups
					$db->exec( "DELETE FROM `" . $db_config['prefix'] . "_" . $lang_data . "_blocks_groups` WHERE `module` NOT IN (SELECT `title` FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modules`)" );

					//xoa du lieu tai bang nv3_vi_modthemes
					$db->exec( "DELETE FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modthemes` WHERE `func_id` in (SELECT `func_id` FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modfuncs` WHERE `in_module` NOT IN (SELECT `title` FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modules`))" );

					//xoa du lieu tai bang nv3_vi_modfuncs
					$db->exec( "DELETE FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modfuncs` WHERE `in_module` NOT IN (SELECT `title` FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modules`)" );

					//xoa du lieu tai bang nv3_config
					$db->exec( "DELETE FROM `" . $db_config['prefix'] . "_config` WHERE `lang`=" . $db->dbescape( $lang_data ) . " AND `module`!='global' AND `module` NOT IN (SELECT `title` FROM `" . $db_config['prefix'] . "_" . $lang_data . "_modules`)" );

					$sql = "SELECT * FROM `" . $db_config['prefix'] . "_" . NV_LANG_DATA . "_modules` WHERE `title`='news'";
					if( $db->query( $sql )->rowCount() )
					{
						$result = $db->query( "SELECT catid FROM `" . $db_config['prefix'] . "_" . $lang_data . "_news_cat` ORDER BY `order` ASC" );
						while( list( $catid_i ) = $result->fetch( 3 ) )
						{
							nv_create_table_news( $catid_i );
						}

						$result = $db->query( "SELECT id, listcatid FROM `" . $db_config['prefix'] . "_" . $lang_data . "_news_rows` ORDER BY `id` ASC" );
						while( list( $id, $listcatid ) = $result->fetch( 3 ) )
						{
							$arr_catid = explode( ',', $listcatid );
							foreach( $arr_catid as $catid )
							{
								$db->exec( "INSERT INTO `" . $db_config['prefix'] . "_" . $lang_data . "_news_" . $catid . "` SELECT * FROM `" . $db_config['prefix'] . "_" . $lang_data . "_news_rows` WHERE `id`=" . $id );
							}
						}
						$result->closeCursor();
					}

					++$step;
					$nv_Request->set_Session( 'maxstep', $step );

					Header( 'Location: ' . NV_BASE_SITEURL . 'install/index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&step=' . $step );
					exit();
				}
			}
		}
	}

	$title = $lang_module['config_database'];
	$contents = nv_step_5( $db_config, $nextstep );
}
elseif( $step == 6 )
{
	$nextstep = 0;
	$array_data = array();
	$array_data['lang_multi'] = (int) $nv_Request->get_bool( 'lang_multi', 'post');

	$error = $site_name = $login = $email = $password = $re_password = '';

	if( $nv_Request->isset_request( 'nv_login,nv_password', 'post' ) )
	{
		$db_config['dbsystem'] = $db_config['dbname'];
		define( 'NV_USERS_GLOBALTABLE', $db_config['prefix'] . '_users' );

		$db = new sql_db( $db_config );
		if( ! empty( $db->error ) )
		{
			$error = ( ! empty( $db->error['user_message'] ) ) ? $db->error['user_message'] : $db->error['message'];
		}

		$site_name = $nv_Request->get_title( 'site_name', 'post', '', 1 );
		$login = nv_substr( $nv_Request->get_title( 'nv_login', 'post', '', 1 ), 0, NV_UNICKMAX );
		$email = $nv_Request->get_title( 'nv_email', 'post', '' );
		$password = $nv_Request->get_title( 'nv_password', 'post', '' );
		$re_password = $nv_Request->get_title( 're_password', 'post', '' );
		$check_login = nv_check_valid_login( $login, NV_UNICKMAX, NV_UNICKMIN );
		$check_pass = nv_check_valid_pass( $password, NV_UPASSMAX, NV_UPASSMIN );
		$check_email = nv_check_valid_email( $email );

		$question = $nv_Request->get_title( 'question', 'post', '', 1 );
		$answer_question = $nv_Request->get_title( 'answer_question', 'post', '', 1 );

		$array_data['site_name'] = $site_name;
		$array_data['nv_login'] = $login;
		$array_data['nv_email'] = $email;
		$array_data['nv_password'] = $password;
		$array_data['re_password'] = $re_password;
		$array_data['nv_login'] = $login;
		$array_data['question'] = $question;
		$array_data['answer_question'] = $answer_question;

		$global_config['site_email'] = $email;

		if( empty( $site_name ) )
		{
			$error = $lang_module['err_sitename'];
		}
		elseif( ! empty( $check_login ) )
		{
			$error = $check_login;
		}
		elseif( $login != $db->fixdb( $login ) )
		{
			$error = sprintf( $lang_module['account_deny_name'], '<strong>' . $login . '</strong>' );
		}
		elseif( ! empty( $check_email ) )
		{
			$error = $check_email;
		}
		elseif( ! empty( $check_pass ) )
		{
			$error = $check_pass;
		}
		elseif( $password != $re_password )
		{
			$error = sprintf( $lang_global['passwordsincorrect'], $password, $re_password );
		}
		elseif( empty( $question ) )
		{
			$error = $lang_module['your_question_empty'];
		}
		elseif( empty( $answer_question ) )
		{
			$error = $lang_module['answer_empty'];
		}
		elseif( empty( $error ))
		{
			$password = $crypt->hash( $password );
			define( 'NV_CONFIG_GLOBALTABLE', $db_config['prefix'] . '_config' );

			$db->exec( "TRUNCATE TABLE `" . $db_config['prefix'] . "_users`" );
			$sth = $db->prepare( "INSERT INTO `" . $db_config['prefix'] . "_users`
				(`username`, `md5username`, `password`, `email`, `full_name`, `gender`, `photo`, `birthday`, `sig`,	`regdate`, `question`, `answer`, `passlostkey`, `view_mail`, `remember`, `in_groups`, `active`, `checknum`, `last_login`, `last_ip`, `last_agent`, `last_openid`, `idsite`)
				VALUES( :username, :md5username, :password, :email, :full_name, '', '', 0, NULL, " . NV_CURRENTTIME . ", :question, :answer_question, '', 0, 1, '', 1, '', " . NV_CURRENTTIME . ", '', '', '', 0)" );
			$sth->bindParam( ':username', $login, PDO::PARAM_STR );
			$sth->bindValue( ':md5username', nv_md5safe( $login ), PDO::PARAM_STR );
			$sth->bindParam( ':password', $password, PDO::PARAM_STR );
			$sth->bindParam( ':email', $email, PDO::PARAM_STR );
			$sth->bindParam( ':full_name', $login, PDO::PARAM_STR );
			$sth->bindParam( ':question', $question, PDO::PARAM_STR );
			$sth->bindParam( ':answer_question', $answer_question, PDO::PARAM_STR );
			$sth->execute();

			$userid = $db->lastInsertId();

			$db->exec( "TRUNCATE TABLE `" . $db_config['prefix'] . "_authors`" );
			$sql = "INSERT INTO `" . $db_config['prefix'] . "_authors` (`admin_id`, `editor`, `lev`, `files_level`, `position`, `addtime`, `edittime`, `is_suspend`, `susp_reason`, `check_num`, `last_login`, `last_ip`, `last_agent`) VALUES(" . $userid . ", 'ckeditor', 1, 'adobe,application,archives,audio,documents,flash,images,real,video|1|1|1', 'Administrator', 0, 0, 0, '', '', 0, '', '')";

			if( $userid > 0 and $db->exec( $sql ) )
			{
				$db->exec( "INSERT INTO `" . $db_config['prefix'] . "_users_info` (`userid`) VALUES (" . $userid . ")" );
				$db->exec( "INSERT INTO `" . $db_config['prefix'] . "_groups_users` (`group_id`, `userid`, `data`) VALUES(1, " . $userid . ", '0')" );

				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'site', 'site_email', " . $db->quote( $global_config['site_email'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'site', 'error_send_email', " . $db->quote( $global_config['site_email'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'my_domains', " . $db->quote( NV_SERVER_NAME ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'cookie_prefix', " . $db->quote( $global_config['cookie_prefix'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'session_prefix', " . $db->quote( $global_config['session_prefix'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'site_timezone', " . $db->quote( $global_config['site_timezone'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'site', 'statistics_timezone', " . $db->quote( NV_SITE_TIMEZONE_NAME ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'proxy_blocker', " . $db->quote( $global_config['proxy_blocker'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'str_referer_blocker', " . $db->quote( $global_config['str_referer_blocker'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'lang_multi', " . $db->quote( $global_config['lang_multi'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'lang_geo', " . $db->quote( $global_config['lang_geo'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'site_lang', " . $db->quote( $global_config['site_lang'] ) . ")" );

				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'ftp_server', " . $db->quote( $global_config['ftp_server'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'ftp_port', " . $db->quote( $global_config['ftp_port'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'ftp_user_name', " . $db->quote( $global_config['ftp_user_name'] ) . ")" );

				$ftp_user_pass = nv_base64_encode( $crypt->aes_encrypt( $global_config['ftp_user_pass'] ) );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'ftp_user_pass', " . $db->quote( $ftp_user_pass ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'ftp_path', " . $db->quote( $global_config['ftp_path'] ) . ")" );
				$db->exec( "INSERT INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES ('sys', 'global', 'ftp_check_login', " . $db->quote( $global_config['ftp_check_login'] ) . ")" );
				$db->exec( "UPDATE `" . NV_CONFIG_GLOBALTABLE . "` SET `config_value` = " . $db->dbescape_string( $site_name ) . " WHERE `module` = 'global' AND `config_name` = 'site_name'" );

				$result = $db->query( "SELECT * FROM `" . $db_config['prefix'] . "_authors_module` ORDER BY `weight` ASC" );
				while( $row = $result->fetch() )
				{
					$checksum = md5( $row['module'] . "#" . $row['act_1'] . "#" . $row['act_2'] . "#" . $row['act_3'] . "#" . $global_config['sitekey'] );
					$db->exec( "UPDATE `" . $db_config['prefix'] . "_authors_module` SET `checksum` = '" . $checksum . "' WHERE `mid` = " . $row['mid'] );
				}

				if( ! ( nv_function_exists( 'finfo_open' ) or nv_class_exists( "finfo" ) or nv_function_exists( 'mime_content_type' ) or ( substr( $sys_info['os'], 0, 3 ) != 'WIN' and ( nv_function_exists( 'system' ) or nv_function_exists( 'exec' ) ) ) ) )
				{
					$db->exec( "UPDATE `" . NV_CONFIG_GLOBALTABLE . "` SET `config_value` = 'mild' WHERE `lang`='sys' AND `module` = 'global' AND `config_name` = 'upload_checking_mode'" );
				}
				if( empty( $array_data['lang_multi'] ) )
				{
					$global_config['rewrite_optional'] = 1;
					$global_config['lang_multi'] = 0;
					$db->exec( "UPDATE `" . NV_CONFIG_GLOBALTABLE . "` SET `config_value` = '0' WHERE `lang`='sys' AND `module` = 'global' AND `config_name` = 'lang_multi'" );
					$db->exec( "UPDATE `" . NV_CONFIG_GLOBALTABLE . "` SET `config_value` = '1' WHERE `lang`='sys' AND `module` = 'global' AND `config_name` = 'rewrite_optional'" );
					$result = $db->query( "SELECT * FROM `" . $db_config['prefix'] . "_" . NV_LANG_DATA . "_modules` where `title`='news'" );
					if( $result->rowCount() )
					{
						$global_config['rewrite_op_mod'] = 'news';
						$db->exec( "UPDATE `" . NV_CONFIG_GLOBALTABLE . "` SET `config_value` = 'news' WHERE `lang`='sys' AND `module` = 'global' AND `config_name` = 'rewrite_op_mod'" );
					}
				}
				nv_save_file_config();

				$array_config_rewrite = array(
					'rewrite_optional' => $global_config['rewrite_optional'],
					'rewrite_endurl' => $global_config['rewrite_endurl'],
					'rewrite_exturl' => $global_config['rewrite_exturl']
				);
				$rewrite = nv_rewrite_change( $array_config_rewrite );
				if( empty( $rewrite[0] ) )
				{
					$error .= sprintf( $lang_module['file_not_writable'], $rewrite[1] );
				}
				elseif( nv_save_file_config_global() )
				{
					++$step;
					$nv_Request->set_Session( 'maxstep', $step );

					nv_save_file_config();

					@rename( NV_ROOTDIR . '/' . $file_config_temp, NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_CONFIG_FILENAME );

					if( is_writable( NV_ROOTDIR . '/robots.txt' ) )
					{
						$contents = file_get_contents( NV_ROOTDIR . '/robots.txt' );

						$check_rewrite_file = nv_check_rewrite_file();

						if( $check_rewrite_file )
						{
							$content_sitemap = 'Sitemap: ' . NV_MY_DOMAIN . NV_BASE_SITEURL . 'Sitemap.xml';
						}
						else
						{
							$content_sitemap = 'Sitemap: ' . NV_MY_DOMAIN . NV_BASE_SITEURL . 'index.php/SitemapIndex' . $global_config['rewrite_endurl'];
						}

						$contents = str_replace( 'Sitemap: http://yousite.com/?nv=SitemapIndex', $content_sitemap, $contents );

						file_put_contents( NV_ROOTDIR . '/robots.txt', $contents, LOCK_EX );
					}

					Header( 'Location: ' . NV_BASE_SITEURL . 'install/index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&step=' . $step );
					exit();
				}
				else
				{
					$error = sprintf( $lang_module['file_not_writable'], NV_DATADIR . '/config_global.php' );
				}
			}
			else
			{
				$error = 'Error add Administrator';
			}
		}
	}

	$array_data['error'] = $error;
	$title = $lang_module['website_info'];
	$contents = nv_step_6( $array_data, $nextstep );
}
elseif( $step == 7 )
{
	$finish = 0;

	if( file_exists( NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_CONFIG_FILENAME ) )
	{
		$ftp_check_login = 0;
		$ftp_server_array = array( 'ftp_check_login' => 0 );

		if( $nv_Request->isset_request( 'ftp_server_array', 'session' ) )
		{
			$ftp_server_array = $nv_Request->get_string( 'ftp_server_array', 'session' );
			$ftp_server_array = unserialize( $ftp_server_array );
		}

		if( isset( $ftp_server_array['ftp_check_login'] ) and intval( $ftp_server_array['ftp_check_login'] ) == 1 )
		{
			// set up basic connection
			$conn_id = ftp_connect( $ftp_server_array['ftp_server'], $ftp_server_array['ftp_port'], 10 );

			// login with username and password
			$login_result = ftp_login( $conn_id, $ftp_server_array['ftp_user_name'], $ftp_server_array['ftp_user_pass'] );

			if( ( ! $conn_id ) || ( ! $login_result ) )
			{
				$ftp_check_login = 3;
			}
			elseif( ftp_chdir( $conn_id, $ftp_server_array['ftp_path'] ) )
			{
				$ftp_check_login = 1;
			}
		}

		if( $ftp_check_login == 1 )
		{
			ftp_rename( $conn_id, NV_TEMP_DIR . '/' . NV_CONFIG_FILENAME, NV_CONFIG_FILENAME );
			nv_chmod_dir( $conn_id, NV_UPLOADS_DIR, true );
			ftp_chmod( $conn_id, 0644, NV_CONFIG_FILENAME );
			ftp_close( $conn_id );
		}
		else
		{
			@rename( NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_CONFIG_FILENAME, NV_ROOTDIR . '/' . NV_CONFIG_FILENAME );
		}
	}

	if( file_exists( NV_ROOTDIR . '/' . NV_CONFIG_FILENAME ) )
	{
		$finish = 1;
	}
	else
	{
		$finish = 2;
	}

	$title = $lang_module['done'];
	$contents = nv_step_7( $finish );
}

echo nv_site_theme( $step, $title, $contents );

function nv_save_file_config()
{
	global $nv_Request, $file_config_temp, $db_config, $global_config, $step;

	if( is_writable( NV_ROOTDIR . '/' . $file_config_temp ) or is_writable( NV_ROOTDIR . '/' . NV_TEMP_DIR ) )
	{
		$global_config['cookie_prefix'] = ( empty( $global_config['cookie_prefix'] ) or $global_config['cookie_prefix'] == 'nv3' ) ? 'nv3c_' . nv_genpass( 5 ) : $global_config['cookie_prefix'];
		$global_config['session_prefix'] = ( empty( $global_config['session_prefix'] ) or $global_config['session_prefix'] == 'nv3' ) ? 'nv3s_' . nv_genpass( 6 ) : $global_config['session_prefix'];
		$global_config['site_email'] = ( ! isset( $global_config['site_email'] ) ) ? '' : $global_config['site_email'];

		$db_config['dbhost'] = ( ! isset( $db_config['dbhost'] ) ) ? 'localhost' : $db_config['dbhost'];
		$db_config['dbport'] = ( ! isset( $db_config['dbport'] ) ) ? '' : $db_config['dbport'];
		$db_config['dbname'] = ( ! isset( $db_config['dbname'] ) ) ? '' : $db_config['dbname'];
		$db_config['dbuname'] = ( ! isset( $db_config['dbuname'] ) ) ? '' : $db_config['dbuname'];
		$db_config['dbpass'] = ( ! isset( $db_config['dbpass'] ) ) ? '' : $db_config['dbpass'];
		$db_config['prefix'] = ( ! isset( $db_config['prefix'] ) ) ? 'nv3' : $db_config['prefix'];

		$persistent = ( $db_config['persistent'] ) ? 'true' : 'false';

		$content = '';
		$content .= "<?php\n\n";
		$content .= NV_FILEHEAD . "\n\n";
		$content .= "if ( ! defined( 'NV_MAINFILE' ) )\n";
		$content .= "{\n";
		$content .= "\tdie( 'Stop!!!' );\n";
		$content .= "}\n\n";
		$content .= "\$db_config['dbhost'] = '" . $db_config['dbhost'] . "';\n";
		$content .= "\$db_config['dbport'] = '" . $db_config['dbport'] . "';\n";
		$content .= "\$db_config['dbname'] = '" . $db_config['dbname'] . "';\n";
		$content .= "\$db_config['dbuname'] = '" . $db_config['dbuname'] . "';\n";
		$content .= "\$db_config['dbpass'] = '" . $db_config['dbpass'] . "';\n";
		$content .= "\$db_config['dbtype'] = '" . $db_config['dbtype'] . "';\n";
		$content .= "\$db_config['collation'] = '" . $db_config['collation'] . "';\n";
		$content .= "\$db_config['persistent'] = " . $persistent . ";\n";
		$content .= "\$db_config['prefix'] = '" . $db_config['prefix'] . "';\n";
		$content .= "\n";
		$content .= "\$global_config['idsite'] = 0;\n";
		$content .= "\$global_config['sitekey'] = '" . $global_config['sitekey'] . "';// Do not change sitekey!\n";

		if( $step < 7 )
		{
			$content .= "\$global_config['cookie_prefix'] = '" . $global_config['cookie_prefix'] . "';\n";
			$content .= "\$global_config['session_prefix'] = '" . $global_config['session_prefix'] . "';\n";

			$global_config['ftp_server'] = ( ! isset( $global_config['ftp_server'] ) ) ? "localhost" : $global_config['ftp_server'];
			$global_config['ftp_port'] = ( ! isset( $global_config['ftp_port'] ) ) ? 21 : $global_config['ftp_port'];
			$global_config['ftp_user_name'] = ( ! isset( $global_config['ftp_user_name'] ) ) ? "" : $global_config['ftp_user_name'];
			$global_config['ftp_user_pass'] = ( ! isset( $global_config['ftp_user_pass'] ) ) ? "" : $global_config['ftp_user_pass'];
			$global_config['ftp_path'] = ( ! isset( $global_config['ftp_path'] ) ) ? "" : $global_config['ftp_path'];
			$global_config['ftp_check_login'] = ( ! isset( $global_config['ftp_check_login'] ) ) ? 0 : $global_config['ftp_check_login'];

			if( $global_config['ftp_check_login'] )
			{
				$ftp_server_array = array(
					"ftp_server" => $global_config['ftp_server'],
					"ftp_port" => $global_config['ftp_port'],
					"ftp_user_name" => $global_config['ftp_user_name'],
					"ftp_user_pass" => $global_config['ftp_user_pass'],
					"ftp_path" => $global_config['ftp_path'],
					"ftp_check_login" => $global_config['ftp_check_login']
				);

				$nv_Request->set_Session( 'ftp_server_array', serialize( $ftp_server_array ) );
			}

			$content .= "\n";
			$content .= "\$global_config['ftp_server'] = '" . $global_config['ftp_server'] . "';\n";
			$content .= "\$global_config['ftp_port'] = '" . $global_config['ftp_port'] . "';\n";
			$content .= "\$global_config['ftp_user_name'] = '" . $global_config['ftp_user_name'] . "';\n";
			$content .= "\$global_config['ftp_user_pass'] = '" . $global_config['ftp_user_pass'] . "';\n";
			$content .= "\$global_config['ftp_path'] = '" . $global_config['ftp_path'] . "';\n";
			$content .= "\$global_config['ftp_check_login'] = '" . $global_config['ftp_check_login'] . "';\n";
		}

		$content .= "\n";
		$content .= "?>";

		file_put_contents( NV_ROOTDIR . '/' . $file_config_temp, $content, LOCK_EX );

		return true;
	}
	else
	{
		return false;
	}
}

?>