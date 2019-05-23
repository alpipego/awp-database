<?php

namespace Alpipego\AWP\Database;

interface TableInterface
{
	public function table() : string;

	public function count(string $where = '1=1') : int;

	public function get_results(string $select = '*', string $where = '1=1') : array;

	public function get_col(string $colname, string $where = '1=1') : array;

	public function get_cols(string $colnames, string $where = '1=1') : array;

	public function get_row(string $where) : array;

	public function get_rows(string $where = '1=1', int $limit = null) : array;

	public function get_var(string $field, string $where);

	public function insert(array $data, array $format = null);

	public function replace(array $data, array $format = null);

	public function update(array $data, array $where, array $format = null, array $whereFormat = null);

	public function delete(array $where, array $format);
}
