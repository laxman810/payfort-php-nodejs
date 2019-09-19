<?php

abstract class API {

    protected $method = '';
    protected $endpoint = '';
    protected $verb = '';
    protected $args = Array();
    protected $file = Null;
    protected $_xss_hash            = '';
    protected $_never_allowed_str = array(
        'document.cookie'   => '[removed]',
        'document.write'    => '[removed]',
        '.parentNode'       => '[removed]',
        '.innerHTML'        => '[removed]',
        'window.location'   => '[removed]',
        '-moz-binding'      => '[removed]',
        '<!--'              => '&lt;!--',
        '-->'               => '--&gt;',
        '<![CDATA['         => '&lt;![CDATA[',
        '<comment>'         => '&lt;comment&gt;'
    );
    protected $_never_allowed_regex = array(
        'javascript\s*:',
        'expression\s*(\(|&\#40;)', // CSS and IE
        'vbscript\s*:', // IE, surprise!
        'Redirect\s+302',
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
    );
    public function __construct($request_uri, $request) {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        $this->request = $request;
        $method_arg = explode('/', $request_uri); //rtrim($request_uri, '/'));
        $this->endpoint = end($method_arg); //array_shift($this->args);
        if (strncmp($this->endpoint, '_', 1) == 0) {
            return $this->_response($this->_requestStatus(400), 400);
        }
        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->verb = array_shift($this->args);
        }


        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }
            
        $request = $this->parseIncomingParams();
        
        switch ($this->method) {
            case 'DELETE':
            case 'POST':
                $this->args = $this->_cleanInputs($request);
                break;
            case 'GET':
                $this->args = $this->_cleanInputs($request);
                break;
            case 'PUT':
                $this->args = $this->_cleanInputs($request);
                $this->file = file_get_contents("php://input");
                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }

    public function processAPI() {
        if ((int) method_exists($this, $this->endpoint) > 0) {
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        return $this->_response($this->_requestStatus(400), 400);
    }

    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        header('Content-type: application/json');
        
        $data_new = $this->_return_response($data);
        return json_encode($data_new);
    }

    protected function _convert_attribute($match)
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }

    protected function _filter_attributes($str)
    {
        $out = '';

        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
        {
            foreach ($matches[0] as $match)
            {
                $out .= preg_replace("#/\*.*?\*/#s", '', $match);
            }
        }

        return $out;
    }

    protected function _decode_entity($match)
    {
        return $this->entity_decode($match[0], strtoupper('UTF-8'));
    }

    protected function _validate_entities($str)
    {

        $str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xss_hash()."\\1=\\2", $str);        
        $str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);
        $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);
        $str = str_replace($this->xss_hash(), '&', $str);
        return $str;
    }

    private function remove_invisible_characters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();        
        if ($url_encoded)
        {
            $non_displayables[] = '/%0[0-8bcef]/';  // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';   // url encoded 16-31
        }        
        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';   // 00-08, 11, 12, 14-31, 127
        do
        {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        }
        while ($count);
        return $str;
    }

    public function xss_hash()
    {
        if ($this->_xss_hash == '')
        {
            mt_srand();
            $this->_xss_hash = md5(time() + mt_rand(0, 1999999999));
        }

        return $this->_xss_hash;
    }


    protected function _do_never_allowed($str)
    {
        $str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

        foreach ($this->_never_allowed_regex as $regex)
        {
            $str = preg_replace('#'.$regex.'#is', '[removed]', $str);
        }

        return $str;
    }


    // ----------------------------------------------------------------

    /**
     * Compact Exploded Words
     *
     * Callback function for xss_clean() to remove whitespace from
     * things like j a v a s c r i p t
     *
     * @param   type
     * @return  type
     */
    protected function _compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
    }



    // --------------------------------------------------------------------

    /**
     * JS Link Removal
     *
     * Callback function for xss_clean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @param   array
     * @return  string
     */
    protected function _js_link_removal($match)
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
                '',
                $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]
        );
    }



    // --------------------------------------------------------------------

    /**
     * JS Image Removal
     *
     * Callback function for xss_clean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @param   array
     * @return  string
     */
    protected function _js_img_removal($match)
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
                '',
                $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]
        );
    }

    // --------------------------------------------------------------------

    /*
     * Remove Evil HTML Attributes (like evenhandlers and style)
     *
     * It removes the evil attribute and either:
     *  - Everything up until a space
     *      For example, everything between the pipes:
     *      <a |style=document.write('hello');alert('world');| class=link>
     *  - Everything inside the quotes
     *      For example, everything between the pipes:
     *      <a |style="document.write('hello'); alert('world');"| class="link">
     *
     * @param string $str The string to check
     * @param boolean $is_image TRUE if this is an image
     * @return string The string with the evil attributes removed
     */
    protected function _remove_evil_attributes($str, $is_image)
    {
        // All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
        $evil_attributes = array('on\w*', 'style', 'xmlns', 'formaction');

        if ($is_image === TRUE)
        {
            /*
             * Adobe Photoshop puts XML metadata into JFIF images, 
             * including namespacing, so we have to allow this for images.
             */
            unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
        }

        do {
            $count = 0;
            $attribs = array();

            // find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
            preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr)
            {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // find occurrences of illegal attribute strings without quotes
            preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr)
            {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // replace illegal attribute strings that are inside an html tag
            if (count($attribs) > 0)
            {
                $str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)('.implode('|', $attribs).')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
            }

        } while ($count);

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Sanitize Naughty HTML
     *
     * Callback function for xss_clean() to remove naughty HTML elements
     *
     * @param   array
     * @return  string
     */
    protected function _sanitize_naughty_html($matches)
    {
        // encode opening brace
        $str = '&lt;'.$matches[1].$matches[2].$matches[3];

        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace(array('>', '<'), array('&gt;', '&lt;'),
                            $matches[4]);

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * _cleanInputs
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission.  It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some code and ideas I
     * got from Bitflux: http://channel.bitflux.ch/wiki/XSS_Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     * http://ha.ckers.org/xss.html
     *
     * @param   mixed   string or array
     * @param   bool
     * @return  string
     */
    private function _cleanInputsx($str, $is_image = FALSE) {
            return $str;
    }
    private function _cleanInputs($str, $is_image = FALSE) {
        /*
         * Is the string an array?
         *
         */
        if (is_array($str))
        {
            while (list($key) = each($str))
            {
                $str[$key] = $this->_cleanInputs($str[$key]);
            }

            return $str;
        }

        /*
         * Remove Invisible Characters
         */
        $str = $this->remove_invisible_characters($str);

        // Validate Entities in URLs
        $str = $this->_validate_entities($str);

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         *
         */
        $str = rawurldecode($str);

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */

        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);

        $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_decode_entity'), $str);

        /*
         * Remove Invisible Characters Again!
         */
        $str = $this->remove_invisible_characters($str);

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja  vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on
         * large blocks of data, so we use str_replace.
         */

        if (strpos($str, "\t") !== FALSE)
        {
            $str = str_replace("\t", ' ', $str);
        }

        /*
         * Capture converted string for later comparison
         */
        $converted_string = $str;

        // Remove Strings that are never allowed
        $str = $this->_do_never_allowed($str);

        /*
         * Makes PHP tags safe
         *
         * Note: XML tags are inadvertently replaced too:
         *
         * <?xml
         *
         * But it doesn't seem to pose a problem.
         */
        if ($is_image === TRUE)
        {
            // Images have a tendency to have the PHP short opening and
            // closing tags every so often so we skip those and only
            // do the long opening tags.
            $str = preg_replace('/<\?(php)/i', "&lt;?\\1", $str);
        }
        else
        {
            $str = str_replace(array('<?', '?'.'>'),  array('&lt;?', '?&gt;'), $str);
        }

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         */
        $words = array(
            'javascript', 'expression', 'vbscript', 'script', 'base64',
            'applet', 'alert', 'document', 'write', 'cookie', 'window'
        );

        foreach ($words as $word)
        {
            $temp = '';

            for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++)
            {
                $temp .= substr($word, $i, 1)."\s*";
            }

            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
        }

        /*
         * Remove disallowed Javascript in links or img tags
         * We used to do some version comparisons and use of stripos for PHP5,
         * but it is dog slow compared to these simplified non-capturing
         * preg_match(), especially if the pattern exists in the string
         */
        do
        {
            $original = $str;

            if (preg_match("/<a/i", $str))
            {
                $str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array($this, '_js_link_removal'), $str);
            }

            if (preg_match("/<img/i", $str))
            {
                $str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array($this, '_js_img_removal'), $str);
            }

            if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str))
            {
                $str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
            }
        }
        while($original != $str);

        unset($original);

        // Remove evil attributes such as style, onclick and xmlns
        $str = $this->_remove_evil_attributes($str, $is_image);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         */
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed.  Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example: eval('some code')
         * Becomes:     eval&#40;'some code'&#41;
         */
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);


        // Final clean up
        // This adds a bit of extra precaution in case
        // something got through the above filters
        $str = $this->_do_never_allowed($str);

        /*
         * Images are Handled in a Special Way
         * - Essentially, we want to know that after all of the character
         * conversion is done whether any unwanted, likely XSS, code was found.
         * If not, we return TRUE, as the image is clean.
         * However, if the string post-conversion does not matched the
         * string post-removal of XSS, then it fails, as there was unwanted XSS
         * code found and removed/changed during processing.
         */

        if ($is_image === TRUE)
        {
            return ($str == $converted_string) ? TRUE: FALSE;
        } else{
            // HTML Injection filteration
            $str = htmlspecialchars($str, ENT_QUOTES); 
        }

        return $str;
    }
    
    
    private function parseIncomingParams() {
        $parameters = array();

        // first of all, pull the GET vars
//        if (isset($_SERVER['QUERY_STRING'])) {
//            parse_str($_SERVER['QUERY_STRING'], $parameters);
//        }
        // now how about PUT/POST bodies? These override what we got from GET
        $body = file_get_contents("php://input");
        $content_type = false;
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $content_type = $_SERVER['CONTENT_TYPE'];
        }
        switch ($content_type) {
            case "application/json":
                $body_params = json_decode($body);
                if ($body_params) {
                    foreach ($body_params as $param_name => $param_value) {
                        $parameters[$param_name] = $param_value;
                    }
                }
                $this->format = "json";
                break;
            case "application/json; charset=utf-8":
                $body_params = json_decode($body);
                if ($body_params) {
                    foreach ($body_params as $param_name => $param_value) {
                        $parameters[$param_name] = $param_value;
                    }
                }
                $this->format = "json";
                break;
            case "application/x-www-form-urlencoded":
                parse_str($body, $postvars);
                foreach ($postvars as $field => $value) {
                    $parameters[$field] = $value;
                }
                $this->format = "html";
                break;
            default:
                // we could parse other supported formats here
                break;
        }
        return $parameters;
    }

    

    private function _requestStatus($code) {
        $status = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported');
        return ($status[$code]) ? $status[$code] : $status[500];
    }

    /*
     * Method name: _return_response
     * Desc: returns response with out any null values
     * Input: Array with null
     * Output:  returns array by making nulls as empty
     */

    private function _return_response($arr) {

        $ret_arr = array();

        foreach ($arr as $ind => $obj)
            if (is_array($arr[$ind]))
                $ret_arr[$ind] = $this->_return_response($obj);
            else if ($obj === NULL)
                $ret_arr[$ind] = '';
            else
                $ret_arr[$ind] = $obj;

        return $ret_arr;
    }

}

?>
