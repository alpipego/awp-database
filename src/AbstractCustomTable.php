<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 21.07.2017
 * Time: 12:56
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Database;

abstract class AbstractCustomTable implements CustomTableInterface
{
	protected const NAME = '';
	protected const SCHEMA = '';
	protected const VERSION = '';
	protected const OPTION_PREFIX = 'awp_db_';
	protected $db;
	protected $prefix;
	protected $table;

	public function __construct(DatabaseInterface $database)
	{
		$this->db     = $database;
		$this->prefix = $this->prefix ?? $this->db->db()->prefix;
		$this->table  = $this->table ?? $this->prefix . static::NAME;
		$this->db->register(static::NAME, $this->table);
	}

	public function create()
	{
		if ( ! $this->needsUpdate()) {
			return;
		}
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$errors = array_filter(dbDelta($this->getSchema()), function ($query) {
			return strpos($query, 'database error') !== false;
		});
		if ( ! empty($errors)) {
			throw new Exceptions\DatabaseException(implode('\n', $errors));
		}
		$this->saveVersion();
	}

	protected function needsUpdate() : bool
	{
		return static::VERSION !== get_option(static::OPTION_PREFIX . static::NAME . '_version');
	}

	public function getSchema() : string
	{
		$schema = static::SCHEMA;

		return "CREATE TABLE {$this->table} (
			${schema}
		) {$this->db->db()->get_charset_collate()};";
	}

	protected function saveVersion() : bool
	{
		return update_option(static::OPTION_PREFIX . static::NAME . '_version', static::VERSION);
	}
}
