<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Database;

use Alpipego\AWP\Database\Exceptions\DatabaseException;
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
	protected $sort = [];
	protected $limit;
	protected $where = [];
	protected $join = [];

	public function __construct(string $table, wpdb $db)
	{
		$this->db    = $db;
		$this->table = $table;
	}

	public function table() : string
	{
		return $this->table;
	}

	public function sort(string $sort) : TableInterface
	{
		$this->sort[] = $sort;

		return $this;
	}

	public function limit(int $limit) : TableInterface
	{
		$this->limit = $limit;

		return $this;
	}

	public function where(array $where) : TableInterface
	{
		$this->where[] = $where;

		return $this;
	}

	public function count(array $where = []) : int
	{
		// "SELECT * FROM {$this->table} WHERE $where;"
		return (int)$this->db->get_var($this->select([
			'fields'  => ['count(*)'],
			'where'   => $this->parseWhere($where),
			'limit'   => null,
			'orderby' => null,
		]));
	}

	public function get_results(array $fields = ['*'], array $where = [], int $limit = null, int $offset = 0, array $groupby = []) : array
	{
		// "SELECT ${fields} FROM {$this->table} WHERE ${where} LIMIT ${limit};"
		return $this->db->get_results($this->select([
				'fields'  => $fields,
				'where'   => $this->parseWhere($where),
				'limit'   => (is_null($limit) && empty($offset)) ? null : $this->parseLimit($offset, $limit),
				'groupby' => $this->parseGroupBy($groupby),
			]), ARRAY_A) ?? [];
	}

	public function get_col(string $field, array $where = []) : array
	{
		// "SELECT ${field} FROM {$this->table} WHERE ${where};"
		return $this->db->get_col($this->select([
				'fields' => [$field],
				'where'  => $this->parseWhere($where),
			])) ?? [];
	}

	public function get_cols(array $fields, array $where = []) : array
	{
		// "SELECT ${fields} FROM {$this->table} WHERE ${where};"
		return $this->db->get_results($this->select([
				'fields' => $fields,
				'where'  => $this->parseWhere($where),
			]), ARRAY_A) ?? [];
	}

	public function get_row(array $where) : array
	{
		// "SELECT * FROM {$this->table} WHERE ${where};"
		return $this->db->get_row($this->select([
				'where' => $this->parseWhere($where),
			]), ARRAY_A) ?? [];
	}

	public function get_rows(array $where = [], int $limit = null, int $offset = 0) : array
	{
		// "SELECT * FROM {$this->table} WHERE ${where} LIMIT ${limit};"
		return $this->db->get_results($this->select([
				'where' => $this->parseWhere($where),
				'limit' => (is_null($limit) && empty($offset)) ? null : $this->parseLimit($offset, $limit),
			]), ARRAY_A) ?? [];
	}

	public function get_var(string $field, array $where)
	{
		// "SELECT ${field} FROM {$this->table} WHERE ${where};"
		return $this->db->get_var($this->select([
			'fields'  => [$field],
			'where'   => $this->parseWhere($where),
			'orderby' => null,
		]));
	}

	public function save(array $data, array $where = null, array $format = null, array $whereFormat = null)
	{
		$parsedWhere = [];
		array_walk($where, function($value, $key) use (&$parsedWhere) {
			$parsedWhere[] = [$key, $value];
		});
		if ( ! is_null($where) && ! empty($this->get_row($parsedWhere))) {
			return $this->update($data, $where, $format, $whereFormat);
		}
		$format      = $format ?? [];
		$formatCount = count($format);
		$format      = array_merge($format, array_fill($formatCount, (count($data) - $formatCount), ''));
		$i           = 0;
		foreach ($data as $value) {
			$numbered = $i++;
			if (is_null($value)) {
				$format[$numbered] = 'NULL';
				continue;
			}
			if ( ! empty($format[$numbered])) {
				continue;
			}
			if (is_numeric($value) && is_string($value)) {
				if (strpos($value, '.') || strpos($value, ',')) {
					$format[$numbered] = '%F';
					continue;
				}
				if ((int)$value == $value) {
					$format[$numbered] = '%d';
					continue;
				}
			}

			$format[$numbered] = '%s';
		}

		$fields       = array_keys($data);
		$values       = array_values($data);
		$format       = array_values($format);
		$fieldsString = '`' . implode('`, `', $fields) . '`';
		$valuesString = $this->db->prepare(implode(',', $format), $values);

		$updateString = '';
		for ($i = 0; $i < count($values); $i++) {
			$updateString .= sprintf('`%s`="%' . $format[$i] . '",', $fields[$i]);
		}
		$updateString = rtrim($updateString, ',');
		$updateString = $this->db->prepare($updateString, $values);

		return $this->db->query("INSERT INTO {$this->table} ({$fieldsString}) VALUES ({$valuesString}) ON DUPLICATE KEY UPDATE {$updateString};");
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

	public function delete(array $where, array $format = null)
	{
		return $this->db->delete($this->table, $where, $format);
	}

	public function select(array $args = [])
	{
		/**
		 * @var string $distinct
		 * @var array  $fields
		 * @var string $join
		 * @var string $where
		 * @var string $groupby
		 * @var string $orderby
		 * @var string $limit
		 */
		extract(array_merge([
			'distinct' => '',
			'fields'   => ['*'],
			'join'     => '',
			'where'    => '',
			'groupby'  => '',
			'orderby'  => '',
			'limit'    => '',
		], $args));

		$fields = implode(', ', $fields);
		$where  = ! empty($where) ? $where : '1=1';

		if ( ! is_null($orderby)) {
			$this->sort[] = $orderby;
			$this->sort   = array_filter($this->sort);
			if ( ! empty($this->sort)) {
				$orderby = 'ORDER BY ' . implode(', ', $this->sort);
			}
		}

		if (is_null($limit)) {
			$limit = is_null($this->limit) ? '' : ' LIMIT ' . $this->limit;
		}

		$this->join[] = $join;
		$this->join   = array_filter($this->join);
		if (empty($join)) {
			$join = implode(' ', $this->join);
		}

		$query = "SELECT ${distinct} ${fields} FROM {$this->table} ${join} WHERE ${where} ${groupby} ${orderby} ${limit}";

		return $query;
	}

	protected function parseWhere(array $where) : string
	{
		if ( ! empty($where) && empty(array_filter($where, 'is_array'))) {
			$parsed   = [];
			$parsed[] = [
				'field'   => $where[0] ?? '',
				'value'   => $where[1],
				'compare' => $where[2] ?? '=',
			];
			$where    = $parsed;
		}

		if ( ! empty($this->where)) {
			$where = array_merge($where, $this->where);
		}

		$parser      = function ($chunk) {
			$field   = $chunk['field'] ?? $chunk[0];
			$value   = $chunk['value'] ?? $chunk[1];
			$compare = $chunk['compare'] ?? $chunk[2] ?? '=';
			if (empty($field)) {
				return false;
			}
			$type = '%s';
			if (is_null($value)) {
				return sprintf('%s is %s null', $field, $compare === '=' ? '' : 'NOT');
			}
			if (is_int($value)) {
				$type = '%d';
			}
			if (is_float($value)) {
				$type = '%F';
			}

			return $this->db->prepare($field . ' ' . $compare . ' ' . $type, $value);
		};
		$whereString = '';
		$conditions  = array_filter($where, 'is_array');
		$outer       = 1;
		$outerCount  = count($conditions);
		foreach ($conditions as $chunk) {
			$relation = $this->parseRelation($where['relation'] ?? '');
			unset($where['relation']);
			if ( ! empty(array_filter($chunk, 'is_array'))) {
				$whereString   .= '(';
				$innerRelation = $this->parseRelation($chunk['relation'] ?? '');
				unset($chunk['relation']);
				$inner      = 1;
				$innerCount = count($chunk);
				foreach ($chunk as $item) {
					$parsedItem = $parser($item);
					if ( ! $parsedItem) {
						continue;
					}
					$whereString .= $parsedItem;
					if ($innerCount !== $inner++) {
						$whereString .= sprintf(' %s ', $innerRelation);
					}
				}
				$whereString .= ')';
				if ($outerCount !== $outer++) {
					$whereString .= sprintf(' %s ', $relation);
				}
				continue;
			}

			$whereString .= $parser($chunk);
			if ($outerCount !== $outer++) {
				$whereString .= sprintf(' %s ', $relation);
			}
		}

		return $whereString;
	}

	private function parseRelation(string $relation = null) : string
	{
		$relation = strtoupper((string)$relation);

		return in_array($relation, ['AND', 'OR']) ? $relation : 'AND';
	}

	private function parseGroupBy(array $group) : string
	{
		return empty($group) ? '' : sprintf(' GROUP BY %s', implode(',', $group));
	}

	private function parseLimit(int $offset, int $limit = null) : string
	{
		if (is_null($limit)) {
			if (is_null($this->limit)) {
				return '';
			}

			$limit = $this->limit;
		}

		return sprintf(' LIMIT %d OFFSET %d', $limit, $offset);
	}

	public function lastQuery() : string
	{
		return is_array($this->db->last_query) ? end($this->db->last_query) : (string)$this->db->last_query;
	}

	public function join(string $table, string $outerField, string $innerField, string $compare = '=', string $direction = 'inner') : TableInterface
	{
		$allowedJoins = ['inner', 'outer', 'left', 'right'];
		if ( ! in_array($direction, $allowedJoins)) {
			throw new DatabaseException(
				sprintf('$direction has to be one of: %s. %s given', implode(', ', $allowedJoins), $direction)
			);
		}

		$this->join[] = sprintf(' %s JOIN %s ON %s %s %s', strtoupper($direction), $table, $innerField, $compare, $outerField);

		return $this;
	}

	public function reset() : TableInterface
	{
		return new self($this->table, $this->db);
	}

	public function query(string $query)
	{
		return $this->db->query($query);
	}
}
