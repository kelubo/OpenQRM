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


error_reporting(E_ALL);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
$CloudDir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/';
require_once "$RootDir/plugins/cloud/class/cloudsoapadmin.class.php";

// turn off the wsdl cache
ini_set("soap.wsdl_cache_enabled", "0");
ini_set("session.auto_start", 0);

//for persistent session
session_start();

//service
$ws = "./cloudadmin.wdsl";
$server = new SoapServer($ws);

// set class to use
$server->setClass("cloudsoapadmin");


// make persistant
$server->setPersistence(SOAP_PERSISTENCE_SESSION);

$server->handle();

?>

