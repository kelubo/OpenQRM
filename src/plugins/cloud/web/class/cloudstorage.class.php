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


// This class represents a storage in the cloud of openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
// special cloud classes
if(class_exists('clouduser') === false) {
	require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
}
if(class_exists('cloudusergroup') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudusergroup.class.php";
}
if(class_exists('cloudconfig') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
}
if(class_exists('cloudimage') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudimage.class.php";
}
if(class_exists('cloudrespool') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudrespool.class.php";
}

$event = new event();
global $event;

global $OPENQRM_SERVER_BASE_DIR;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $RESOURCE_INFO_TABLE;


class cloudstorage {

	var $appliance_id = '';
	var $timeout = '';


	function init($timeout) {
		$this->resource_id=0;
		$this->timeout=$timeout;
	}

// ---------------------------------------------------------------------------------
// general cloudstorage methods
// ---------------------------------------------------------------------------------


	// clones the volume of an image
	function create_clone($cloud_image_id, $image_clone_name, $disk_size, $timeout) {
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;
		global $RESOURCE_INFO_TABLE;
		global $RootDir;
		$this->init($timeout);
		global $event;
		$event->log("create_clone", $_SERVER['REQUEST_TIME'], 5, "cloudstorage.class", "Creating clone of image on storage", "", "", 0, 0, 0);
		// we got the cloudimage id here, get the image out of it
		$cloudimage = new cloudimage();
		$cloudimage->get_instance_by_id($cloud_image_id);
		// get image
		$image = new image();
		$image->get_instance_by_id($cloudimage->image_id);
		$image_id = $image->id;
		$image_name = $image->name;
		$image_type = $image->type;
		$image_version = $image->version;
		$image_rootdevice = $image->rootdevice;
		$image_rootfstype = $image->rootfstype;
		$image_storageid = $image->storageid;
		$image_isshared = $image->isshared;
		$image_comment = $image->comment;
		$image_capabilities = $image->capabilities;
		$image_deployment_parameter = $image->deployment_parameter;
		// get image storage
		$storage = new storage();
		$storage->get_instance_by_id($image_storageid);
		$storage_resource_id = $storage->resource_id;
		// get storage resource
		$resource = new resource();
		$resource->get_instance_by_id($storage_resource_id);
		$resource_id = $resource->id;
		$resource_ip = $resource->ip;
		// set default snapshot size
		if (!strlen($disk_size)) {
			$disk_size=5000;
		}

		// plug-in the specific storage methods
		$deployment = new deployment();
		$deployment->get_instance_by_type($image->type);
		$deployment_type = $deployment->type;
		$deployment_plugin_name = $deployment->storagetype;
		$storage_cloud_hook = "$RootDir/plugins/$deployment_plugin_name/openqrm-$deployment_type-cloud-hook.php";
		if (file_exists($storage_cloud_hook)) {
			$event->log("create_clone", $_SERVER['REQUEST_TIME'], 5, "cloudstorage.class", "Found plugin $deployment_plugin_name to clone $image_name type $deployment_type .", "", "", 0, 0, $resource->id);
			require_once "$storage_cloud_hook";
			$storage_method="create_clone_"."$deployment_type";
			$storage_method=str_replace("-", "_", $storage_method);
			$storage_method($cloud_image_id, $image_clone_name, $disk_size);
		} else {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloustorage.class", "Do not know how to clone the image from type $image_type.", "", "", 0, 0, 0);
		}

	}



	// removes the volume of an image
	function remove($cloud_image_id, $timeout) {
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;
		global $RESOURCE_INFO_TABLE;
		global $RootDir;
		$this->init($timeout);
		global $event;
		$event->log("remove", $_SERVER['REQUEST_TIME'], 5, "cloudstorage.class", "Removing image on storage", "", "", 0, 0, 0);

		$cloudimage = new cloudimage();
		$cloudimage->get_instance_by_id($cloud_image_id);
		// get image
		$image = new image();
		$image->get_instance_by_id($cloudimage->image_id);
		$image_id = $image->id;
		$image_name = $image->name;
		$image_type = $image->type;
		$image_version = $image->version;
		$image_rootdevice = $image->rootdevice;
		$image_rootfstype = $image->rootfstype;
		$image_storageid = $image->storageid;
		$image_isshared = $image->isshared;
		$image_comment = $image->comment;
		$image_capabilities = $image->capabilities;
		$image_deployment_parameter = $image->deployment_parameter;

		// get image storage
		$storage = new storage();
		$storage->get_instance_by_id($image_storageid);
		$storage_resource_id = $storage->resource_id;
		// get storage resource
		$resource = new resource();
		$resource->get_instance_by_id($storage_resource_id);
		$resource_id = $resource->id;
		$resource_ip = $resource->ip;

		// plug-in the specific storage methods
		$deployment = new deployment();
		$deployment->get_instance_by_type($image->type);
		$deployment_type = $deployment->type;
		$deployment_plugin_name = $deployment->storagetype;
		$storage_cloud_hook = "$RootDir/plugins/$deployment_plugin_name/openqrm-$deployment_type-cloud-hook.php";
		if (file_exists($storage_cloud_hook)) {
			$event->log("remove", $_SERVER['REQUEST_TIME'], 5, "cloudstorage.class", "Found plugin $deployment_plugin_name to remove $image_name type $deployment_type .", "", "", 0, 0, $resource->id);
			require_once "$storage_cloud_hook";
			$storage_method="remove_"."$deployment_type";
			$storage_method=str_replace("-", "_", $storage_method);
			$storage_method($cloud_image_id);
		// not supported yet
		} else {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloustorage.class", "Do not know how to remove clone from image type $image_type.", "", "", 0, 0, 0);
		}



	}


	// resizes the volume of an image
	function resize($cloud_image_id, $resize_value, $timeout) {
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;
		global $RESOURCE_INFO_TABLE;
		global $RootDir;
		$this->init($timeout);
		global $event;
		$event->log("resize", $_SERVER['REQUEST_TIME'], 5, "cloudstorage.class", "Resize image on storage", "", "", 0, 0, 0);

		$cloudimage = new cloudimage();
		$cloudimage->get_instance_by_id($cloud_image_id);
		// get image
		$image = new image();
		$image->get_instance_by_id($cloudimage->image_id);
		$image_id = $image->id;
		$image_name = $image->name;
		$image_type = $image->type;
		$image_version = $image->version;
		$image_rootdevice = $image->rootdevice;
		$image_rootfstype = $image->rootfstype;
		$image_storageid = $image->storageid;
		$image_isshared = $image->isshared;
		$image_comment = $image->comment;
		$image_capabilities = $image->capabilities;
		$image_deployment_parameter = $image->deployment_parameter;

		// get image storage
		$storage = new storage();
		$storage->get_instance_by_id($image_storageid);
		$storage_resource_id = $storage->resource_id;
		// get storage resource
		$resource = new resource();
		$resource->get_instance_by_id($storage_resource_id);
		$resource_id = $resource->id;
		$resource_ip = $resource->ip;

		// plug-in the specific storage methods
		$deployment = new deployment();
		$deployment->get_instance_by_type($image->type);
		$deployment_type = $deployment->type;
		$deployment_plugin_name = $deployment->storagetype;
		$storage_cloud_hook = "$RootDir/plugins/$deployment_plugin_name/openqrm-$deployment_type-cloud-hook.php";
		if (file_exists($storage_cloud_hook)) {
			$event->log("resize", $_SERVER['REQUEST_TIME'], 5, "cloudstorage.class", "Found plugin $deployment_plugin_name to resize $image_name type $deployment_type .", "", "", 0, 0, $resource->id);
			require_once "$storage_cloud_hook";
			$storage_method="resize_"."$deployment_type";
			$storage_method=str_replace("-", "_", $storage_method);
			$storage_method($cloud_image_id, $resize_value);
		// not supported yet
		} else {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloustorage.class", "Do not know how to resize image type $image_type.", "", "", 0, 0, 0);
		}

	}



	// creates a private copy of the volume of an image
	function create_private($cloud_image_id, $private_disk, $private_image_name, $timeout) {
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;
		global $RESOURCE_INFO_TABLE;
		global $RootDir;
		$this->init($timeout);
		global $event;
		$private_success = false;
		$event->log("create_private", $_SERVER['REQUEST_TIME'], 5, "cloudstorage.class", "Creating private image on storage", "", "", 0, 0, 0);

		$cloudimage = new cloudimage();
		$cloudimage->get_instance_by_id($cloud_image_id);
		// get image
		$image = new image();
		$image->get_instance_by_id($cloudimage->image_id);
		$image_id = $image->id;
		$image_name = $image->name;
		$image_type = $image->type;
		$image_version = $image->version;
		$image_rootdevice = $image->rootdevice;
		$image_rootfstype = $image->rootfstype;
		$image_storageid = $image->storageid;
		$image_isshared = $image->isshared;
		$image_comment = $image->comment;
		$image_capabilities = $image->capabilities;
		$image_deployment_parameter = $image->deployment_parameter;

		// get image storage
		$storage = new storage();
		$storage->get_instance_by_id($image_storageid);
		$storage_resource_id = $storage->resource_id;
		// get storage resource
		$resource = new resource();
		$resource->get_instance_by_id($storage_resource_id);
		$resource_id = $resource->id;
		$resource_ip = $resource->ip;

		// plug-in the specific storage methods
		$deployment = new deployment();
		$deployment->get_instance_by_type($image->type);
		$deployment_type = $deployment->type;
		$deployment_plugin_name = $deployment->storagetype;
		$storage_cloud_hook = "$RootDir/plugins/$deployment_plugin_name/openqrm-$deployment_type-cloud-hook.php";
		if (file_exists($storage_cloud_hook)) {
			$event->log("create_private", $_SERVER['REQUEST_TIME'], 5, "cloudstorage.class", "Found plugin $deployment_plugin_name to create private $image_name type $deployment_type .", "", "", 0, 0, $resource->id);
			require_once "$storage_cloud_hook";
			$storage_method="create_private_"."$deployment_type";
			$storage_method=str_replace("-", "_", $storage_method);
			$new_rootdevice = $storage_method($cloud_image_id, $private_disk, $private_image_name);
			return $new_rootdevice;
		// not supported yet
		} else {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloustorage.class", "Do not know how to create a private image type $image_type.", "", "", 0, 0, 0);
		}

	}






// ---------------------------------------------------------------------------------

}

?>
