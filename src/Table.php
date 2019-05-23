<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Database;

use wpdb;

class Table implements TableInterface
{
	/**
	 * @var wpdb
	 */
	protected $db;
	/**
	 * @var string
	 */
	protected $table;

	public function __construct(string $table, wpdb $db)
	{
		$this->db    = $db;
		$this->table = $table;
	}

	public function table() : string
	{
		return $this->table;
	}

	public function count(string $where = '1=1') : int
	{
		$limit = PHP_INT_MAX;

		return (int)$this->db->get_var("SELECT count(*) FROM {$this->table} WHERE ${where} LIMIT ${limit};");
	}

	public function get_results(string $select = '*', string $where = '1=1') : array
	{
		return $this->db->get_results("SELECT ${select} FROM {$this->table} WHERE ${where};", ARRAY_A);
	}

	public function get_col(string $colname, string $where = '1=1') : array
	{
		return $this->db->get_col("SELECT ${colname} FROM {$this->table} WHERE ${where};");
	}

	public function get_cols(string $colnames, string $where = '1=1') : array
	{
		return $this->db->get_results("SELECT ${colnames} FROM {$this->table} WHERE ${where};", ARRAY_A);
	}

	public function get_row(string $where) : array
	{
		return $this->db->get_row("SELECT * FROM {$this->table} WHERE ${where};", ARRAY_A);
	}

	public function get_rows(string $where = '1=1', int $limit = null) : array
	{
		$limit = is_null($limit) ? '' : ' LIMIT ' . $limit;

		return $this->db->get_results("SELECT * FROM {$this->table} WHERE ${where}${limit};", ARRAY_A);
	}

	public function get_var(string $field, string $where)
	{
		return $this->db->get_var("SELECT ${field} FROM {$this->table} WHERE ${where};");
	}

	public function insert(array $data, array $format = null)
	{
		return $this->db->insert($this->table, $data, $format);
	}

	public function replace(array $data, array $format = null)
	{
		return $this->db->insert($this->table, $data, $format);
	}

	public function update(array $data, array $where, array $format = null, array $whereFormat = null)
	{
		return $this->db->update($this->table, $data, $where, $format, $whereFormat);
	}

	public function delete(array $where, array $format)
	{
		return $this->db->delete($this->table, $where, $format);
	}
}
