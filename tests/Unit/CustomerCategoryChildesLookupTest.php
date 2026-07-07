<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Mockery;
use Modules\CategoryManagement\Entities\Category;
use Modules\CategoryManagement\Http\Controllers\Api\V1\Customer\CategoryController;
use Modules\ServiceManagement\Entities\FavoriteService;
use Modules\ServiceManagement\Entities\RecentView;
use Tests\TestCase;

class CustomerCategoryChildesLookupTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_childes_resolves_parent_category_by_slug_or_id(): void
    {
        $categoryModel = Mockery::mock(Category::class);
        $recentView = Mockery::mock(RecentView::class);
        $favoriteService = Mockery::mock(FavoriteService::class);

        $parent = (object) ['id' => 'cat-uuid-123'];

        $categoryModel->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnSelf();
        $categoryModel->shouldReceive('first')
            ->once()
            ->andReturn($parent);

        $childQuery = Mockery::mock();
        $childQuery->shouldReceive('ofStatus')->with(1)->andReturnSelf();
        $childQuery->shouldReceive('ofType')->with('sub')->andReturnSelf();
        $childQuery->shouldReceive('withoutGlobalScopes')->with(['zone_wise_data'])->andReturnSelf();
        $childQuery->shouldReceive('withActiveServices')->andReturnSelf();
        $childQuery->shouldReceive('withCount')->andReturnSelf();
        $childQuery->shouldReceive('whereHas')->andReturnSelf();
        $childQuery->shouldReceive('where')->with('parent_id', 'cat-uuid-123')->andReturnSelf();
        $childQuery->shouldReceive('orderBY')->with('name', 'asc')->andReturnSelf();
        $childQuery->shouldReceive('paginate')
            ->once()
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1));

        $categoryModel->shouldReceive('ofStatus')->with(1)->andReturn($childQuery);

        $request = Request::create('/api/v1/customer/category/childes', 'GET', [
            'limit' => 20,
            'offset' => 1,
            'slug' => 'cat-uuid-123',
        ]);

        $controller = new CategoryController($categoryModel, $recentView, $favoriteService, $request);
        $response = $controller->childes($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
