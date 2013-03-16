<?php

/*-----------------------------------

* simpas.php - SimPas core file
* (c) Macsch15 - web@macsch15.pl

-----------------------------------*/

class SimPas{

	/*
	 * Database
	 * @return object
	 * @access protected
	 */
	protected $db;

	/*
	 * Database data
	 * @return array
	 * @access protected
	 */
	protected $data;

	/*
	 * Paste syntax
	 * @return string
	 * @access public
	 */
	public $syntax;

	/*
	 * Paste main content
	 * @access public
	 * @return string
	 */
	public $content;

	/*
	 * ID request
	 * @access public
	 * @return string
	 */
	public $get;

	/*
	 * Paste ID
	 * @access public
	 * @return integer
	 */
	public $id;

	/*
	 * Actual time in timestamp
	 * @access public
	 * @return integer
	 */
	public $time;

	/*
	 * Errors
	 * @access public
	 * @return array
	 */
	public $errors = array();

	/*
	 * SimPas settings
	 * @access public
	 * @return array
	 */
	public $settings = array();

	/*
	 * SimPas i18n
	 * @access public
	 * @return array
	 */
	public $language = array();

	/*
	 * Page generation time, first microtime
	 * @access public
	 * @return array
	 */
	public $microtime;

	/*
	 * IP sender
	 * @access public
	 * See settings: "show_ip_sender"
	 * @return string
	 */
	public $ip_address = '0.0.0.0';

	/*
	 * Client IP
	 * @access public
	 * @return string
	 */
	public $client_ip;

	/*
	 * Application version
	 * @return string
	 */
	const Version = '0.9.5 B1';

	/*
	 * Main application construct
	 * @return string
	 */
	public function __construct($microtime){
		$this -> microtime = $microtime;
		
		if(!ini_get('date.timezone')){
			@date_default_timezone_set('Europe/Warsaw');
		}

		//--- Load settings and languages
		$this -> settings   = array_merge(require_once $this -> buildRootPath('configuration.php'));
		$this -> language   = array_merge(require_once $this -> buildRootPath('i18n/' . $this -> settings['default_lang'] . '.php'));

		//--- Set error reporting
		if($this -> settings['in_dev'] || $this -> settings['error_reporting']){
			ini_set('display_errors', 1);
			error_reporting(E_ALL ^ E_NOTICE);
			set_error_handler(array($this, 'errorHandler'), E_ALL ^ E_NOTICE);
		}else{
			ini_set('display_errors', 0);
			error_reporting(0);
			set_error_handler(array($this, 'errorHandler'), 0);
		}

		require_once $this -> buildRootPath('pdo.php');

		//--- SimPas INIT
		$this -> db         = new SimPasDB;
		$this -> syntax     = ($_POST['lang_select'] ? $this -> clearText($_POST['lang_select']) : $this -> settings['default_syntax']);
		$this -> content    = $_POST['code_cc'];
		$this -> get        = $this -> clearText($_GET['id']);
		$this -> id         = (isset($this -> get) ? $this -> clearText($this -> get) : false);
		$this -> data       = ($this -> id ? $this -> DBData() : array());
		$this -> client_ip  = (isset($_SERVER['REMOTE_ADDR']) && $this -> IPValid($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0');
		//---\ INIT

		if($this -> get){
			$this -> syntax = $this -> data[0]['syntax'];
			$this -> time   = $this -> data[0]['time'];
		}

		//--- V
		if(substr($this -> settings['home_url'], -1) !== '/'){
			$this -> settings['home_url'] .= '/';
		}

		if(!is_writable($this -> buildRootPath('cache'))){
			$this -> error[] = $this -> language['error'][10];
		}

		if(!$this -> settings['installed']){
			$this -> silentInstall();
		}

		if($this -> id && preg_match('/[^A-Za-z0-9]/is', $this -> id) || preg_match('/[^A-Za-z0-9]/is', $this -> get)){
			$this -> errors[] = $this -> language['error'][6];
			//--- DIE.
			return false;
		}

		if($this -> settings['use_furl'] && !file_exists($this -> buildRootPath('.htaccess'))){
			$this -> errors[] = $this -> language['error'][7];
		}

		if($this -> id && !$this -> checkIDExists($this -> id)){
			$this -> errors[] = $this -> language['error'][4];
		}

		if($_POST['checker'] === 'true' && preg_replace('/[\s\t\r\n]+/s', '', $this -> content) === ''){
			$this -> errors[] = $this -> language['error'][3];
		}

		if(isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] != 200){
			$this -> errors[] = sprintf($this -> language['error'][8], $_SERVER['REDIRECT_STATUS']);
		}
		//---\ Valid checkers
	}

	/*
	 * Check system status (on/off)
	 * @return bool
	 */
	public function systemIsOff(){
		if($this -> settings['status'] == 0){
			return true;
		}

		return false;
	}

	/*
	 * Create database tables (if not exists)
	 * @return bool
	 */
	protected function silentInstall(){
		require_once $this -> buildRootPath('cache/sqlTable.php');
		foreach($TABLE as $query){
			$this -> db -> execQuery($query);
		}

		$conf_file = file_get_contents($this -> buildRootPath('configuration.php'));
		$conf_file = preg_replace('/(\'installed\'\s+=>\s+)false/', '\\1true', $conf_file);

		file_put_contents($this -> buildRootPath('configuration.php'), $conf_file);
		file_put_contents($this -> buildRootPath('cache/timestamp.simpas'), time());

		if(!$this -> settings['in_dev']){
			@unlink($this -> buildRootPath('cache/sqlTable.php'));
		}

		return true;
	}

	/*
	 * Make relative path (or http adress)
	 * @return string
	 */
	public function buildRootPath($path = null, $http = false){
		if(!$http){
			return __DIR__ . DIRECTORY_SEPARATOR . (!empty($path) ? $path : null);
		}

		return $this -> settings['home_url'] . $path;
	}

	/*
	 * IP valid (ipv4 & ipv6)
	 * @return bool
	 */
	public function IPValid($ip){
		if(strpos($ip, ':', true)){
			$is_ipv6 = true;
		}

		// This is the regular expression taken from the Regular Expression Cookbook
		// by Jan Goyvaerts and Steven Levithan
		// @http://crisp.tweakblogs.net/blog/3049/ipv6-validation-more-caveats.html
		if($is_ipv6 && preg_match('
		/\A(?:
			# mixed
			(?:
				# Non-compressed
				(?:[A-F0-9]{1,4}:){6}
				# Compressed with at most 6 colons
				|(?=(?:[A-F0-9]{0,4}:){0,6}
					(?:[0-9]{1,3}\.){3}[0-9]{1,3}    # and 4 bytes
						\Z)                # and anchored
				# and at most 1 double colon
				(([A-F0-9]{1,4}:){0,5}|:)((:[A-F0-9]{1,4}){1,5}:|:)
			)
			# 255.255.255.
			(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
			# 255
			(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
			# Standard
			|(?:[A-F0-9]{1,4}:){7}[A-F0-9]{1,4}
			# Compressed with at most 7 colons
			|(?=(?:[A-F0-9]{0,4}:){0,7}[A-F0-9]{0,4}
				\Z) # anchored
			# and at most 1 double colon
			(([A-F0-9]{1,4}:){1,7}|:)((:[A-F0-9]{1,4}){1,7}|:)
		)\Z/ix', $ip
        )){
			return true;
		}

		if(!$is_ipv6 && preg_match('/\d{0,255}\.\d{0,255}\.\d{0,255}\.\d{0,255}/', $ip)){
			return true;
		}

		return false;
	}

	/*
	 * Paste size in bytes (or KB)
	 * @return integer
	 */
	public function getPasteSize($to_kb = false){
		$size_db = false;

		if($this -> get){
			$size_db = intval($this -> data[0]['size']);
		}

		if($to_kb){
			return ($size_db ? ceil($size_db / 1024) : $this -> stringToBytes($this -> content, true));
		}

		return ($size_db ? ceil($size_db / 1024) : $this -> stringToBytes($this -> content));
	}

	/*
	 * Check paste ID exists in DB
	 * @return bool
	 */
	protected function checkIDExists($id){
		return $this -> db -> countRows(array(
			'select' => 'unique_id',
			'from'   => 'simpas_pastes',
			'where'  => 'unique_id = "' . $this -> clearText($id) . '"',
			'limit'  => 1
			), true
		);
	}

	/*
	 * Paste length
	 * @return integer/string
	 */
	public function getPasteLen(){
		$len = $this -> realStrlen($this -> content);

		if($this -> get){
			$len = intval($this -> data[0]['length']);
		}

		if($len > pow(10, 6)){
			$len = '> 1 mln';
		}

		return $len;
	}

	/*
	 * Save paste to DB
	 * @return bool
	 */
	public function savePaste($code, $lang){
		if($this -> systemIsOff()){
			return false;
		}

		if(count($this -> settings['blocked_ip']) > 0){
			foreach($this -> settings['blocked_ip'] as $ip){
				$ip = str_replace('.', '\.', $ip);

				if(preg_match('/(' . $ip . ')/', $this -> client_ip)){
					$this -> errors[] = $this -> language['error'][9];
				}
			}
		}

		if(!in_array($this -> syntax, $this -> getLanguageArray())){
			$this -> errors[] = $this -> language['error'][0];
		}

		if($this -> getPasteLen() > $this -> settings['max_len'] && $this -> settings['max_len'] !== -1){
			$this -> errors[] = $this -> language['error'][1];
		}

		if($this -> getPasteSize(true) > $this -> settings['max_kb_size'] && $this -> settings['max_kb_size'] !== -1){
			$this -> errors[] = $this -> language['error'][2];
		}

		if($this -> settings['antyflood_status'] && $this -> isFlood()){
			$this -> errors[] = sprintf($this -> language['error'][11], $this -> settings['antyflood_time']);
		}

		$raw_code = $code;
		$raw_code = htmlspecialchars($raw_code);
		$raw_code = $this -> clearText($raw_code);

		switch($_POST['indentsize']){
			case '1':
				$code = str_replace("\t", " ", $code);
				$raw_code = str_replace("\t", " ", $raw_code);
			break;
			case '2':
				$code = str_replace("\t", "  ", $code);
				$raw_code = str_replace("\t", "  ", $raw_code);
			break;
			case '3':
				$code = str_replace("\t", "    ", $code);
				$raw_code = str_replace("\t", "    ", $raw_code);
			break;
			case 'tab':
				$code = $code;
				$raw_code = $raw_code;
			break;
			default:
				$code = str_replace("\t", "    ", $code);
				$raw_code = str_replace("\t", "    ", $raw_code);
			break;
		}

		$code = $this -> parseStringToGeshi($code, strtolower($lang));
		$code = $this -> clearText($code);

		$this -> id = uniqid($this -> generateID(mt_rand(4, 6)));

		if($this -> checkIDExists($this -> id)){
			$this -> errors[] = $this -> language['error'][5];
		}

		$title = $this -> getPasteTitle();
		$author = $this -> getPasteAuthor();

		if($this -> hasError()){
			return false;
		}

		$this -> db -> buildInsert('simpas_pastes', array(
			'unique_id'   => $this -> id,
			'time'        => time(),
			'size'        => $this -> getPasteSize(),
			'length'      => $this -> getPasteLen(),
			'syntax'      => $this -> syntax,
			'content'     => $code,
			'ip_address'  => $this -> client_ip,
			'raw_content' => $raw_code,
			'title'       => $title,
			'author'      => $author
			)
		);
	}

	/*
	 * Read paste from DB
	 * @return string
	 */
	public function readPaste(){
		if($this -> hasError()){
			return false;
		}

		if(!$this -> get){
			$this -> data = $this -> DBData();
		}

		if($this -> settings['show_ip_sender_except_ip']){
			$this -> ip_address = (count($this -> settings['show_ip_sender_except_ip']) > 0 
			&& in_array($this -> data[0]['ip_address'], $this -> settings['show_ip_sender_except_ip']) ? $this -> language['private_ip'] : $this -> data[0]['ip_address']);
		}

		return $this -> data[0]['content'];
	}

	/*
	 * Plain text mode
	 * @return string/bool
	 */
	public function getRawPaste(){
		if($_GET['raw'] === '1' && $this -> id && $this -> checkIDExists($this -> id)){
			return ($_GET['raw'] === '1' && $_GET['syntax_hl'] === '1' ? $this -> data[0]['content'] : '<pre>' . $this -> data[0]['raw_content'] . '</pre>');
		}

		return false;
	}

	/*
	 * Anty flood function
	 * @return bool
	 */
	public function isFlood(){
		if(count($this -> settings['antyflood_except_ip']) > 0 && in_array($this -> client_ip, $this -> settings['antyflood_except_ip'])){
			return false;
		}

		$search_ip = $this -> db -> countRows(array(
			'select' => 'ip_address',
			'from'   => 'simpas_pastes',
			'where'  => 'ip_address = "' . $this -> clearText($this -> client_ip) . '"',
			'limit'  => 1
			), true
		);

		if(!$search_ip){
			return false;
		}
							
		$flood_query = $this -> db -> countRows(array(
			'select' => 'ip_address, time',
			'from'   => 'simpas_pastes',
			'where'  => 'ip_address = "' . $this -> clearText($this -> client_ip) . '"
						 AND time >= "' . (time() - intval($this -> settings['antyflood_time'])) . '"',
			'limit'  => 1
			), true
		);

		if(!$flood_query){
			return false;
		}

		return true;
	}
	
	/*
	 * Paste title
	 * @return string
	 */
	public function getPasteTitle(){
		if($this -> get){
			return $this -> data[0]['title'];
		}

		if($_POST['pastetitle']){
			if(preg_match('/[^A-Za-z0-9\.\_\-\!\?ęóąśłżźćńĘÓĄŚŁŻŹĆŃ\s]/i', $_POST['pastetitle'])){
				$this -> errors[] = sprintf($this -> language['illegal_chars'], $this -> language['paste_title'][0]);
			}

			if($this -> realStrlen($_POST['pastetitle']) > $this -> settings['max_title_len']){
				$_POST['pastetitle'] = $this -> cutText($_POST['pastetitle'], $this -> settings['max_title_len']);
			}

			return $this -> clearText($_POST['pastetitle']);
		}

		return null;
	}

	/*
	 * Paste author
	 * @return string
	 */
	public function getPasteAuthor(){
		if($this -> get){
			return $this -> data[0]['author'];
		}

		if($_POST['pasteauthor']){
			if(preg_match('/[^A-Za-z0-9\.\_\-\!\?ęóąśłżźćńĘÓĄŚŁŻŹĆŃ\s]/i', $_POST['pasteauthor'])){
				$this -> errors[] = sprintf($this -> language['illegal_chars'], $this -> language['paste_author'][0]);
			}

			if($this -> realStrlen($_POST['pasteauthor']) > $this -> settings['max_author_len']){
				$_POST['pasteauthor'] = $this -> cutText($_POST['pasteauthor'], $this -> settings['max_author_len']);
			}

			return $this -> clearText($_POST['pasteauthor']);
		}

		return null;
	}

	/*
	 * All syntax languages
	 * @return array
	 */
	public function getLanguageArray(){
		global $language_data, $language;

		if(!file_exists($this -> buildRootPath('cache/languages.cache.php'))){
			$lang = $this -> loadFiles('geshi/geshi', '.php', true, false, true);

			foreach($lang as $key => $value){
				require $this -> buildRootPath('geshi/geshi/' . $value);

				$language[$value] = $language_data['LANG_NAME'];
			}
			
			$language = @serialize($language);

			if(!$this -> settings['in_dev']){
				$to_write = "<?php\n\$ts = " . time() . ";\n\$language = '" . $language . "';\n?>";
				$filename = 'languages.cache.php';

				@file_put_contents($this -> buildRootPath('cache/' . $filename), $to_write);
			}
		}else{
			require_once $this -> buildRootPath('cache/languages.cache.php');
		}

		return @unserialize($language);
	}

	/*
	 * Generate rand ID
	 * @return string
	 */
	protected function generateID($length){
		$standardChars = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789';

		$count = $this -> realStrlen($standardChars);

		$return = '';

		$iterator = 0;
		while($iterator <= intval($length)){
			$iterator++;

			$return .= $standardChars[mt_rand(0, $count - 1)];
		}

		return $return;
	}

	/*
	 * Cut text
	 * @return string
	 */
	public function cutText($text, $limit = 20, $part = false, $separator = '...'){
		if(!is_string($text)){
			return false;
		}

		$text = trim($text);
		$length = strlen($text);
		$limit = intval($limit);

		if($limit == null){
			$limit = 20;
		}

		if($length > $limit){
			$text = substr($text, 0, $limit) . ($part ? $separator . substr($text, -$limit) : '...');
		}

		return $text;
	}

	/*
	 * Load and display template
	 * @return bool
	 */
	public function doOutput(){
		require_once $this -> buildRootPath('library/Twig/Autoloader.php');
		Twig_Autoloader::register();

		$loader = new Twig_Loader_Filesystem($this -> buildRootPath('static/template'));

		$twig = new Twig_Environment($loader, array(
			'cache' => ($this -> settings['in_dev'] ? false : $this -> buildRootPath('cache')),
			'strict_variables' => ($this -> settings['in_dev'] ? true : false)
		));

		$twig -> addGlobal('SimPas', $this);

		$output = $twig -> loadTemplate('globalTemplate.twig.html');
		$output -> display(array(/*--- YAYAYA ---*/));

		return true;
	}

	/*
	 * Remove evil tags from $text
	 * @return string
	 */
	public function clearText($text){
		$text = trim($text);

		$text = addslashes($text);

		$remove = array(
			'/<html/is'       => '&lt;html',
			'/<body/is'       => '&lt;body',
			'/<img/is'        => '&lt;img',
			'/about:/is'      => '&#097;bout:',
			'/onload/is'      => '&#111;nload',
			'/onclick/is'     => '&#111;nclick',
			'/alert/is'       => '&#097;lert',
			'/onsubmit/is'    => '&#111;nsubmit',
			'/document\./is'  => '&#100;ocument.',
			'/onmouseover/is' => '&#111;nmouseover'
		);

		$text = preg_replace(array_keys($remove), array_values($remove), $text);
		return $text;
	}

	/*
	 * Convert string to bytes (or KB)
	 * @return integer
	 */
	protected function stringToBytes($var, $to_kb = false){
		$len = strlen($var);
		$pow = pow(10, 0);

		return ($to_kb ? ceil(round($len / (pow(1024, 0) / $pow)) / $pow / 1024) : round($len / (pow(1024, 0) / $pow)) / $pow);
	}

	/*
	 * IE-Checker
	 * @return bool/integer
	 */
	public function isIE($version = false, $full_ver = false){
		if(!isset($_SERVER['HTTP_USER_AGENT'])){
			return false;
		}

		$check = preg_match('/msie(\s+((\d+)\.\d+)\;)/i', $_SERVER['HTTP_USER_AGENT'], $match);
		$v = (isset($match[2]) && $full_ver ? $match[2] = intval(str_replace('.', '', $match[2])) : (isset($match[3]) ? intval($match[3]) : null));

		if(!$check){
			return false;
		}

		return ($version ? $v : true);
	}

	/*
	 * Parse text to geshi (syntax highlighter)
	 * @return string
	 */
	protected function parseStringToGeshi($code, $lang){
		if(!class_exists('GeSHi')){
			require_once $this -> buildRootPath('geshi/geshi.php');
			$geshi = new GeSHi($code, $lang);
		}

		if($this -> settings['enable_line_numbers'] && $_POST['numline']){
			$geshi -> enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
		}

		return $geshi -> parse_code();
	}

	/*
	 * Load files from dir
	 * @return string/array
	 */
	protected function loadFiles($dir, $ext, $to_array = false, $url = false, $only_filename = false){
		foreach(new DirectoryIterator($this -> buildRootPath($dir)) as $file){
			if($file -> isFile() && strpos($file, $ext, true)){
				if(!$to_array){
					$files = (!$only_filename ? ($url ? $this -> settings['home_url'] : $this -> buildRootPath()) . (substr($dir, -1) == '/' ? $dir : $dir . '/') : null) . $file -> getFilename();
					require_once $files;
				}else{
					$files[] = (!$only_filename ? ($url ? $this -> settings['home_url'] : $this -> buildRootPath()) . (substr($dir, -1) == '/' ? $dir : $dir . '/') : null) . $file -> getFilename();
				}
			}
		}
		return $files;
	}

	/*
	 * Strlen without spaces, tabs, new lines...
	 * @return integer
	 */
	protected function realStrlen($text, $except_chars = null){
		$text = preg_replace('/[\s\t\r\n' . trim($except_chars) . ']+/s', '', $text);

		return strlen($text);
	}

	/*
	 * Fetch data from DB for paste-ID
	 * @return array
	 */
	protected function DBData(){
		$this -> db -> buildSelect(array(
			'select' => 'id, unique_id, time, size, length, syntax, content, ip_address, raw_content, title, author',
			'from'   => 'simpas_pastes',
			'where'  => 'unique_id = "' . $this -> id . '"',
			'limit'  => 1
			)
		);
		
		return $this -> db -> fetch();
	}

	/*
	 * Debug
	 * @return string
	 */
	public function getDebugResult(){
		if(!isset($this -> microtime)){
			return false;
		}

		return '%s' . abs(round(microtime(true) - $this -> microtime, 4)) . 's
		%s' . round(memory_get_peak_usage(true) / 1024, 2) . 'KB
		%s' . $this -> serverLoad() . '
		%s' . $this -> db -> getNumQuery();
	}

	/*
	 * Display server load (X.X) (only linux)
	 * @return string
	 */
	protected function serverLoad(){
		$load = '-';

		if(strpos(PHP_OS, 'win') !== true){
			$unixload = @exec('uptime');
			preg_match('/(.*?)average: (\d+\.\d+)\,(.*?)/', $unixload, $match);

			if($match[2] != null){
				$load = $match[2];
			}
		}

		return $load;
	}
	
	/*
	 * List DB queries
	 * @return integer
	 */
	public function getDBQueries(){
		return $this -> db -> getDebug();
	}	

	/*
	 * Error handler
	 * @return string/bool
	 */
	public function errorHandler($errno, $errstr, $errfile, $errline){
		$saveErrors = true;

		if(!$errno){
			return false;
		}

		$this -> errors[] = '<b>PHP: </b> ' . $errstr . ' on line ' . $errline . '. File: ' . $errfile;

		if($saveErrors){
			$to_write = "<----------------------->\nError: " . $errstr . "\nLine: " . $errline . "\nLevel: " . $errno . "\nFile: " . $errfile . "\nDate: " . date('r') . "\n<----------------------->\n\n\n";
			$filename = $this -> settings['errorlog_prefix_filename'] . md5(date('d_m_Y_H_i')) . '.cgi';

			return file_put_contents($this -> buildRootPath('cache/' . $filename), $to_write, FILE_APPEND);
		}

		return true;
	}

	/*
	 * Check errors
	 * @return array/bool
	 */
	public function hasError(){
		if(count($this -> errors) > 0){
			return $this -> errors;
		}

		return false;
	}
}