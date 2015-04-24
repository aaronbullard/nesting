<?php

require_once __DIR__ . '/../stubs/ChildrenNested.php';

use Illuminate\Routing\Route;
use ChildrenNested as Nested;

class NestedTest extends PHPUnit_Framework_TestCase {

	public static function tearDownAfterClass()
	{
		Mockery::close();
	}

	protected function mockDatabase($exists = TRUE)
	{
		$db = Mockery::mock('Illuminate\\Database\\DatabaseManager');
		$db->shouldReceive('table')->andReturn($db);
		$db->shouldReceive('where')->andReturn($db);
		$db->shouldReceive('exists')->andReturn( $exists );

		return $db;
	}

	protected function mockRequest(Route $route)
	{
		$request = Mockery::mock('Illuminate\\Http\\Request');
		$request->shouldReceive('route')->once()->andReturn($route);

		return $request;
	}

	protected function mockRoute($parent, $parentId, $child, $childId)
	{
		$route = Mockery::mock('Illuminate\\Routing\\Route');
		$route->shouldReceive('getParameter')->with($parent)->andReturn($parentId);
		$route->shouldReceive('getParameter')->with($child)->andReturn($childId);

		return $route;
	}

	public function testItRecognizesANestedResource()
	{
		// Pass
		$route = $this->mockRoute('parents', 1, 'children', 1);
		$this->assertTrue( Nested::isNestedResourceRoute($route) );

		// Fail
		$route = $this->mockRoute('parents', 1, 'children', NULL);
		$this->assertFalse( Nested::isNestedResourceRoute($route) );
	}

	public function testItPassesTheRequestToTheNextClosure()
	{
		$middleware = new Nested( $this->mockDatabase(TRUE) );

		$route = $this->mockRoute('parents', 1, 'children', 1);
		$request = $this->mockRequest($route);
		$request->shouldReceive('nextCalled')->once();

		$middleware->handle($request, function($request){
			$request->nextCalled();
		});
	}

	public function testItThrowsAnExceptionIfNestedResourceIsNotFound()
	{
		$middleware = new Nested( $this->mockDatabase(FALSE) );

		$route = $this->mockRoute('parents', 1, 'children', 1); // Doesn't exist
		$request = $this->mockRequest( $route );

		$this->setExpectedException('Aaronbullard\\Exceptions\\NotFoundException');
		$middleware->handle($request, function($request){
			$request->nextCalled();
		});
	}

	public function testItQueriesTheDatabase()
	{
		$db = Mockery::mock('Illuminate\\Database\\DatabaseManager');
		$db->shouldReceive('table')->with('children')->once()->andReturn($db);
		$db->shouldReceive('where')->with('parent_id', 1)->once()->andReturn($db);
		$db->shouldReceive('where')->with('id', 1)->once()->andReturn($db);
		$db->shouldReceive('exists')->andReturn( TRUE );

		$middleware = new Nested($db);

		$route = $this->mockRoute('parents', 1, 'children', 1);

		$request = $this->mockRequest($route);
		$request->shouldReceive('nextCalled')->once();

		$middleware->handle($request, function($request){
			$request->nextCalled();
		});
	}

}