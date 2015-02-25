<?php

/*
  _ +----------------------------------------------+ _
  /o)|          Created By Pham Dinh Cuong          |(o\
  / / |   Skype: phamcuongt2 - Mobile: 0986 835 651  | \ \
  ( (_ |  _       Email: phamcuongt2@gmail.com     _  | _) )
  ((\ \)+-/o)--------------------------------------(o\-+(/ /))
  (\\\ \_/ /                                        \ \_/ ///)
  \      /                                          \      /
  \____/
 */
// Load Simple Html Dom And Random User Agent in CURL
require_once 'simple_html_dom.php';

class HtmlDom {

    static public function getUrl($url) {
        $html_dom = new html_dom();
        if( !($dom = $html_dom->Connect($url)) ) {
            $read_url_content = file_get_contents($url);
            $dom = str_get_html($read_url_content);
        }
        return $dom;
    }

}

class html_dom extends simple_html_dom {

    private $_error = array();
    protected $_response = null;
    protected $cookie_file = 'cookie.txt';

    function user_agent() {
        require_once 'random_user_agent.php';
        return random_user_agent();
    }

    //Connect To Url and return Dom if Connect Oke
    function Connect($url, $reffer = '', $post = '', $cookie = '') {
        if( $this->_Exec($url, $reffer, $post, $cookie) === false ) {
            return false;
        }
        return $this->_Parser();
    }

    //Download as html content
    function Download($url, $file, $reffer = '', $cookie = '') {
        return $this->_Download($url, $file, $reffer, $cookie);
    }

    //View As Web
    function DisplayHTML() {
        echo $this->outertext;
    }

    // Debug
    function SetError($error) {
        if( empty($error) ) {
            return;
        }

        $time = date('H:i:s d-m-Y');
        if( is_string($error) ) {
            $this->_error[$time] = $error;
        } else {
            $rs = $this->GetArray($error);
            foreach( $rs as $e ) {
                $this->_error[$time] = $e['text'];
            }
        }
    }

    function Error() {
        echo "<p><div><strong>Having Error:</strong></div>";
        foreach( $this->_error as $t => $e ) {
            echo "<div> $e  ( at $t )</div>";
        }
        echo '</p>';
    }

    // Exec Using CURL
    private function _Exec($url, $reffer = '', $post = '', $cookie = '') {
        if( !$cookie ) {
            $cookie = $this->cookie_file;
        }
        $ch = curl_init();
        // Set so curl_exec returns the result instead of outputting it.
        //curl_setopt($ch, CURLOPT_HEADER, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // Cookies file
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent());
        // Post data to server
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8,gzip,deflate'); // Get Content Utf-8
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_REFERER, $reffer);

        if( $post != '' ) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        // Get the response
        $this->_response = curl_exec($ch);
        if( curl_error($ch) ) {
            $this->SetError('Error ' . curl_errno($ch) . ': ' . curl_error($ch));
        }
        //echo $this->_response;

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if( $httpCode == 404 ) {
            $this->_response = false;
        }

        // Close curl handle
        curl_close($ch);

        return $this->_response;
    }

    // Download Page Using Curl
    private function _Download($url, $file, $reffer = '', $cookie = '') {
        if( !$cookie ) {
            $cookie = $this->cookie_file;
        }
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent());

        // Cookies file
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);

        // Post data to server
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8,gzip,deflate'); // Get Content Utf-8
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_REFERER, $reffer);

        // Luu noi dung vao file
        if( ($fp = fopen($file, "wb")) === false ) {
            $this->SetError('Error filename = ' . $file);
            curl_close($ch);
            return false;
        }
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        if( curl_exec($ch) === false ) {
            fclose($fp);
            unlink($file);
            $this->SetError('Error url = ' . $url);
            curl_close($ch);
            return false;
        }

        fclose($fp);
        curl_close($ch);
        return true;
    }

    private function _Parser($html = null) {
        if( $html == null ) {
            $html = $this->_response;
        }
        if( empty($html) ) {
            $this->SetError('Error html empty');
            return false;
        }

        $this->load($html);
        return $this;
    }

}
