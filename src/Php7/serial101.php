<?php //éèà


// ----------------------------------------------------------------------------


// load global settings
require $_SERVER['DOCUMENT_ROOT'].'/includes/commons5.php';

ob_end_clean();	// clear output buffering (suppress BOMs, if any from included files)
ob_start();		// restart output buffering


// -----------------------------------------------------------------------------


define('__PROG__', SCRIPT_FILE);

$microtime_us0 = 0;

function micros()
{
	global $microtime_us0;
	if ($microtime_us0 == 0) $microtime_us0 = microtime_us();
	return (microtime_us() - $microtime_us0);
}

function millis()
{
	return floor(micros()/1000);
}


// -----------------------------------------------------------------------------


define('OK', true);
define('ER', false);

define('_OK_', "=OK=");
define('_ER_', "#ER#");

define('_DG_', "[DG]");

define('_BC_', "<!--");
define('_EC_', " -->"."\n");


// -----------------------------------------------------------------------------


function subtrace_line($line)
{
	return sprintf(" %s:%d; millis:%d;",  __PROG__, $line, millis());
}

function trace_line($line)
{
//	return sprintf(_BC_._DG_." %s:%d; millis:%d;"._EC_,  __PROG__, $line, millis());
	return sprintf(_BC_._DG_.sprintf($line)._EC_);
}

function trace_line_message($line, $message)
{
//	return sprintf(_BC_._DG_." %s:%d; millis:%d; message:%s;"._EC_,  __PROG__, $line, millis(), ve($message));
	return sprintf(_BC_._DG_.subtrace_line($line).sprintf(" message:%s;", ve($message))._EC_);
}


// -----------------------------------------------------------------------------


// About UTF-16, UCS-2, ISO 10646 and Windows:
// -------------------------------------------
// https://fr.wikipedia.org/wiki/UTF-16
// https://fr.wikipedia.org/wiki/ISO/CEI_10646
// https://docs.microsoft.com/en-us/windows/win32/extensible-storage-engine/wchar


define('EXEC_CODEPAGE', console_codepage_get());
define('POPEN_CODEPAGE', 'UCS-2');
define('COMM_PORT', 'COM1');
define('SERIAL_EXE', 'serial101.exe');


// ----------------------------------------------------------------------------


// Parsing C++ Command-Line Arguments - https://msdn.microsoft.com/en-us/library/17w5ykft(v=vs.85).aspx
// http://www.windowsinspired.com/understanding-the-command-line-string-and-arguments-received-by-a-windows-program/
// https://docs.microsoft.com/en-us/cpp/c-language/parsing-c-command-line-arguments?view=vs-2019
// https://docs.microsoft.com/en-us/windows-server/administration/windows-commands/set_1
// https://ss64.com/nt/syntax-esc.html
// https://docs.microsoft.com/en-us/archive/blogs/twistylittlepassagesallalike/everyone-quotes-command-line-arguments-the-wrong-way
// https://docs.microsoft.com/en-us/previous-versions//17w5ykft(v=vs.85)?redirectedfrom=MSDN


function console_argument_escape($argument)
{
	$s = $argument;

	$s = str_replace('"', '\""', $s);
	if (preg_match('/[\t "<>|&^]/', $s)) $s = '"'.$s.'"';

	return $s;
}


// Code Page 850 MS-DOS Latin 1 - https://msdn.microsoft.com/en-us/library/cc195064.aspx
// Code Page Identifiers - https://msdn.microsoft.com/en-us/library/windows/desktop/dd317756(v=vs.85).aspx


function console_codepage_get()
{
	$matches = null;
//	if (preg_match('/\xFF: (\d+)$/', exec('chcp'), $matches) !== 1)
	if (preg_match('/: (\d+)$/', exec('CHCP'), $matches) !== 1)
		trigger_error('if(preg_match()!==1)', E_USER_ERROR);
//echo $matches[1],BR,NL;
	return $matches[1];
}


function console_codepage_check()
{
	if (console_codepage_get() !== EXEC_CODEPAGE)
		trigger_error('if(console_codepage_get()!==EXEC_CODEPAGE)', E_USER_ERROR);
}


function console_codepage_encode($command)
{
	$s = $command;
	if (EXEC_CODEPAGE != 65001) $s = iconv(DEFAULT_CHARSET, 'cp'.EXEC_CODEPAGE.'//TRANSLIT', $s);
	return $s;
}


function console_codepage_decode($output)
{
	$s = $output;
	if (EXEC_CODEPAGE != 65001) $s = iconv('cp'.EXEC_CODEPAGE, DEFAULT_CHARSET.'//TRANSLIT', $s);
	return $s;
}


function console_exec($program, $arguments, &$output, &$retcode) // return last output line
{
	$command = console_argument_escape($program);
	foreach ($arguments as $argument) $command .= SP.console_argument_escape($argument);
	
	$result = console_codepage_decode( exec(console_codepage_encode($command), $output, $retcode) );
	foreach ($output as &$line) $line = console_codepage_decode($line);

	return $result;
}


// ----------------------------------------------------------------------------


// CreateProcess - https://msdn.microsoft.com/en-us/library/windows/desktop/ms682425(v=vs.85).aspx

function program_exec($program, $arguments, &$output, $length=4096)  // return read binary content
{
	$output = '';
	
	$command = console_argument_escape($program);
	foreach ($arguments as $argument) $command .= SP.console_argument_escape($argument);

	$handle = popen($command.' 2>&1', 'r');
	// if (is_null($handle)) trigger_error('if (is_null($handle))', E_USER_ERROR);
	// !!! It's impossible to check if $handle is normal or error !!!
	
	while ($length>0) {
		$result = fread($handle, $length);
		if (strlen($result)==0) break;
		$output .= $result;
		$length -= strlen($result);
	}
	
	$retcode = pclose($handle);
//echo $retcode,BR,NL;
	
//	return $output;
	return $retcode;
}


// ----------------------------------------------------------------------------


/*

MODE COM8 /STATUS
=================

<cr><lf>
Statut du périphérique COM8:<cr><lf>
----------------------------<cr><lf>
	Baud :		115200<cr><lf>
	Parité :	  None<cr><lf>
	Bits de données : 8<cr><lf>
	Bits d'arrêt :	1<cr><lf>
	Temporisation :   ON<cr><lf>
	XON/XOFF :	OFF<cr><lf>
	Protocole CTS :   OFF<cr><lf>
	Protocole DSR :   OFF<cr><lf>
	Sensibilité DSR : OFF<cr><lf>
	Circuit DTR :	 OFF<cr><lf>
	Circuit RTS :	 OFF<cr><lf>
<cr><lf>

*/


// MODE COM8 baud=115200 parity=N data=8 stop=1 to=ON xon=OFF octs=OFF odsr=OFF idsr=OFF dtr=OFF rts=OFF


function comm_settings_arduino()
{
	return [
		'baud' => '115200',
		'parity' => 'None',
		'data' => '8',
		'stop' => '1',
		'to' => 'ON',
		'xon' => 'OFF',
		'octs' => 'OFF',
		'odsr' => 'OFF',
		'idsr' => 'OFF',
		'dtr' => 'OFF',
		'rts' => 'OFF'
	];
}


function comm_settings_arguments($settings) // return arguments list
{
	if (!is_array($settings)) return null;
	
	$a = [];
	foreach ($settings as $key => $value) $a[] = $key.'='.($key=='parity' ? substr($value,0,1) : $value);
	
	return $a;
}


function comm_settings_tostring($settings) // return string list
{
	return implode(SP, comm_settings_arguments($settings));
}


function comm_list_getall() // return an array of available COM devices and their current settings
{
	$output = null;
	$retcode = null;
	console_exec("MODE", ["/STATUS"], $output, $retcode );
	if ($retcode != 0) trigger_error("if (retcode != 0)", E_USER_ERROR);

	$a = [];
	foreach ($output as $line) {
		$match = null;
//		if (preg_match('/^Statut du périphérique'.SP.'COM([0-9]+)'.':$/', $line, $matches)==1) {
		if (preg_match('/^.+ COM([0-9]+):$/', $line, $matches)==1) {
			$a[] = $matches[1];
		}
	}
	sort($a);
	foreach ($a as &$value) $value = 'COM'.$value;
	
	return $a;
}

#if 0

function comm_settings_getall() // return an array of settings of all devices (CON, COM, ...)
{
	$result = program_exec("MODE", ["/STATUS"]);

	$a = explode("\n\n", trim($result));
//echo __LINE__."\n";
//echo ve($a)."\n";
	foreach ($a as &$device) $device = explode("\n", trim($device));
	foreach ($a as &$device)
	{
		$b = [];
		foreach ($device as $num => &$line) {
		switch ($num)
		{
			case 0:
//				if (preg_match('/^Statut du périphérique'.SP.'([A-Z]+[0-9]*)'.':$/', $line, $matches)!==1) 
				if (preg_match('/^.+ ([A-Z]+[0-9]*):$/', $line, $matches)!==1) 
					trigger_error("preg_match(COMx) doesn't match.");
				$b['Périphérique'] = $matches[1];
				break;
				
			case 1:
				if (preg_match('/^[-]+$/', $line)!==1) 
					trigger_error("preg_match(---) doesn't match.");
				break;
				
			default:
				$c = explode(NB.': ', trim($line));
				$b[trim($c[0])] = trim($c[1]);
				break;
			};
		}
		$d[$b['Périphérique']] = $b;
	}
	$a = $d;
//echo __LINE__."\n";
//echo ve($a)."\n";
	
	return $a;
}

#endif

function comm_settings_get($comm_port) // return an array of settings of a COM device
{
	$output = null;
	$retcode = null;
	console_exec("MODE", [$comm_port, "/STATUS"], $output, $retcode );
//	if ($retcode != 0) trigger_error("if (retcode != 0)", E_USER_ERROR);
	if ($retcode != 0) return null;
	
	$settings = [];
	$i = 3;
	foreach (comm_settings_arduino() as $key => $value)
		$settings[$key] = mb_substr($output[$i++], 22);
		
	return $settings;
}


function comm_settings_put($comm_port, $settings) // put settings to a COM device
{
	$output = null;
	$retcode = null;
	console_exec("MODE", array_merge([$comm_port], comm_settings_arguments($settings)), $output, $retcode);
	if ($retcode != 0) trigger_error("if (retcode != 0)", E_USER_ERROR);
	
	$settings = [];
	$i = 3;
	foreach (comm_settings_arduino() as $key => $value)
		$settings[$key] = mb_substr($output[$i++], 22);
		
	return $settings;
}


// ----------------------------------------------------------------------------


function qs_boolval($s)
{
	return (
		isset($s) ? 
			is_bool($s) ?
				$s :
				is_numeric($s) ?
					($s!=0) :
					strlen($s)==0 ? 
						true :
						array_search(mb_strtolower($s), ['true','ok','on','enable','enabled','active','actived','yes','oui','ja','si']) : 
		false
	);
} 


// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------


$debug = qs_boolval(ain($_GET,'debug'));

$init = qs_boolval(ain($_GET,'init'));
$check = qs_boolval(ain($_GET,'check'));
$put = qs_boolval(ain($_GET,'put'));
$flush = qs_boolval(ain($_GET,'flush'));

$return = aie($_GET,'return');


// ----------------------------------------------------------------------------


$cmd_options = intval(aie($_GET,'cmd_options','0'));
$comm_port = strtoupper(aie($_GET,'comm_port', aie($_COOKIE,'comm_port', 'COM1')));
$rx_timeout = intval(aie($_GET,'rx_timeout','100'));
$tx_argument = aie($_GET,'tx_argument');


// ----------------------------------------------------------------------------


$response = '';


// ----------------------------------------------------------------------------


//if ($debug) $response .= __LINE__.':'.SP.SCRIPT_HREF.NL;
if ($debug) $response .= trace_line_message(__LINE__, SCRIPT_HREF);


// ----------------------------------------------------------------------------


$retcode = 0;

$comm_list = null;		// 
$comm_status = null;	//
$comm_answer = null;	//

$flush_input_needed = false;
$put_settings_needed = false;


// ----------------------------------------------------------------------------


if(0) {

if ($init || $check)
{
//	// assert codepage of console: CMD_CODEPAGE expected
//	console_codepage_check();
//	if ($debug) $response .= __LINE__.':'.SP.CMD_CODEPAGE.NL;

	// retrieve list of device and then assert $comm_port into
	$comm_list = comm_list_getall();
//	if ($debug) $response .= __LINE__.':'.SP.ve($comm_list).NL;
	if ($debug) $response .= trace_line_message(__LINE__, $comm_list);
	if (array_search($comm_port, $comm_list)===false)
		trigger_error("if (array_search(comm_port,comm_list)===false)", E_USER_ERROR);

	// get $comm_port settings and then check expected settings: if != then put settings
	$comm_status = comm_settings_get($comm_port);
//echo __LINE__,':';
//echo ve($comm_status),NL;
//	if ($debug) $response .= __LINE__.':'.SP.ve($comm_status).NL;
	if ($debug) $response .= trace_line_message(__LINE__, $comm_status);
	if ($comm_status !== comm_settings_arduino())
		if (!$init)
			trigger_error("if (comm_status !== comm_settings_arduino())", E_USER_ERROR);
		else
			$put_settings_needed = true;
}

if ($init && $put_settings_needed || $put)
{
	// put settings
	$comm_status = comm_settings_put($comm_port, comm_settings_arduino());
//	if ($debug) $response .= __LINE__.':'.SP.ve($comm_status).NL;
	if ($debug) $response .= trace_line_message(__LINE__, $comm_status);
	if ($comm_status !== comm_settings_arduino())
		trigger_error("if (comm_status !== comm_settings_arduino())", E_USER_ERROR);
	$flush_input_needed = true;
}

if ($init && $flush_input_needed || $flush)
{
	// flush receive result from Arduino
	$retcode = program_exec(SERIAL_EXE, [0, $comm_port, 100], $comm_answer);
//	if ($debug) $response .= __LINE__.':'.SP.$retcode.SP.ve($comm_answer).NL;
	if ($debug) $response .= trace_line_message(__LINE__, $retcode.SP.$comm_answer);
}

}


// ----------------------------------------------------------------------------


if ($check)
{
	// check communication with Arduino
	$retcode = program_exec(SERIAL_EXE, [0, $comm_port, 100, "/millis"], $comm_check);
//	if ($debug) $response .= __LINE__.':'.SP.$retcode.SP.ve($comm_check).NL;
	if ($debug) $response .= trace_line_message(__LINE__, $retcode.SP.$comm_check);
}
else
{
	$comm_check = null;
}


// ----------------------------------------------------------------------------


if (strlen($return))
{
	if ($return=='all') $return = 'codepage,comm_list,comm_port,comm_settings,comm_check';

	foreach (explode(',', $return) as $value)
	{
		switch ($value)
		{			
			case 'cp':
			case 'codepage':
				$response .= $value.':'.console_codepage_get().NL;
				break;

			case 'comm_list':
				$response .= $value.':'.implode(',', comm_list_getall()).NL;
				break;

			case 'comm_port':
				$response .= $value.':'.$comm_port.NL;
				break;

			case 'comm_settings':
				$_csa = comm_settings_arguments(comm_settings_get($comm_port));
				if (!isset($_csa)) $_csa = [];
				$response .= $value.':'.implode(',', $_csa).NL;
				break;

			case 'comm_check':
//				$response .= $value.':'.$comm_check.NL;
				$response .= $value.':'.(comm_settings_get($comm_port)?'true':'false').NL;
				break;
		}
	}
}


// ----------------------------------------------------------------------------


// send command to Arduino and wait answer from Arduino...

if (strlen($tx_argument))
{
	$retcode = program_exec(SERIAL_EXE, [$cmd_options, $comm_port, $rx_timeout, $tx_argument], $comm_answer);
//	if ($debug) $response .= __LINE__.':'.SP.ve($comm_answer).NL;
	if ($debug) $response .= trace_line_message(__LINE__, $comm_answer);
	$response .= $comm_answer;

//	//$response = str_replace('<style>', '<style>body {margin:auto; width:400px;} ', $response);
//	$response = str_replace('<style>', '<style>body {margin:8px;} ', $response);
}


// ----------------------------------------------------------------------------


if ($retcode != 0)
{
//	$response .= '<!--'.'#ER#'.$retcode.' -->'.NL;
	$response .= _BC_._ER_.subtrace_line(__LINE__).' retcode:'.$retcode.';'._EC_;
}
 

// ----------------------------------------------------------------------------

/*

// echo content with a content-type...

if (substr($tx_argument, -5) == ".html" ||
	substr($tx_argument, -4) == ".htm" ||
	substr($tx_argument, -4) == ".php" ||
	substr($tx_argument, 0, 1) == '/' && substr($tx_argument, -1) == "/")
//then
	echo_content( $response, ['NO_CACHE','ALLOW_ORIGIN_ALL','TEXT_HTML'] );
else
if (substr($tx_argument, -4) == ".css")
//then
	echo_content( $response, ['NO_CACHE','ALLOW_ORIGIN_ALL','TEXT_CSS'] );
else
if (substr($tx_argument, -3) == ".js")
//then
	echo_content( $response, ['NO_CACHE','ALLOW_ORIGIN_ALL','APPLICATION_JAVASCRIPT'] );
else
if (substr($tx_argument, -5) == ".json")
//then
	echo_content( $response, ['NO_CACHE','ALLOW_ORIGIN_ALL','APPLICATION_JSON'] );
else
if (substr($tx_argument, -6) == ".jsonp")
//then
	echo_content( $response, ['NO_CACHE','ALLOW_ORIGIN_ALL','APPLICATION_JAVASCRIPT'] );
else
if (substr($tx_argument, -4) == ".xml")
//then
	echo_content( $response, ['NO_CACHE','ALLOW_ORIGIN_ALL','APPLICATION_XML'] );
else
if (substr($tx_argument, -4) == ".csv")
//then
	echo_content( $response, ['NO_CACHE','ALLOW_ORIGIN_ALL','TEXT_CSV'] );
else
//default...
//content-type text/plain
	echo_content( $response, ['NO_CACHE','ALLOW_ORIGIN_ALL','TEXT_PLAIN'] );

*/

// ----------------------------------------------------------------------------


// set headers with a content-type...

$is_text_html = false;

if (substr($tx_argument, -5) == ".html" ||
	substr($tx_argument, -4) == ".htm" ||
	substr($tx_argument, -4) == ".php" ||
	substr($tx_argument, 0, 1) == '/' && substr($tx_argument, -1) == "/")
//then
{
	$is_text_html = true;
	headers_set( ['NO_CACHE','ALLOW_ORIGIN_ALL','TEXT_HTML'] );
}
else
if (substr($tx_argument, -4) == ".css")
//then
	headers_set( ['NO_CACHE','ALLOW_ORIGIN_ALL','TEXT_CSS'] );
else
if (substr($tx_argument, -3) == ".js")
//then
	headers_set( ['NO_CACHE','ALLOW_ORIGIN_ALL','APPLICATION_JAVASCRIPT'] );
else
if (substr($tx_argument, -5) == ".json")
//then
	headers_set( ['NO_CACHE','ALLOW_ORIGIN_ALL','APPLICATION_JSON'] );
else
if (substr($tx_argument, -6) == ".jsonp")
//then
	headers_set( ['NO_CACHE','ALLOW_ORIGIN_ALL','APPLICATION_JAVASCRIPT'] );
else
if (substr($tx_argument, -4) == ".xml")
//then
	headers_set( ['NO_CACHE','ALLOW_ORIGIN_ALL','APPLICATION_XML'] );
else
if (substr($tx_argument, -4) == ".csv")
//then
	headers_set( ['NO_CACHE','ALLOW_ORIGIN_ALL','TEXT_CSV'] );
else
//default...
//content-type text/plain
	headers_set( ['NO_CACHE','ALLOW_ORIGIN_ALL','TEXT_PLAIN'] );
 

// ----------------------------------------------------------------------------


function read_file($filename,$default_content='')
{
	if (file_exists($filename))
		return file_get_contents($filename);
	else
		return $default_content;
}


function include_php_text($t1)
{
//	echo($t1);
//	return;

/*	
//	$t1 = 
//	"<?=1234?>\n" .
//	"abcd<title>\n" .
//	"<?php\necho 5678?>\n" .
//	"ijkl\n";
*/	

	if (strlen($t1) == 0) return;
	
	define('PHP0', '<\?');
//	define('PHP1', '<\?(php)(\r\n|\r|\n|\s)');
	define('PHP1', '<\?php\s');
//	define('PHP2', '<\?(=)()');
	define('PHP2', '<\?=');
//	define('PHP3', '<\?()()');
	define('PHP3', '<\?');
	define('PHP9', '\?>\R');

/*	preg_match_all('/<\?(=|php)?[^?]*\?>|([^<]|<(?!\?(=|php)?[^?]*\?>))+/', $t1, $a1, PREG_SET_ORDER);	*/
/*	preg_match_all('/(<\?(=|php)?)[^?]*(\?>)|([^<]|<(?!\?(=|php)?[^?]*\?>))+/', $t1, $a1, PREG_SET_ORDER);	*/
	preg_match_all(
		'/' .
//		'('.PHP1.'|'.PHP2.'|'.PHP3.')' . '[^?]*' . '('.PHP9.')' .
		'('.PHP1.'|'.PHP2.'|'.PHP3.')' . '((?!'.PHP9.').*)' . '('.PHP9.')' .
		'|' .
//		'([^<]|<(?!\?(=|php)?[^?]*'.PHP9.'))' .
//		'(.|\R)+(?=' . '('.PHP1.'|'.PHP2.'|'.PHP3.')' . '((?!'.PHP9.').*)' . '('.PHP9.')' .')' .
//		'((?!'.PHP0.')[\r\n\t 0-9a-z]+)' .
//		'(((?!'.PHP0.')(.|\R))+)' .
//		'((?!'.PHP1.'|'.PHP2.'|'.PHP3.')(.|\R))+' .
		'((?!'.PHP1.'|'.PHP2.'|'.PHP3.').|(?!'.PHP1.'|'.PHP2.'|'.PHP3.')\R)+' .
		'/',
		$t1, $a1, PREG_SET_ORDER);

//echo(ve($a1).NL);
//if (implode("", $a1) != $t1) die("'t1.match' not complete!");

	foreach ($a1 as $t2)
	{
		if (substr($t2[0],0,5) == '<'.'?'.'php')
		{
		//	eval(substr($t2[0],5,strlen($t2[0])-5-2));
//			eval(substr($t2[0],5,strlen($t2[0])-5-2).';');
			eval( substr($t2[0],strlen($t2[1]),strlen($t2[0])-strlen($t2[1])-strlen($t2[count($t2)-1])).';' );
		}
		else
		if (substr($t2[0],0,3) == '<'.'?'.'=')
		{
		//	eval('echo '.substr($t2[0],3,strlen($t2[0])-3-2));
//			eval('echo '.substr($t2[0],3,strlen($t2[0])-3-2).';');
			eval( 'echo '.substr($t2[0],strlen($t2[1]),strlen($t2[0])-strlen($t2[1])-strlen($t2[count($t2)-1])).';' );
		}
		else
		if (substr($t2[0],0,2) == '<'.'?')
		{
		//	eval(substr($t2[0],2,strlen($t2[0])-2-2));
//			eval(substr($t2[0],2,strlen($t2[0])-2-2).';');
			eval( substr($t2[0],strlen($t2[1]),strlen($t2[0])-strlen($t2[1])-strlen($t2[count($t2)-1])).';' );
		}
		else
		{
			echo( $t2[0] );
		}
	}
	return true;
}
 

// ----------------------------------------------------------------------------


if ($is_text_html)
{
	include_php_text($response);
}
else
{
	echo_content($response);
}


// ----------------------------------------------------------------------------


?>
