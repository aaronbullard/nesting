<?php

use Aaronbullard\Nesting\Middleware\Nested;

class ChildrenNested extends Nested {

	protected static $childTable = 'children';
	protected static $childUri = 'children';
	protected static $childKey = 'id';
	protected static $parentUri = 'parents';
	protected static $parentFk = 'parent_id';
}