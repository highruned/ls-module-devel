<?php

class Devel_Class
{
    protected static $instance = null;

    /*
     * Page Load
     */
    protected static $page_load = array(
                                    'boot'          =>  array('start'=>0, 'end'=>0),
                                    'init'          =>  array('start'=>0, 'end'=>0),
                                    'page'          =>  array('start'=>0, 'end'=>0)
                                  );

    /*
     * Memory Usage
     */
    protected static $memory_load = array(
                                    'boot_peak'     =>  array('start'=>0, 'end'=>0),
                                    'boot_regular'  =>  array('start'=>0, 'end'=>0),
                                    'init_peak'     =>  array('start'=>0, 'end'=>0),
                                    'init_regular'  =>  array('start'=>0, 'end'=>0)
                                  );

    /*
     * Sql Usage
     */
    protected static $sql_log = array();
    protected static $sql_load = array();
    protected static $sql_total_times = 0;

    public static function create()
    {
        if(self::$instance)
            return self::$instance;

        return self::$instance = new self();
    }

    public static function core_initialize()
    {
        self::$page_load['init']['start'] = microtime(true);

        self::$memory_load['init_peak']['start'] = memory_get_peak_usage(true);
        self::$memory_load['init_regular']['start'] = memory_get_usage(true);;

        ob_start( array('Devel_Class', 'handle_buffer') );
    }

    public static function core_uninitialize()
    {
        self::print_footer();
    }

    public static function before_page_display($page)
    {
        self::$page_load['page']['start'] = microtime(true);
    }

    public static function after_page_display($page)
    {
        self::$page_load['page']['end'] = microtime(true);
    }

    public static function before_handle_ajax($page)
    {
        self::$page_load['page']['start'] = microtime(true);
    }

    public static function after_handle_ajax($page)
    {
        self::$page_load['page']['end'] = microtime(true);

        echo self::print_footer($page, true);
    }

    public static function on_before_query($sql)
    {
        $key = md5($sql);
        self::$sql_log[] = array('sql'=>$sql, 'key'=>$key);

        //$test = self::get_backtrace();
        //var_dump($test);

        if( !isset(self::$sql_load[$key]) )
            self::$sql_load[$key] = array('start'=>0, 'end'=>0);
        self::$sql_load[$key]['start'] = microtime(true);
    }

    public static function on_after_query($sql, $results)
    {
        $key = md5($sql);

        self::$sql_load[$key]['end'] = microtime(true);
        $total_time = self::$sql_load[$key]['end'] - self::$sql_load[$key]['start'];
        self::$sql_total_times += $total_time;
    }

    public static function handle_buffer($buffer)
    {
        return self::print_footer($buffer, false);
    }

    public static function print_footer($buffer, $is_ajax=false)
    {
        global $module_devel_start_time;

        $timenow = microtime(true);

        if( isset($module_devel_start_time) ) {
            self::$page_load['boot']['start'] = $module_devel_start_time;
        }

        self::$page_load['boot']['end'] = $timenow;
        self::$page_load['init']['end'] = $timenow;
        self::$page_load['page']['end'] = $timenow;

        $real_page_load_time = ( self::$page_load['boot']['start'] > 0 ) ? self::$page_load['boot']['end'] - self::$page_load['boot']['start'] : 0;
        $page_load_time = self::$page_load['init']['end'] - self::$page_load['init']['start'];

        $boot_sequence = ( self::$page_load['boot']['start'] > 0 ) ? self::$page_load['init']['start'] - self::$page_load['boot']['start'] : 0;
        $initialize_sequence = ( self::$page_load['boot']['start'] > 0 ) ? self::$page_load['page']['start'] - self::$page_load['boot']['start'] : 0;
        $page_sequence = ( self::$page_load['page']['start'] > 0 ) ? self::$page_load['page']['end'] - self::$page_load['page']['start'] : 0;

        self::$memory_load['init_peak']['end'] = memory_get_peak_usage(true);
        self::$memory_load['init_regular']['end'] = memory_get_usage(true);

        $peak_memory_usage = self::$memory_load['init_peak']['end'] - self::$memory_load['init_peak']['start'];
        $regular_memory_usage = self::$memory_load['init_regular']['end'] - self::$memory_load['init_regular']['start'];

        $total_queries = count(self::$sql_log);
        $average_query = $total_queries > 0 ? self::$sql_total_times / $total_queries : 0;

        $has_firebug = false;

        //Start output

        $output = '';

        $backendUrl = '/' . Core_String::normalizeUri(Phpr::$config->get('BACKEND_URL', 'backend'));
        $loginUrl = $backendUrl . 'session/handle/create';

        $currentUrl = isset(Phpr::$request->get_fields['q']) ? Phpr::$request->get_fields['q'] : '';

        $is_login = stristr($currentUrl, $loginUrl) === false ? false : true;
        $is_backend = stristr($currentUrl, $backendUrl) === false ? false : true;

        if( !$is_ajax ) {

            if( $is_backend ) {
                $output .= '<script type="text/javascript" src="'.root_url('/modules/cms/resources/javascript/jquery_src.js').'"></script>' . "\n";
            }

        	$output .= '<link rel="stylesheet" type="text/css" href="'.root_url('/modules/devel/resources/css/frontend.css').'" />' . "\n";

            if( $has_firebug ) {
                $output .= '<script src="https://getfirebug.com/firebug-lite.js#overrideConsole=true,startOpened=true"></script>';
            }

            $output .= '<script type="text/javascript" src="'.root_url('/modules/devel/resources/javascript/devel.js').'"></script>' . "\n";

            $output .= '<div id="devel-module" class="devel-module'.($is_backend ? ' devel-module-backend' : '').'">';
            $output .= '<div class="devel-module-wrapper">';

            $output .= '<div id="devel-log" class="devel-log"></div>';

        }

        //Start Javascript

        if( $is_backend ) {
            $output .= '<script type="text/javascript"> jQuery.noConflict(); </script>';
        }

        $output .= '<script type="text/javascript"> jQuery(document).ready(function($) { ' . "\n";

        //if( $has_firebug ) {
            //$output .= 'LSDevel.Logger.init({ hasFirebug: true });' . "\n";
        //}

        $output .= 'LSDevel.Logger.init();' . "\n";

        //Start Request

        $title = $is_ajax ? 'Ajax Page Request' : 'Page Request';
        $output .= 'LSDevel.Logger.startGroup("'.$title.': '.date("F j, Y, g:i a").'");' . "\n";


        //Start Page Information

        $output .= 'LSDevel.Logger.startGroup("Page Information");' . "\n";

        if( $real_page_load_time == 0 )
        {
            $output .= 'LSDevel.Logger.log("Assume Page Load Time: ' . number_format($page_load_time, 4) . ' s");' . "\n";

            $output .= 'LSDevel.Logger.log('. self::safe_parameter('This information is tracked after Lemonstand has intilized resulting in slightly different values. Please consider completing the installation part 2 <a href="http://forum.lemonstandapp.com/topic/1735-devel-module/" target="_blank">here</a> to track the real data.') . ', "info");' . "\n";
        }
        else
        {
            $output .= 'LSDevel.Logger.log("Page Load Time: ' . number_format($real_page_load_time, 4) . ' s");' . "\n";

            if( $boot_sequence > 0 ) {
                $output .= 'LSDevel.Logger.log(" + Boot Sequence: ' . number_format($boot_sequence, 4) . ' s");' . "\n";
            }

            if( $initialize_sequence > 0 ) {
                $output .= 'LSDevel.Logger.log(" + Init Sequence: ' . number_format($initialize_sequence, 4) . ' s");' . "\n";
            }

            if( $page_sequence > 0 ) {
                $output .= 'LSDevel.Logger.log(" + Page Sequence: ' . number_format($page_sequence, 4) . ' s");' . "\n";
            }

            //$output .= 'LSDevel.Logger.log(" + Total Queries: ' . number_format(self::$sql_total_times, 4) . ' s");' . "\n";
        }

        $output .= 'LSDevel.Logger.log("Peak Memory Usage: ' . number_format($peak_memory_usage/1024/1024) . ' MB (' .  number_format($peak_memory_usage/1024) . ' KB)");' . "\n";

        $output .= 'LSDevel.Logger.log("Memory Usage: ' . number_format($regular_memory_usage/1024/1024) . ' MB (' . number_format($regular_memory_usage/1024) . ' KB)");' . "\n";

        $output .= 'LSDevel.Logger.log("Total Queries: ' . number_format($total_queries) . '");' . "\n";

        $output .= 'LSDevel.Logger.log("Average Query Time: ' . number_format($average_query, 4) . ' s");' . "\n";

        $output .= 'LSDevel.Logger.log("Total Query Time: ' . number_format(self::$sql_total_times, 4) . ' s");' . "\n";

        $output .= 'LSDevel.Logger.endGroup();' . "\n";

        //End Page Information


        //Start Query Log

        $output .= 'LSDevel.Logger.startGroup("Query Log");' . "\n";

        $note = '<div class="page-query-color-sample query-log-low">&nbsp;</div>';
        $note .= '<div class="page-query-color-text">- Higher than average execution</div>';
        $note .= '<div class="page-query-color-sample query-log-high">&nbsp;</div>';
        $note .= '<div class="page-query-color-text">- Longer than 1 second</div>';
        $note .= '<div class="devel-clear"></div>';

        $output .= 'LSDevel.Logger.log('.self::safe_parameter($note).', "html");' . "\n";


        $output .= 'LSDevel.Logger.logQueryTable([' . "\n";
        $rid = 1;
        foreach(self::$sql_log as $querydata) {
            $key = $querydata['key'];
            $query_time = ( isset(self::$sql_load[$key]) && self::$sql_load[$key]['start'] > 0 && self::$sql_load[$key]['end'] > 0 ) ? self::$sql_load[$key]['end'] - self::$sql_load[$key]['start'] : -1;
            $time_str = ( $query_time > -1 ) ? number_format($query_time, 4) : '"N/A"';

            $priority = 1;
            if( $query_time > $average_query ) {
                $priority = 2;
            }

            $output .= '{ id: '.$rid.', sql: '.self::safe_parameter($querydata['sql']).', time: '.$time_str.', priority: '.$priority.' }, ' . "\n";
            $rid++;
        }
        if( $rid > 1 ) {
            $output = substr($output, 0, -2);
        }
        $output .= "\n" . ']);' . "\n";

        /*$rid = 1;
        foreach(self::$sql_log as $querydata) {
            $key = $querydata['key'];
            $query_time = ( isset(self::$sql_load[$key]) && self::$sql_load[$key]['start'] > 0 && self::$sql_load[$key]['end'] > 0 ) ? self::$sql_load[$key]['end'] - self::$sql_load[$key]['start'] : -1;
            $time_str = ( $query_time > -1 ) ? number_format($query_time, 4) : '"N/A"';

            $output .= 'LSDevel.Logger.logQuery("\('.$time_str.'\) '.self::safe_parameter($querydata['sql']).'");' . "\n";
            $rid++;
        }*/

        $output .= 'LSDevel.Logger.endGroup();' . "\n";

        //End Query Log


        /*$output .= '<div class="devel-heading">Lemonstand Settings</div>';

        $caching_params = Phpr::$config->get('CACHING', array());

        if( !$caching_params ) {
            $output .= '<div class="settings-cache-on">';
            $output .= 'Caching: Off';
            $output .= '</div>' . "\n";
        } else {
            $output .= '<div class="page-explain">Cache Settings</div>';

            $output .= '<div class="settings-cache-on">';
            $output .= 'Caching: On';
            $output .= '</div>' . "\n";

            if( in_array('CLASS_NAME', $caching_params) ) {
                $output .= '<div class="settings-cache-type">';
                $output .= 'Caching Type: ' . $caching_params['CLASS_NAME'];
                $output .= '</div>' . "\n";
            }
        }*/


        //Start Other Variables

        $output .= 'LSDevel.Logger.startGroup("Other Variables");' . "\n";

        $log = '<strong>GET Data</strong><div class="page-variables-content">' . self::recursive_print('Phpr::$request->get_fields', Phpr::$request->get_fields) . '</div>';
        $output .= 'LSDevel.Logger.log('.self::safe_parameter($log).', "html");' . "\n";

        $log = '<strong>POST Data</strong><div class="page-variables-content">' . self::recursive_print('$_POST', $_POST) . '</div>';
        $output .= 'LSDevel.Logger.log('.self::safe_parameter($log).', "html");' . "\n";

        $log = '<strong>SESSION Data</strong><div class="page-variables-content">' . self::recursive_print('$_SESSION', $_SESSION) . '</div>';
        $output .= 'LSDevel.Logger.log('.self::safe_parameter($log).', "html");' . "\n";

        $log = '<strong>COOKIE Data</strong><div class="page-variables-content">' . wordwrap(self::recursive_print('$_COOKIE', $_COOKIE), 115, "<br />", true) . '</div>';
        $output .= 'LSDevel.Logger.log('.self::safe_parameter($log).', "html");' . "\n";

        //$output .= '<div class="page-headers">';
        //$output .= '<strong>Headers</strong><div class="page-variables-content">' . self::recursive_print('getAllHeaders', getAllHeaders());
        //$output .= '</div></div>' . "\n";

        //$output .= '<div class="page-constants">';
        //$output .= '<strong>Defined Constants</strong><div class="page-variables-content">' . self::recursive_print('get_defined_constants', get_defined_constants());
        //$output .= '</div></div>' . "\n";

        $defined_func = get_defined_functions();
        $log = '<strong>Defined Functions</strong><div class="page-variables-content">' . self::recursive_print('get_defined_functions', $defined_func['user']) . '</div>';
        $output .= 'LSDevel.Logger.log('.self::safe_parameter($log).', "html");' . "\n";

        $log = '<strong>Include Files</strong><div class="page-variables-content">' . self::recursive_print('get_included_files', get_included_files()) . '</div>';
        $output .= 'LSDevel.Logger.log('.self::safe_parameter($log).', "html");' . "\n";

        //$output .= '<div class="page-interfaces">';
        //$output .= '<strong>Declared Interfaces</strong><div class="page-variables-content">' . self::recursive_print('get_declared_interfaces', get_declared_interfaces());
        //$output .= '</div></div>' . "\n";

        $log = '<strong>Declared Classes</strong><div class="page-variables-content">' . self::recursive_print('get_declared_classes', get_declared_classes()) . '</div>';
        $output .= 'LSDevel.Logger.log('.self::safe_parameter($log).', "html");' . "\n";

        $output .= 'LSDevel.Logger.endGroup();' . "\n";

        //End Other Variables


        //$output .= '<div class="devel-heading">Backtrace</div>';

        //$output .= '<div class="page-backtrace">';
        //$output .= '<strong>Declared Classes</strong><div class="page-variables-content">' . self::recursive_print('debug_backtrace', debug_backtrace());
        //$output .= '</div></div>' . "\n";


        $output .= 'LSDevel.Logger.endGroup();' . "\n";

        //End Page Request

        $output .= '}); </script>';

        if( !$is_ajax ) {
            $output .= '</div>'; //.devel-module-wrapper
            $output .= '</div>'; //.devel-module

        	$template_content = preg_replace(',\</body\>,i', $output.'</body>', $buffer, 1);
            return $template_content;
        } else {
        	//$template_content = preg_replace(',\</body\>,i', $output.'</body>', $buffer, 1);
            return $output;
        }
    }

    public static function recursive_print ($varname, $varval) {
        $output = '';

        if( is_array($varval) ) {
            $output .= $varname . " = array()<br>\n";
            foreach ($varval as $key => $val) {
                $output .= self::recursive_print($varname . "['" . $key . "']", $val);
            }
        } else if( is_object($varval) ) {
            $output .= $varname . " = object()<br>\n";
            $obj = get_object_vars($varval);
            foreach ($obj as $key => $val) {
                $output .= self::recursive_print($varname . "['" . $key . "']", $val);
            }
        } else {
            $output .= $varname . ' = ' . $varval . "<br>\n";
        }

        return $output;
    }

	public static function get_backtrace() {
		$trace  = array_reverse( debug_backtrace() );
		$caller = array();

		foreach($trace as $call) {
			if( isset( $call['class'] ) && __CLASS__ == $call['class'] )
				continue;
			//$caller[] = isset( $call['class'] ) ? "{$call['class']}->{$call['function']}" : $call['function'];
            $new = isset( $call['class'] ) ? "{$call['class']}->{$call['function']}" : $call['function'];
            $new .= '(' . (isset($call['file']) ? $call['file'] : '') . ':' . (isset($call['line']) ? $call['line'] : '') . ')';
            //$new .= '[' . (isset($call['args']) ? implode(",", $call['args']) : '') . ']';
            $caller[] = $new;
            //var_dump($call);
		}

		return join(', ', $caller);
	}

    public static function safe_parameter($str)
    {
        $result = $str;
        $result = preg_replace( '/\s+/', ' ', $result );
        $result = json_encode($result);
        return $result;
    }
}

?>