<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

interface ICache {
public function addObject($key, $object, $ttl = 0);
public function getObject($key);
public function isObject($key);
}

class ObjectCache implements ICache {

private $cache = null;

public function __construct()
	{
	if (function_exists('apc_store'))
		{
		$this->cache = new APCObjectCache();
		}
	else
		{
		$this->cache = new NOOPObjectCache();
		}
	}

public function addObject($key, $object, $ttl = 0)
	{
	return $this->cache->addObject($key, $object, $ttl);
	}

public function getObject($key)
	{
	return $this->cache->getObject($key);
	}

public function isObject($key)
	{
	return $this->cache->isObject($key);
	}

}

class NOOPObjectCache implements ICache {

public function addObject($key, $object, $ttl = 0)
	{
	return false;
	}

public function getObject($key)
	{
	return false;
	}

public function isObject($key)
	{
	return false;
	}
}

class APCObjectCache implements ICache {

public function addObject($key, $object, $ttl = 0)
	{
	return apc_store($key, $object, $ttl);
	}

public function getObject($key)
	{
	return apc_fetch($key);
	}

public function isObject($key)
	{
	apc_fetch($key, $success);
	return $success;
	}
}

class PersistentCache extends Modul implements ICache {

private $time = 0;

public function __construct()
	{
	$this->time = time();
	}

public function addObject($key, $object, $ttl = 0)
	{
	if ($ttl <= 0)
		{
		try
			{
			$stm = $this->DB->prepare
				('
				REPLACE INTO
					cache
				SET
					`key` = ?,
					value = ?
				');
			$stm->bindString($key);
			$stm->bindString(serialize($object));
			$stm->execute();
			}
		catch (DBException $e)
			{
			}
		}
	else
		{
		try
			{
			$stm = $this->DB->prepare
				('
				REPLACE INTO
					cache
				SET
					`key` = ?,
					value = ?,
					expires = ?
				');
			$stm->bindString($key);
			$stm->bindString(serialize($object));
			$stm->bindInteger(($this->time + $ttl));
			$stm->execute();
			}
		catch (DBException $e)
			{
			}
		}
	$stm->close();
	}

public function getObject($key)
	{
	$this->collectGarbage();

	$value = false;

	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				value
			FROM
				cache
			WHERE
				`key` = ?
			');
		$stm->bindString($key);

		$value = unserialize($stm->getColumn());
		}
	catch (DBNoDataException $e)
		{
		}
	$stm->close();

	return $value;
	}

public function isObject($key)
	{
	$value = false;

	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				value
			FROM
				cache
			WHERE
				`key` = ?
			');
		$stm->bindString($key);

		$value = true;
		}
	catch (DBNoDataException $e)
		{
		}
	$stm->close();

	return $value;
	}

private function collectGarbage()
	{
	/* Ignore 49% of requests */
	if (!mt_rand(0, 50))
		{
		try
			{
			$stm = $this->DB->prepare
				('
				DELETE FROM
					cache
				WHERE
					expires < ?
				');
			$stm->bindInteger($this->time);
			$stm->execute();
			}
		catch (DBException $e)
			{
			}
		$stm->close();
		}
	}

}

?>