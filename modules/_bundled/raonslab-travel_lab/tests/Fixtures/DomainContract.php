<?php

/**
 * 병렬 도메인 구현을 합치기 전 워크플로만 검증하기 위한 계약 fixture.
 * 실제 클래스가 존재하면 이 파일의 대체 클래스는 선언하지 않는다.
 * 운영 autoload/provider에서는 이 파일을 참조하지 않는다.
 */

namespace Modules\Raonslab\TravelLab\Enums;

if (! enum_exists(InquiryStatus::class)) {
    enum InquiryStatus: string
    {
        case TEST_INQUIRY = 'test_inquiry';
        case UNDER_REVIEW = 'under_review';
        case TEST_ACCEPTED = 'test_accepted';
        case DECLINED = 'declined';
        case CANCELLED = 'cancelled';
    }
}

namespace Modules\Raonslab\TravelLab\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;

if (! class_exists(TravelProduct::class)) {
    class TravelProduct extends Model
    {
        protected $table = 'travel_lab_products';

        protected $guarded = [];

        protected $casts = ['published' => 'boolean', 'itinerary' => 'array'];

        public function product()
        {
            return $this->belongsTo(Product::class);
        }
    }
}

if (! class_exists(Departure::class)) {
    class Departure extends Model
    {
        protected $table = 'travel_lab_departures';

        protected $guarded = [];

        protected $casts = ['departure_date' => 'date', 'return_date' => 'date', 'capacity' => 'integer', 'reserved' => 'integer', 'is_active' => 'boolean'];

        public function product()
        {
            return $this->belongsTo(Product::class);
        }

        public function option()
        {
            return $this->belongsTo(ProductOption::class, 'product_option_id');
        }
    }
}

if (! class_exists(Inquiry::class)) {
    class Inquiry extends Model
    {
        protected $table = 'travel_lab_inquiries';

        protected $guarded = [];

        protected $casts = ['status' => InquiryStatus::class, 'contact' => 'array', 'total_amount' => 'decimal:2'];

        public function items()
        {
            return $this->hasMany(InquiryItem::class);
        }

        public function user()
        {
            return $this->belongsTo(User::class);
        }
    }
}

if (! class_exists(InquiryItem::class)) {
    class InquiryItem extends Model
    {
        protected $table = 'travel_lab_inquiry_items';

        protected $guarded = [];

        protected $casts = ['product_name' => 'array', 'departure_date' => 'date', 'quantity' => 'integer', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];

        public function departure()
        {
            return $this->belongsTo(Departure::class);
        }

        public function inquiry()
        {
            return $this->belongsTo(Inquiry::class);
        }
    }
}
