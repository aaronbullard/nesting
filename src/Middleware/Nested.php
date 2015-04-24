<?php namespace Aaronbullard\Nesting\Middleware;

use Closure;
use RuntimeException;
use Illuminate\Routing\Route;
use Illuminate\Database\DatabaseManager;
use Aaronbullard\Exceptions\NotFoundException;

abstract class Nested {

	protected static $childKey = 'id';

	protected $db;

	public function __construct(DatabaseManager $db)
	{
		$this->db = $db;

		foreach([static::$childTable, static::$childUri, static::$parentUri, static::$parentFk] as $static)
		{
			if( is_null($static))
			{
				throw new RuntimeException("Class is not configured.");
			}
		}
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$route = $request->route();

		// Check if nested route
		if( ! static::isNestedResourceRoute($route) )
		{
			return $next($request);
		}

		$exists = $this->db->table(static::$childTable)
			->where(static::$parentFk, $route->getParameter(static::$parentUri))
			->where(static::$childKey,  $route->getParameter(static::$childUri))
			->exists();

		if( ! $exists )
		{
			throw new NotFoundException("Nested resource was not found.");
		}

		return $next($request);
	}

	public static function isNestedResourceRoute(Route $route)
	{
		return ! is_null( $route->getParameter(static::$childUri) );
	}

}
