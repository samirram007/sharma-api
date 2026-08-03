<?php

namespace Modules\TestItem\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\TestItem\Contracts\TestItemRepositoryInterface;
use Modules\TestItem\Models\TestItem;

class TestItemRepository extends BaseRepository implements TestItemRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'print_name',
        'sku',
        'article_no',
        'part_no',
        'description',
        // 'base_unit_value',
        // 'alternate_unit_value',
        // 'reorder_level',
        // 'minimum_stock',
        // 'maximum_stock',
        'hsn_sac_code',
        // 'mrp',
        // 'standard_cost',
        // 'standard_selling_price',
        'icon',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'stock_category_id',
        // 'stock_group_id',
        // 'stock_unit_id',
        // 'alternate_stock_unit_id',
        // 'unique_quantity_code_id',
        // 'type_of_supply',
        // 'is_negative_sales_allow',
        // 'is_maintain_batch',
        // 'is_maintain_serial',
        // 'use_expiry_date',
        // 'track_manufacturing_date',
        // 'is_finish_goods',
        // 'is_raw_material',
        // 'is_unfinished_goods',
        // 'costing_method',
        // 'market_valuation_method',
        // 'has_bom',
        // 'is_sales_as_new_manufacture',
        // 'is_purchase_as_consumed',
        // 'is_rejection_as_scrap',
        // 'is_gst_applicable',
        // 'rate_of_duty',
        // 'is_gst_inclusive',
        // 'gst_type',
        // 'stock_item_brand_id',
        'status',
    ];

    public function __construct(TestItem $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
