<?php
/*
	Copyright 2002-2007 Pierre Schmitz <pschmitz@laber-land.de>

	This file is part of LL.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with LL.  If not, see <http://www.gnu.org/licenses/>.
*/
require (LL_PATH.'modules/DB.php');
require (LL_PATH.'modules/IOutput.php');

abstract class Page extends Modul implements IOutput{

protected $variables = array();

private static $availablePages = array
	(
	'Start' => 'pages/Start.php',
	'NotFound' => 'pages/NotFound.php',
	'Packages' => 'pages/Packages.php',
	'PackageDetails' => 'pages/PackageDetails.php',
	'GetUpdates' => 'pages/GetUpdates.php'
	);

public static function loadPage($name)
	{
	if (isset(self::$availablePages[$name]))
		{
		include_once(self::$availablePages[$name]);
		}
	else
		{
		throw new RuntimeException('Seite '.$name.' wurde nicht gefunden!', 0);
		}
	}

public function __construct()
	{
	self::__set('DB', new DB(
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database')
		));

	$this->variables['body']	 = '';
	$this->variables['title']	 = '';
	$this->variables['meta.robots']	 = 'index,follow';
	}

public function setValue($key, $value)
	{
	$this->variables[$key] = $value;
	}

public function getValue($key)
	{
	return $this->variables[$key];
	}

protected function showWarning($text)
	{
	$this->setValue('meta.robots', 'noindex,nofollow');
	$this->setValue('title', 'Warnung');
	$this->setValue('body', '<div class="warning">'.$text.'</div>');
	$this->sendOutput();
	}

protected function showFailure($text)
	{
	$this->setValue('meta.robots', 'noindex,nofollow');
	$this->setValue('title', 'Fehler');
	$this->setValue('body', '<div class="warning">'.$text.'</div>');
	$this->sendOutput();
	}

public function prepare()
	{
	$this->setValue('title', 'Warnung');
	$this->setValue('body', 'kein Text');
	}

protected function makeMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=Download">ISOs</a></li>
			<li><a href="?page=Packages">Pakete</a></li>
			<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
			<li><a href="http://wiki.archlinux.de/?title=Bugs">Bugs</a></li>
			<li><a href="http://wiki.archlinux.de">Wiki</a></li>
			<li><a href="http://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li class="selected">Start</li>
		</ul>';
	}

private function sendOutput()
	{
	$file = '<?xml version="1.0" encoding="UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
	<head>
		<title>archlinux.de :: '.$this->getValue('title').'</title>
		<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
		<meta http-equiv="content-language" content="de" />
		<meta http-equiv="expires" content="120" />
		<meta name="robots" content="index,follow" />
		<meta name="revisit-after" content="3 days" />
		<link rel="shortcut icon" href="favicon.ico" />
		<link rel="stylesheet" href="arch.css" />
	</head>
	<body>
		<h1 id="logo">Arch Linux</h1>
		<div id="nav_bar">
			'.$this->makeMenu().'
		</div>
		<div id="subnav_bar"></div>
		<div id="content">
			'.$this->getValue('body').'
			<div id="foot">
				<a href="http://wiki.archlinux.de/?title=Wiki:Datenschutz">Datenschutz</a> ::
				<a href="http://wiki.archlinux.de/?title=Wiki:Impressum">Impressum</a>
			</div>
		</div>
	</body>
</html>';
	$this->Io->out($file);
	}

public function show()
	{
	$this->sendOutput();
	}
}

?>