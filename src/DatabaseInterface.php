<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 21.07.2017
 * Time: 13:08
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Database;

use wpdb;

interface DatabaseInterface
{
	public function register(string $name, string $table);

	public function tables(string $scope = 'all', bool $prefix = true, int $blogId = 0) : array;

	public function db() : wpdb;
}
