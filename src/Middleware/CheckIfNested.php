<?php namespace Aaronbullard\Nesting\Middleware;

use Closure;
use Exception;
use Illuminate\Database\DatabaseManager;
use Nesting\Services\RequestTranslator;

abstract class CheckIfNested {

	protected static $childKey = 'id';

	protected $db;

	protected $translator;

	public function __construct(DatabaseManager $db, RequestTranslator $translator)
	{
		$this->db = $db;
		$this->translator = $translator;
	}

	protected function getTable()
	{
		return static::$table;
	}

	protected function getForeignKeyForParent()
	{
		return static::$parentKey;
	}

	protected function getPrimaryKeyForChild()
	{
		return static::$childKey;
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
		// Check if nested route
		if( ! $this->translator->isNestedResourceRoute($request) )
		{
			return $next($request);
		}

		$exists = $this->db->table($this->getTable())
			->where($this->getForeignKeyForParent(), $this->translator->getParentId($request))
			->where($this->getPrimaryKeyForChild(), $this->translator->getChildId($request))
			->exists();

		if( ! $exists )
		{
			throw new Exception("Nested resource was not found.");
		}

		return $next($request);
	}

}