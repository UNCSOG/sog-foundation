<?php

/**
 * Mail Handler Class
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/classes/common/Mail_Handler.php $
 * $LastChangedDate: 2015-03-26 14:45:38 +0100 (gio, 26 mar 2015) $
 * $LastChangedRevision: 32662 $
 * $LastChangedBy: francesco $
 *
 */
class CRED_Mail_Handler {

    private $_isHtml = false;
    private $_doFilter = false;
    private $_contentType = false;
    private $_to = array();
    private $_cc = array();
    private $_bcc = array();
    private $_from = array();
    private $_hasFrom = false;
    private $_headers = array();
    private $_attachments = array();
    private $_body = '';
    private $_subject = '';
    private $_formid = '';
    private $_postid = '';
    private $_notnum = 0;
    private $_notname = "";

    public function __construct() {
        $this->reset();
    }

    public function setHTML($enable, $doFilter = true) {
        $this->_isHtml = $enable;

        if ($this->_isHtml)
            $this->_doFilter = $doFilter;
        else
            $this->_doFilter = false;

        // chainable
        return $this;
    }

    //callback function for the regex
    public static function utf8_entity_decode($entity) {
        $convmap = array(0x0, 0x10000, 0, 0xfffff);
        return mb_decode_numericentity($entity, $convmap, 'UTF-8');
    }

    public function setSubject($sub = '') {

        //decode decimal HTML entities added by web browser
        $sub = preg_replace_callback(
                '/&#\d{2,5};/u', "utf8ed", $sub
        );

        //decode hex HTML entities added by web browser
        $sub = preg_replace_callback(
                '/&#x([a-fA-F0-7]{2,8});/u', "utf8ed_with_value", $sub
        );
        //set subject
        $this->_subject = $sub;
        // chainable
        return $this;
    }

    public function setBody($body = '') {
        $this->_body = $body;
        // chainable
        return $this;
    }

    public function addHeader($hdr) {
        $this->_headers[] = $hdr;
        // chainable
        return $this;
    }

    /* public function setAttachment($attach)
      {
      } */

    public function setFrom($_from = array()) {
        if (is_array($_from) && !empty($_from)) {
            $this->_from = $_from;
            $this->_hasFrom = true;
        } else {
            $this->_from = array();
            $this->_hasFrom = false;
        }
        // chainable
        return $this;
    }

    public function addAddress($addr) {
        $this->_to[] = $addr;
        // chainable
        return $this;
    }

    public function addRecipients($addresses) {
        if (!is_array($addresses))
            $addresses = explode(',', $addresses);

        foreach ((array) $addresses as $address) {
            $a = explode(':', $address);
            if (isset($a[1])) {
                $to = strtolower(trim($a[0]));
                $a = trim($a[1]);
            } else {
                $to = 'to';
                $a = trim($address);
            }

            switch ($to) {
                case 'to':
                    $this->_to[] = $a;
                    break;
                case 'cc':
                    $this->_cc[] = $a;
                    break;
                case 'bcc':
                    $this->_bcc[] = $a;
                    break;
            }
        }
        // chainable
        return $this;
    }

    protected function filter($t) {
        return wpautop($t);
    }

    protected function buildMail() {
        // build header
        $header = array();
        $this->_contentType = ($this->_isHtml) ? "Content-Type: text/html" : "Content-Type: text/plain";
        $header = array_merge($header, $this->_headers);
        $header = array_merge($header, array($this->_contentType));
        $header = apply_filters('cred_mail_header', $header, $this->_formid, $this->_postid, $this->_notname, $this->_notnum);

        if (!empty($this->_cc)) {
            $header = array_merge($header, array("Cc: " . implode(',', $this->_cc)));
        }
        if (!empty($this->_bcc)) {
            $header = array_merge($header, array("Bcc: " . implode(',', $this->_bcc)));
        }

        // build subject
        $subject = $this->_subject;

        // build body
        $body = ($this->_doFilter) ? $this->filter($this->_body) : $this->_body;

        // build recipient addresses
        $to = $this->_to; //implode(',',$this->_to);

        return array('to' => $to, 'subject' => $subject, 'body' => $body, 'header' => $header);
    }

    public function reset() {
        $this->_to = array();
        $this->_cc = array();
        $this->_bcc = array();
        $this->_from = array();
        $this->_hasFrom = false;
        $this->_headers = array();
        $this->_attachments = array();
        $this->_body = '';
        $this->_subject = '';
        $this->setHTML(false);
        // chainable
        return $this;
    }

    public function send() {
        $data = $this->buildMail();
        extract($data);

        if (count($to) == 0)
            return false;

        if ($this->_hasFrom) {
            add_filter('wp_mail_from', array(&$this, 'onMailFromFilter'), 10, 1);
            add_filter('wp_mail_from_name', array(&$this, 'onMailFromNameFilter'), 10, 1);
        }
        if ($this->_isHtml) {
            add_filter('wp_mail_content_type', array(&$this, 'set_html_content_type'), 10, 1);
        }

        /**
         * create global $phpmailer to setup empty body
         */
        global $phpmailer;
        // (Re)create it, if it's gone missing
		if (
			! is_object( $phpmailer ) ||
			! (
                is_a( $phpmailer, 'PHPMailer' ) ||
                is_a( $phpmailer, '\PHPMailer\PHPMailer\PHPMailer' )
            )
		) {
			if ( file_exists( ABSPATH . WPINC . '/PHPMailer/SMTP.php' ) ) {
				require_once ABSPATH . WPINC .'/PHPMailer/SMTP.php';
			} elseif ( file_exists( ABSPATH . WPINC . '/class-smtp.php' ) ) {
				require_once ABSPATH . WPINC . '/class-smtp.php';
			} else {
				throw new Exception( 'Failed to load the "PHPMailer\PHPMailer\SMTP" class.' );
			}

			if ( file_exists( ABSPATH . WPINC . '/PHPMailer/Exception.php' ) ) {
				require_once ABSPATH . WPINC .'/PHPMailer/Exception.php';
			}

			if ( file_exists( ABSPATH . WPINC . '/PHPMailer/PHPMailer.php' ) ) {
				require_once ABSPATH . WPINC .'/PHPMailer/PHPMailer.php';
				$phpmailer = new \PHPMailer\PHPMailer\PHPMailer( true );
			} elseif ( file_exists( ABSPATH . WPINC . '/class-phpmailer.php' ) ) {
				require_once ABSPATH . WPINC . '/class-phpmailer.php';
				$phpmailer = new PHPMailer( true );
			} else {
				throw new Exception( 'Failed to load the "PHPMailer\PHPMailer\PHPMailer" class.' );
			}
        }
        $phpmailer->AllowEmpty = true;

        $isSend = wp_mail($to, $subject, $body, $header);
        if (!$isSend) {
            $pattern = "/[^@\s]*@[^@\s]*\.[^@\s]*/";
            $replacement = "[removed]";
            $isSend = preg_replace($pattern, $replacement, $GLOBALS['phpmailer']->ErrorInfo);
        }
        //$isSend=true;

        if ($this->_hasFrom) {
            remove_filter('wp_mail_from_name', array(&$this, 'onMailFromNameFilter'), 10, 1);
            remove_filter('wp_mail_from', array(&$this, 'onMailFromFilter'), 10, 1);
        }

        if ($this->_isHtml) {
            remove_filter('wp_mail_content_type', array(&$this, 'set_html_content_type'), 10, 1);
        }
        return $isSend;
    }

    // new email-adress
    public function onMailFromFilter($email) {
        if (isset($this->_from['address']))
            $email = is_email($this->_from['address']);
        return $email;
    }

    // new name
    public function onMailFromNameFilter($name) {
        if (isset($this->_from['name']))
            $name = sanitize_text_field($this->_from['name']);
        return $name;
    }

    public function set_html_content_type($type) {
        return 'text/html';
    }

    public function setPostId($postid = '') {
        $this->_postid = $postid;
        // chainable
        return $this;
    }

    public function setFormId($formid = '') {
        $this->_formid = $formid;
        // chainable
        return $this;
    }

    public function setNotificationNum($notnum = '') {
        $this->_notnum = $notnum;
        // chainable
        return $this;
    }
    public function setNotificationName($notname = '') {
        $this->_notname = $notname;
        // chainable
        return $this;
    }

}
