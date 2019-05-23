<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 21.07.2017
 * Time: 12:54
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Database;

interface CustomTableInterface
{
	public function create();

	public function getSchema() : string;
}
