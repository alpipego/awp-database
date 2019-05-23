<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 21.07.2017
 * Time: 13:09
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Database;

use Alpipego\AWP\Database\Exceptions\DatabaseException;
use wpdb;

class Database implements DatabaseInterface
{
	/**
	 * @var wpdb $db
	 */
	private $db;
	protected $tables;
	protected $tableImplementation;

	public function __construct()
	{
		$this->db     = $GLOBALS['wpdb'];
		$this->tables = $this->db->tables();
	}

	public function setTableImplementation(string $table)
	{
		$this->tableImplementation = $table;
	}

	protected function table(string $name, wpdb $db) : TableInterface
	{
		$default = Table::class;
		try {
			$reflection = new \ReflectionClass($this->tableImplementation ?? $default);
		} catch (\ReflectionException $e) {
			return new $default($name, $db);
		}

		if (!$reflection->implementsInterface(TableInterface::class)) {
			return new $default($name, $db);
		}

		return $reflection->newInstance($name, $db);
	}

	public function db() : wpdb
	{
		return $this->db;
	}

	public function register(string $name, string $table)
	{
		if ( ! array_key_exists($name, $this->tables)) {
			$this->tables[$name] = $table;
		}
	}

	public function __get(string $name) : TableInterface
	{
		if ( ! array_key_exists($name, $this->tables)) {
			throw new DatabaseException(sprintf('Table "%s" does not exist.', $name));
		}

		return $this->table($this->tables[$name], $this->db);
	}

	public function tables(string $scope = 'all', bool $prefix = true, int $blogId = 0) : array
	{
		if ($scope === 'custom') {
			$custom = array_diff_assoc($this->tables, $this->db->tables());

			return $prefix ? $custom : array_keys($custom);
		}

		if ($scope === 'all') {
			return $prefix ? $this->tables : array_keys($this->tables);
		}

		return $this->db->tables($scope, $prefix, $blogId);
	}
}
