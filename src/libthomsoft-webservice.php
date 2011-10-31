<?php if (!defined('SERVICE_CLASS')) die('Access Denied');

foreach (array(
    '/usr/share/php/libzend-framework-php'
) as $dir) {
    if (is_dir($dir)) {
        define('ZEND_FRAMEWORK_PATH', $dir);
        set_include_path(ZEND_FRAMEWORK_PATH);
    }
}

require_once 'Zend/Loader.php';
function __autoload($class) { Zend_Loader::loadClass($class); }


if (defined('AUTHENTICATION_CLASS')) {
	authenticate();
}

function authenticate() {
	if (
		!isset($_SERVER['PHP_AUTH_USER']) || 
		!call_user_func(
			array(AUTHENTICATION_CLASS,'checkLogin'),
			$_SERVER['PHP_AUTH_USER'], 
			$_SERVER['PHP_AUTH_PW']
		)
	) {
	    header('WWW-Authenticate: Basic realm="API"');
	    header('HTTP/1.0 401 Unauthorized');
	    echo 'Access Denied';
	    exit;
	}
}

switch (substr(strtolower($_SERVER['PATH_INFO']),0, 5))
{
	case '/soap':
		serveSoap();
		break;

	case '/json':
		serveJson();
		break;

	case '/xmlr':
		serveXmlRpc();
		break;

	case '/rest':
		serveRest();
		break;

	case '/www/':
		serveWww();
		break;

	default:
		die('Access Denied');
}

exit();

function serveSoap() {
	if(isset($_GET['wsdl'])) {
		$server = new Zend_Soap_AutoDiscover();
		$server->setClass(SERVICE_CLASS);
		echo $server->handle();
	} else {
		$server = new Zend_Soap_Server(
			null,
			array('uri' => getUri())
		);
		$server->registerFaultException(array('Exception'));
		$server->setClass(SERVICE_CLASS);
		echo $server->handle();
	}
}

function serveJson() {
	$server = new Zend_Json_Server();
	$server->setClass(SERVICE_CLASS);

	if('GET' == $_SERVER['REQUEST_METHOD']) {
		$server->setTarget(getUri())->setEnvelope(
			Zend_Json_Server_Smd::ENV_JSONRPC_2
		);
		$smd = $server->getServiceMap();
		if (isset($_GET['dojo'])) $smd->setDojoCompatible(true);
		header('Content-Type: application/json');
		echo $smd;
		exit(0); // Should this be here?
	}

	echo $server->handle();
}

function serveXmlRpc() {
	$server = new Zend_XmlRpc_Server();
	$server->setClass(SERVICE_CLASS);
	echo $server->handle();
}

function serveRest() {
	$server = new Zend_Rest_Server();
	$server->setClass(SERVICE_CLASS);
	echo $server->handle();
}

function serveWww() {
	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
	echo '<html xmlns="http://www.w3.org/1999/xhtml">';
	echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title>Webinterface</title>';
	echo '<script src="http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '://ajax.googleapis.com/ajax/libs/dojo/1.4.1/dojo/dojo.xd.js"></script>';
	echo '<style>';
	echo 'fieldset { border: 1px solid green; margin-bottom: 4em; padding: 1em; font: 80%/1 sans-serif; -moz-border-radius: 5px; -webkit-border-radius: 5px; } ';
	echo 'fieldset div { padding-bottom: 1em; } ';
	echo 'label { float: left; width: 25%; margin-right: 0.5em; padding-top: 0.3em; text-align: right; font-weight: bold; } ';
	echo 'legend { padding: 0.4em 1em; font-weight: bold; border: 1px solid green; background: #eeffee; text-shadow: #6374ab 1px 1px 2px; -moz-border-radius: 5px; -webkit-border-radius: 5px; } ';
	echo 'pre { border: 1px solid green; padding: 1em; background: #eeffee; } ';
	echo 'pre.error { border: 1px solid red; padding: 1em; background: #ffeeee; } ';
	echo '</style>';
	echo '</head><body>';
	echo '<script type="text/javascript">dojo.addOnLoad(function(){
		dojo.require("dojo.rpc.JsonService");

		dojo.xhrGet({
			url:"' . dirname(getUri()) . '/json/",
			handleAs: "json",
			load: function(response, ioArgs) {
				for(var i in response.services) {
					(function(index, entry)
					{
						var fs = document.createElement("fieldset");
						var l = document.createElement("legend");
						l.appendChild(document.createTextNode(index));
						fs.appendChild(l);

						for (var j in entry.parameters) {
							var p = entry.parameters[j];
							
							div = document.createElement("div");
							label = document.createElement("label");
							label.setAttribute("for",index + "---" + j);
							label.appendChild(document.createTextNode(
								(p.optional ? "" : "* ") 
								+ p.name
							));

							if (p.type == "boolean" || p.type == "bool") {
								var fld = document.createElement("input");
								fld.setAttribute("type", "checkbox");
								if (p["default"]) {
									fld.setAttribute("checked", "checked");
								}
							} else {
								var fld = document.createElement("textarea");
                                fld.setAttribute("rows", 1);
                                fld.setAttribute("cols", 40);
							}

							fld.id = index + "---" + j;

                            if (p.optional) {
                                if (p["default"]) {
                                    fld.title = p.name + " (optional, default: " + p["default"] + ")" ;
                                } else {
                                    fld.title = p.name + " (optional)";
                                }
                            } else {
                                fld.title = p.name + " (required)";
                            }

							div.appendChild(label);
							div.appendChild(fld);
							fs.appendChild(div);
						}
	
						label = document.createElement("label");
						fs.appendChild(label);

						btn = document.createElement("button");
						btn.appendChild(document.createTextNode("Invoke"));

						btn.onclick = function() {
							proxy = new dojo.rpc.JsonService("' . dirname(getUri()) . '/json/?dojo");
							parameters = [];

							for (var k in entry.parameters) {
								var p = entry.parameters[k];

								if (p.type == "boolean" || p.type == "bool") {
									parameters.push(document.getElementById(index + "---" + k).checked);
								} else {
									parameters.push(document.getElementById(index + "---" + k).value);
								}
							}

							proxy[index].apply(this,parameters).addCallback(function(result){
								var pre = document.createElement("pre");
								pre.appendChild(document.createTextNode(dojo.toJson(result, true)));
								fs.appendChild(pre);
							}).addErrback(function(message) {
								var pre = document.createElement("pre");
								pre.setAttribute("class", "error");
								pre.appendChild(document.createTextNode(message));
								fs.appendChild(pre);
							});
						};

						fs.appendChild(btn);

						document.body.appendChild(fs);
					})(i, response.services[i]);
				}
			}
		});
	});</script>';
	echo '</body>';
}


function getUri() {
	$path = $_SERVER['REQUEST_URI'];
	if ($pos = strpos($path, '?'))
		$path = substr($path,0,$pos);

    return 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $path;
}
