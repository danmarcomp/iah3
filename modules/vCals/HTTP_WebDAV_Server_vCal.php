<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
 /*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/


require_once 'include/utils.php';
require_once 'modules/Users/User.php';
require_once 'modules/Calendar/Calendar.php';
require_once 'modules/vCals/vCal.php';
require_once 'include/HTTP_WebDAV_Server/Server.php';

    
    /**
     * Filesystem access using WebDAV
     *
     * @access public
     */
    class HTTP_WebDAV_Server_vCal extends HTTP_WebDAV_Server 
    {
        /**
         * Root directory for WebDAV access
         *
         * Defaults to webserver document root (set by ServeRequest)
         *
         * @access private
         * @var    string
         */
        var $base = "";
        var $vcal_focus;
        var $vcal_type = "";
        var $source = "";
        var $publish_key = "";

        function HTTP_WebDAV_Server_vCal()
        {
           $this->vcal_focus = new vCal();
           $this->user_focus = new User();
        }


        /**
         * Serve a webdav request
         *
         * @access public
         * @param  string  
         */
        function ServeRequest($base = false) 
        {

            global $current_language;

			$sess_name = AppConfig::setting('site.session.name');
			if($sess_name)
				session_name($sess_name);
			$sess_path = AppConfig::setting('site.session.dir');
            if($sess_path) 
               session_save_path($sess_path);

            session_start();

            // clean_incoming_data();

            $current_language = AppConfig::setting('locale.defaults.language');

            // special treatment for litmus compliance test
            // reply on its identifier header
            // not needed for the test itself but eases debugging
/*
            foreach(apache_request_headers() as $key => $value) {
                if(stristr($key,"litmus")) {
                    error_log("Litmus test $value");
                    header("X-Litmus-reply: ".$value);
                }
            }
*/

            // set root directory, defaults to webserver document root if not set
            if ($base) { 
                $this->base = realpath($base); // TODO throw if not a directory
            } else if(!$this->base) {
                $this->base = $_SERVER['DOCUMENT_ROOT'];
            }


            $query_arr =  array();
             // set path
            if ( empty($_SERVER["PATH_INFO"]))
            {
				$this->path = "/";
				if(strtolower($_SERVER["REQUEST_METHOD"]) == 'get'){
					$query_arr = $_REQUEST;
				}else{
					parse_str($_REQUEST['parms'],$query_arr);
				}
            } else{
              $this->path = $this->_urldecode( $_SERVER["PATH_INFO"]);

              if(ini_get("magic_quotes_gpc")) {
               $this->path = stripslashes($this->path);
              }

              $query_str = preg_replace('/^\//','',$this->path);
              $query_arr =  array();
              parse_str($query_str,$query_arr);
            }


            if ( ! empty($query_arr['type']))
            {
              $this->vcal_type = $query_arr['type'];
            }
            else {
              $this->vcal_type = 'vfb';
            }

            if ( ! empty($query_arr['source']))
            {
              $this->source = $query_arr['source'];
            }
            else {
              $this->source = 'outlook';
            }

            if ( ! empty($query_arr['key']))
            {
              $this->publish_key = $query_arr['key'];
            }

            // select user by email
            if ( ! empty($query_arr['email']))
            {
              // clean the string!
              $query_arr['email'] = clean_string($query_arr['email']);

              //get user info
              $arr = array('email1'=>$query_arr['email']);
              $this->user_focus->retrieve_by_string_fields($arr);
            }
            // else select user by user_name
            else if ( ! empty($query_arr['user_name']))
            {
              // clean the string!
              $query_arr['user_name'] = clean_string($query_arr['user_name']);

              //get user info
              $arr = array('user_name'=>$query_arr['user_name']);
              $this->user_focus->retrieve_by_string_fields($arr);
            }
            // else select user by user id
            else if ( ! empty($query_arr['user_id']))
            {
                $this->user_focus->retrieve($query_arr['user_id']);
            }

            // if we haven't found a user, then return 404
            if ( empty($this->user_focus->id) || $this->user_focus->id == -1 || $this->user_focus->status == 'Inactive')
            {
                $this->http_status("404 Not Found");
                return;
            }

//            if(empty($this->user_focus->user_preferences))
//            {
                     $this->user_focus->loadPreferences();
//            }
                
            // let the base class do all the work
            parent::ServeRequest();
        }

        /**
         * No authentication is needed here
         *
         * @access private
         * @param  string  HTTP Authentication type (Basic, Digest, ...)
         * @param  string  Username
         * @param  string  Password
         * @return bool    true on successful authentication
         */
        function check_auth($type, $user, $pass) 
        {
            return true;
        }


        function GET()
        {
            return true;
        }

        // {{{ http_GET()

        /**
        * GET method handler
        *
        * @param void
        * @returns void
        */
        function http_GET()
        {
			 if ( $this->user_focus->getPreference('calendar_publish_key') && 
				$this->publish_key != $this->user_focus->getPreference('calendar_publish_key' ))
			 {
					$this->http_status("401 Not Authorized");
            		//header('WWW-Authenticate: Basic realm="info@hand iCal"');
					echo 'Authorization required';
					return;
			 }

           if ($this->vcal_type == 'vfb')
           {
             $this->http_status("200 OK");
             echo vCal::get_combined_freebusy($this->user_focus->id);
           } else if ($this->vcal_type == 'ics') 
           {
             $this->http_status("200 OK");
             header('Content-Disposition: attachment; filename="calendar.ics"');
             header('Content-Type: text/calendar; charset=utf-8');
             echo($this->vcal_focus->get_vcal_ical($this->user_focus));
           } else {
             $this->http_status("404 Not Found");
           }

        }
        // }}}


        // {{{ http_PUT()

        /**
        * PUT method handler
        *
        * @param  void
        * @return void
        */
        function http_PUT()
        {
            $options = Array();
            $options["path"] = $this->path;
            $options["content_length"] = $_SERVER["CONTENT_LENGTH"];

            // get the Content-type
            if (isset($_SERVER["CONTENT_TYPE"])) {
                // for now we do not support any sort of multipart requests
                if (!strncmp($_SERVER["CONTENT_TYPE"], "multipart/", 10)) {
                    $this->http_status("501 not implemented");
                    echo "The service does not support mulipart PUT requests";
                    return;
                }
                $options["content_type"] = $_SERVER["CONTENT_TYPE"];
            } else {
                // default content type if none given
                $options["content_type"] = "application/octet-stream";
            }

            /* RFC 2616 2.6 says: "The recipient of the entity MUST NOT
               ignore any Content-* (e.g. Content-Range) headers that it
               does not understand or implement and MUST return a 501
               (Not Implemented) response in such cases."
            */
            foreach ($_SERVER as $key => $val) {
                if (strncmp($key, "HTTP_CONTENT", 11)) continue;
                switch ($key) {
                case 'HTTP_CONTENT_ENCODING': // RFC 2616 14.11
                    // TODO support this if ext/zlib filters are available
                    $this->http_status("501 not implemented");
                    echo "The service does not support '$val' content encoding";
                    return;

                case 'HTTP_CONTENT_LANGUAGE': // RFC 2616 14.12
                    // we assume it is not critical if this one is ignored
                    // in the actual PUT implementation ...
                    $options["content_language"] = $val;
                    break;

                case 'HTTP_CONTENT_LOCATION': // RFC 2616 14.14
                    /* The meaning of the Content-Location header in PUT
                       or POST requests is undefined; servers are free
                       to ignore it in those cases. */
                    break;

                case 'HTTP_CONTENT_RANGE':    // RFC 2616 14.16
                    // single byte range requests are NOT supported
                    // the header format is also specified in RFC 2616 14.16
                    // TODO we have to ensure that implementations support this or send 501 instead
                        $this->http_status("400 bad request");
                        echo "The service does only support single byte ranges";
                        return;

                case 'HTTP_CONTENT_MD5':      // RFC 2616 14.15
                    // TODO: maybe we can just pretend here?
                    $this->http_status("501 not implemented");
                    echo "The service does not support content MD5 checksum verification";
                    return;

				case 'HTTP_CONTENT_LENGTH': // RFC 2616 14.14
                    /* The meaning of the Content-Location header in PUT
                       or POST requests is undefined; servers are free
                       to ignore it in those cases. */
                    break;

                default:
                    // any other unknown Content-* headers
                    $this->http_status("501 not implemented");
                    echo "The service does not support '$key'";
                    return;
                }
            }

            // DO AUTHORIZATION for publishing Free/busy to Sugar:
            if ( $this->user_focus->getPreference('calendar_publish_key') && 
                $this->publish_key != $this->user_focus->getPreference('calendar_publish_key' ))
            {
                    $this->http_status("401 not authorized");
                    return;
                
            }

            // retrieve
            $arr = array('user_id'=>$this->user_focus->id,'type'=>'vfb','source'=>$this->source);
            $this->vcal_focus->retrieve_by_string_fields($arr); 

            $isUpdate  = false;

            if ( ! empty($this->vcal_focus->user_id ) && 
                $this->vcal_focus->user_id != -1 ) 
            {
              $isUpdate  = true;
            }

            // open input stream
            $options["stream"] = fopen("php://input", "r");
            $content = '';

            // read in input stream
            while (!feof($options["stream"])) 
            {
               $content .= fread($options["stream"], 4096);
            }

            // set freebusy members and save
            $this->vcal_focus->content = $content;
            $this->vcal_focus->type = 'vfb';
            $this->vcal_focus->source = $this->source;
            $this->vcal_focus->date_modified = null;
            $this->vcal_focus->user_id = $this->user_focus->id;
            $this->vcal_focus->save();

            if ( $isUpdate )
            {
               $this->http_status("204 No Content");
            } else {
               $this->http_status("201 Created");
            }
        }

        /**
         * PUT method handler
         * 
         * @param  array  parameter passing array
         * @return bool   true on success
         */
        function PUT(&$options) 
        {

        }

        /**
         * LOCK method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function lock(&$options)
        {

            $options["timeout"] = time()+300; // 5min. hardcoded
            return true;
        }

        /**
         * UNLOCK method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function unlock(&$options)
        {

            return "200 OK";
        }


        /**
         * checkLock() helper
         *
         * @param  string resource path to check for locks
         * @return bool   true on success
         */
        function checkLock($path)
        {
            return false;

        }

    }
?>