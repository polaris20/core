<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

class Permissions {
	/**
	 * @var string $storageId
	 */
	private $storageId;

	/**
	 * @var \OCP\IDBConnection $conn
	 */
	private $conn;

	/**
	 * @param \OC\Files\Storage\Storage|string $storage
	 * @param \OCP\IDBConnection $conn
	 */
	public function __construct($storage, $conn) {
		$this->conn = $conn;
		if ($storage instanceof \OC\Files\Storage\Storage) {
			$this->storageId = $storage->getId();
		} else {
			$this->storageId = $storage;
		}
	}

	/**
	 * get the permissions for a single file
	 *
	 * @param int $fileId
	 * @param string $user
	 * @return int (-1 if file no permissions set)
	 */
	public function get($fileId, $user) {
		$sql = 'SELECT `permissions` FROM `*PREFIX*permissions` WHERE `user` = ? AND `fileid` = ?';
		$query = $this->conn->prepare($sql);
		$query->execute(array($user, $fileId));
		if ($row = $query->fetch()) {
			return $row['permissions'];
		} else {
			return -1;
		}
	}

	/**
	 * set the permissions of a file
	 *
	 * @param int $fileId
	 * @param string $user
	 * @param int $permissions
	 */
	public function set($fileId, $user, $permissions) {
		if (self::get($fileId, $user) !== -1) {
			$sql = 'UPDATE `*PREFIX*permissions` SET `permissions` = ? WHERE `user` = ? AND `fileid` = ?';
		} else {
			$sql = 'INSERT INTO `*PREFIX*permissions`(`permissions`, `user`, `fileid`) VALUES(?, ?,? )';
		}
		$query = $this->conn->prepare($sql);
		$query->execute(array($permissions, $user, $fileId));
	}

	/**
	 * get the permissions of multiply files
	 *
	 * @param int[] $fileIds
	 * @param string $user
	 * @return int[]
	 */
	public function getMultiple($fileIds, $user) {
		if (count($fileIds) === 0) {
			return array();
		}
		$params = $fileIds;
		$params[] = $user;
		$inPart = implode(', ', array_fill(0, count($fileIds), '?'));

		$sql = 'SELECT `fileid`, `permissions` FROM `*PREFIX*permissions`'
			. ' WHERE `fileid` IN (' . $inPart . ') AND `user` = ?';
		$query = $this->conn->prepare($sql);
		$query->execute($params);
		$filePermissions = array();
		while ($row = $query->fetch()) {
			$filePermissions[$row['fileid']] = $row['permissions'];
		}
		return $filePermissions;
	}

	/**
	 * get the permissions for all files in a folder
	 *
	 * @param int $parentId
	 * @param string $user
	 * @return int[]
	 */
	public function getDirectoryPermissions($parentId, $user) {
		$sql = 'SELECT `*PREFIX*permissions`.`fileid`, `permissions`
			FROM `*PREFIX*permissions`
			INNER JOIN `*PREFIX*filecache` ON `*PREFIX*permissions`.`fileid` = `*PREFIX*filecache`.`fileid`
			WHERE `*PREFIX*filecache`.`parent` = ? AND `*PREFIX*permissions`.`user` = ?';

		$query = $this->conn->prepare($sql);
		$query->execute(array($parentId, $user));
		$filePermissions = array();
		while ($row = $query->fetch()) {
			$filePermissions[$row['fileid']] = $row['permissions'];
		}
		return $filePermissions;
	}

	/**
	 * remove the permissions for a file
	 *
	 * @param int $fileId
	 * @param string $user
	 */
	public function remove($fileId, $user = null) {
		if (is_null($user)) {
			$sql = 'DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ?';
			$query = $this->conn->prepare($sql);
			$query->execute(array($fileId));
		} else {
			$sql = 'DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ? AND `user` = ?';
			$query = $this->conn->prepare($sql);
			$query->execute(array($fileId, $user));
		}
	}

	public function removeMultiple($fileIds, $user) {
		$sql = 'DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ? AND `user` = ?';
		$query = $this->conn->prepare($sql);
		foreach ($fileIds as $fileId) {
			$query->execute(array($fileId, $user));
		}
	}

	/**
	 * get the list of users which have permissions stored for a file
	 *
	 * @param int $fileId
	 */
	public function getUsers($fileId) {
		$sql = 'SELECT `user` FROM `*PREFIX*permissions` WHERE `fileid` = ?';
		$query = $this->conn->prepare($sql);
		$query->execute(array($fileId));
		$users = array();
		while ($row = $query->fetch()) {
			$users[] = $row['user'];
		}
		return $users;
	}
}
