<?php
/*
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


// This class is for mails from the cloud

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
// special cloud classes
if(class_exists('cloudnat') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudnat.class.php";
}

$event = new event();
global $event;

class cloudmailer {

	var $to = '';
	var $from = '';
	var $subject = '';
	var $message = '';
	var $headers = '';
	var $template = '';
	var $var_array = '';


	// example
	// $test = new cloudmailer();
	// $test->to = "root@localhost";
	// $test->from = "root@localhost";
	// $test->subject = "testy";
	// $test->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/new_cloud_request.mail.tmpl";
	// $arr = array('@@USER@@'=>"supa", '@@ID@@'=>"2", '@@OPENQRM_IP@@'=>"192.168.88.10");
	// $test->var_array = $arr;
	// $test->send();




	// ---------------------------------------------------------------------------------
	// general cloudmailer methods
	// ---------------------------------------------------------------------------------

	// sends the mail
	function send() {
		global $event;
		$this->headers = "From: $this->from";
		if (file_exists($this->template)) {
			$this->message = file_get_contents($this->template);
		}
		$headers   = array();
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/plain; charset=utf-8";
		$headers[] = "Content-Transfer-Encoding: 8bit";
		$headers[] = "From: ".$this->from;
		$headers[] = "Reply-To: ".$this->from;
		$headers[] = "Subject: ".$this->subject;
		$headers[] = "X-Mailer: PHP/".phpversion();

		// check if we have to cloudnat the ip address
		$cn_conf = new cloudconfig();
		$cn_nat_enabled = $cn_conf->get_value(18);  // 18 is cloud_nat
		if (!strcmp($cn_nat_enabled, "true")) {
			$cloudnat = true;
		} else {
			$cloudnat = false;
		}
		// replace in template
		foreach ($this->var_array as $key => $value) {
			if ($cloudnat) {
				if (!strcmp($key, "@@IP@@")) {
					$cn = new cloudnat();
					$value = $cn->translate($value);
				}
			}
			$this->message = str_replace($key, $value, $this->message);
		}
		$this->message = wordwrap($this->message, 140);
		$res = mail($this->to, $this->subject, $this->message, implode("\r\n", $headers));
		if ($res) {
			$event->log("cloudmailer", $_SERVER['REQUEST_TIME'], 5, "cloudmailer.class.php", "Mail sent successfully  !", "", "", 0, 0, 0);
		} else {
			$event->log("cloudmailer", $_SERVER['REQUEST_TIME'], 1, "cloudmailer.class.php", "Could not sent mail !", "", "", 0, 0, 0);
		}

	}



// ---------------------------------------------------------------------------------

}

?>
