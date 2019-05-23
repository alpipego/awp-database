<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Database;

class QueryBuilder
{
	protected $db;
	protected $table;
	protected $orderBy = [];

	public function __construct(\wpdb $db, string $table)
	{
		$this->db    = $db;
		$this->table = $table;
	}

	public function __invoke()
	{
		// TODO: Implement __invoke() method.
	}

	public function orderBy(string $column, string $direction = 'desc')
	{
		$this->orderBy[$column] = $direction;
	}

	public function var(string $var)
	{

	}

	public function col(string $column)
	{
		return $this->cols($column);
	}

	public function cols(string ...$columns)
	{
		return $this->get();
	}

	public function rows(int $limit)
	{
		return $this->get();
	}

	public function row()
	{
		return $this->rows(1);
	}

	private function get()
	{

	}
}
