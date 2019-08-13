<?php

namespace Alpipego\AWP\Database;

interface TableInterface
{
	public function table() : string;

	public function sort(string $sort) : self;

	public function limit(int $limit) : self;

	public function where(array $where) : self;

	public function join(string $table, string $outerField, string $innerField, string $compare = '=', string $direction = 'inner') : self;

	public function count(array $where = []) : int;

	public function get_results(array $fields = ['*'], array $where = [], int $limit = null, int $offset = 0, array $groupby = []) : array;

	public function get_col(string $field, array $where = []) : array;

	public function get_cols(array $fields, array $where = []) : array;

	public function get_row(array $where) : array;

	public function get_rows(array $where = [], int $limit = null, int $offset = 0) : array;

	public function get_var(string $field, array $where);

	public function insert(array $data, array $format = null);

	public function replace(array $data, array $format = null);

	public function update(array $data, array $where, array $format = null, array $whereFormat = null);

	public function delete(array $where, array $format);

	public function save(array $data, array $where = null, array $format = null, array $whereFormat = null);

	public function select(array $args = []);

	public function lastQuery() : string;

	public function reset() : self;

	public function query(string $query);
}
