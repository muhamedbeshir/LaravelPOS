# نظام نقاط الولاء - دليل شامل

## نظرة عامة

نظام نقاط الولاء هو نظام متكامل لإدارة نقاط العملاء في نظام نقاط البيع. يتيح النظام للعملاء كسب نقاط من مشترياتهم واستخدامها للحصول على مكافآت وخصومات.

## المميزات الرئيسية

### 1. أنواع حساب النقاط

-   **لكل منتج**: نقاط لكل منتج في الفاتورة
-   **لكل فاتورة**: نقاط ثابتة لكل فاتورة
-   **لكل مبلغ**: نقاط لكل وحدة مالية (مثل كل جنيه)
-   **لكل فئة**: نقاط لكل فئة منتجات
-   **مخصص**: حساب مخصص للنقاط

### 2. إدارة القواعد

-   إنشاء قواعد نقاط متعددة
-   تحديد أولويات القواعد
-   تحديد حدود النقاط (يومياً، شهرياً، لكل معاملة)
-   تحديد تواريخ البداية والانتهاء
-   استثناء أو تضمين منتجات وفئات محددة

### 3. إدارة النقاط

-   تتبع النقاط المكتسبة والمستخدمة
-   انتهاء صلاحية النقاط (افتراضياً بعد سنة)
-   تعديل النقاط يدوياً
-   معاملات مفصلة للنقاط

### 4. التقارير والإحصائيات

-   إحصائيات شاملة للنقاط
-   تقارير العملاء
-   رسوم بيانية تفاعلية
-   تتبع النقاط المنتهية الصلاحية

## البنية التقنية

### الجداول الرئيسية

#### 1. `customer_loyalty_points`

```sql
- customer_id (FK)
- points (النقاط الحالية)
- total_earned_points (إجمالي النقاط المكتسبة)
- total_used_points (إجمالي النقاط المستخدمة)
- total_expired_points (إجمالي النقاط المنتهية)
- is_active (تفعيل النقاط للعميل)
- last_points_activity (آخر نشاط)
```

#### 2. `loyalty_point_rules`

```sql
- name (اسم القاعدة)
- description (وصف القاعدة)
- calculation_type (نوع الحساب)
- points_value (قيمة النقاط)
- minimum_amount (الحد الأدنى للمبلغ)
- maximum_points_per_transaction (الحد الأقصى لكل معاملة)
- maximum_points_per_day (الحد الأقصى يومياً)
- maximum_points_per_month (الحد الأقصى شهرياً)
- priority (الأولوية)
- start_date (تاريخ البداية)
- end_date (تاريخ الانتهاء)
- is_active (تفعيل القاعدة)
```

#### 3. `customer_loyalty_point_transactions`

```sql
- customer_id (FK)
- loyalty_point_rule_id (FK)
- invoice_id (FK)
- user_id (FK)
- transaction_type (نوع المعاملة)
- points (عدد النقاط)
- points_before (النقاط قبل المعاملة)
- points_after (النقاط بعد المعاملة)
- amount (المبلغ المرتبط)
- description (وصف المعاملة)
- expiry_date (تاريخ انتهاء الصلاحية)
```

### النماذج (Models)

#### 1. CustomerLoyaltyPoints

```php
// العلاقات
public function customer()
public function transactions()

// الطرق
public function addPoints($points, $description, $invoiceId, $ruleId)
public function usePoints($points, $description, $invoiceId)
public function expirePoints($points, $description)
public function adjustPoints($points, $description, $type)
public function getAvailablePoints()
public function getExpiringPoints($days = 30)
public function getPointsHistory($limit = 10)
public function getPointsSummary()
```

#### 2. LoyaltyPointRule

```php
// العلاقات
public function transactions()

// الطرق
public function isValid()
public function calculatePoints($invoice, $customer)
public function getCalculationTypeText()
public function getStatusText()
public function getStatusClass()
```

#### 3. CustomerLoyaltyPointTransaction

```php
// العلاقات
public function customer()
public function rule()
public function invoice()
public function user()

// الطرق
public function getTransactionTypeText()
public function getTransactionTypeClass()
public function getTransactionTypeIcon()
public function isExpired()
public function isExpiringSoon($days = 30)
public function getDaysUntilExpiry()
```

### الخدمات (Services)

#### LoyaltyPointsService

```php
// الطرق الرئيسية
public function calculatePointsForInvoice(Invoice $invoice)
public function addPointsForInvoice(Invoice $invoice)
public function usePointsInInvoice(Invoice $invoice, $pointsToUse)
public function adjustCustomerPoints(Customer $customer, $points, $description, $type)
public function processExpiredPoints()
public function getLoyaltyPointsStats()
public function getCustomerPointsReport(Customer $customer, $startDate, $endDate)
```

## الاستخدام

### 1. إنشاء قاعدة نقاط جديدة

```php
// مثال: قاعدة نقاط لكل جنيه
$rule = LoyaltyPointRule::create([
    'name' => 'نقاط لكل جنيه',
    'description' => 'يحصل العميل على نقطة واحدة لكل جنيه ينفقه',
    'calculation_type' => 'per_amount',
    'points_value' => 1.0,
    'minimum_amount' => 10.0,
    'maximum_points_per_transaction' => 1000.0,
    'is_active' => true,
    'priority' => 100
]);
```

### 2. إضافة نقاط للعميل

```php
$loyaltyService = new LoyaltyPointsService();
$loyaltyService->addPointsForInvoice($invoice);
```

### 3. استخدام نقاط العميل

```php
$customer->useLoyaltyPoints(50, 'استخدام نقاط في فاتورة', $invoice->id);
```

### 4. تعديل نقاط العميل يدوياً

```php
$loyaltyService->adjustCustomerPoints($customer, 100, 'مكافأة خاصة', 'bonus');
```

## الأوامر (Commands)

### معالجة النقاط المنتهية الصلاحية

```bash
php artisan loyalty:process-expired
```

### جدولة المعالجة التلقائية

```php
// في app/Console/Kernel.php
$schedule->command('loyalty:process-expired')
    ->daily()
    ->at('02:00');
```

## المسارات (Routes)

```php
// قواعد النقاط
Route::prefix('loyalty-points/rules')->name('loyalty-points.rules.')->group(function () {
    Route::get('/', [LoyaltyPointRuleController::class, 'index'])->name('index');
    Route::get('/create', [LoyaltyPointRuleController::class, 'create'])->name('create');
    Route::post('/', [LoyaltyPointRuleController::class, 'store'])->name('store');
    Route::get('/{rule}', [LoyaltyPointRuleController::class, 'show'])->name('show');
    Route::get('/{rule}/edit', [LoyaltyPointRuleController::class, 'edit'])->name('edit');
    Route::put('/{rule}', [LoyaltyPointRuleController::class, 'update'])->name('update');
    Route::delete('/{rule}', [LoyaltyPointRuleController::class, 'destroy'])->name('destroy');
});

// إحصائيات النقاط
Route::get('/loyalty-points/statistics', function() {
    $loyaltyService = new LoyaltyPointsService();
    $stats = $loyaltyService->getLoyaltyPointsStats();
    return view('loyalty-points.stats', compact('stats'));
})->name('loyalty-points.statistics');
```

## التكامل مع نظام المبيعات

### إضافة النقاط تلقائياً

```php
// في SalesController::storeInvoice()
if ($invoice->customer && $invoice->customer->isLoyaltyPointsEnabled()) {
    $loyaltyService = new LoyaltyPointsService();
    $loyaltyService->addPointsForInvoice($invoice);
}
```

### عرض النقاط في صفحة العميل

```php
// في customers/show.blade.php
<span class="badge bg-warning">{{ number_format($customer->loyalty_points, 2) }} نقطة</span>
```

## الإعدادات الافتراضية

### قواعد النقاط الافتراضية

1. **نقاط لكل جنيه**: نقطة واحدة لكل جنيه (الحد الأدنى: 10 جنيه)
2. **مكافأة الفاتورة**: 10 نقاط لكل فاتورة (الحد الأدنى: 50 جنيه)
3. **نقاط المنتجات**: 5 نقاط لكل منتج (الحد الأدنى: 20 جنيه)
4. **مكافأة المشتريات الكبيرة**: نقطتان لكل جنيه للمشتريات فوق 500 جنيه
5. **مكافأة العميل الجديد**: 50 نقطة للعملاء الجدد في أول 3 فواتير

## الأمان والصلاحيات

### الصلاحيات المطلوبة

-   `loyalty-points.view`: عرض قواعد النقاط
-   `loyalty-points.create`: إنشاء قواعد نقاط
-   `loyalty-points.edit`: تعديل قواعد النقاط
-   `loyalty-points.delete`: حذف قواعد النقاط

### التحقق من الصلاحيات

```php
$this->middleware('permission:loyalty-points.view')->only(['index', 'show']);
$this->middleware('permission:loyalty-points.create')->only(['create', 'store']);
$this->middleware('permission:loyalty-points.edit')->only(['edit', 'update']);
$this->middleware('permission:loyalty-points.delete')->only(['destroy']);
```

## الصيانة والدعم

### فحص صحة النظام

```bash
# فحص النقاط المنتهية الصلاحية
php artisan loyalty:process-expired

# فحص إحصائيات النظام
php artisan tinker
>>> $service = new App\Services\LoyaltyPointsService();
>>> $stats = $service->getLoyaltyPointsStats();
>>> dd($stats);
```

### النسخ الاحتياطي

```bash
# نسخ احتياطي لجداول النقاط
mysqldump -u username -p database_name customer_loyalty_points loyalty_point_rules customer_loyalty_point_transactions > loyalty_points_backup.sql
```

## التطوير المستقبلي

### الميزات المخطط لها

1. **نظام المكافآت**: استبدال النقاط بمكافآت وخصومات
2. **مستويات العملاء**: مستويات مختلفة مع قواعد نقاط مختلفة
3. **العروض الموسمية**: عروض خاصة لفترات محددة
4. **التنبيهات**: إشعارات للعملاء عن النقاط المنتهية الصلاحية
5. **التطبيق المحمول**: واجهة للعملاء لمراجعة نقاطهم

### التحسينات المقترحة

1. **الأداء**: تحسين استعلامات قاعدة البيانات
2. **التخزين المؤقت**: تخزين مؤقت للإحصائيات
3. **التوازي**: معالجة متوازية للنقاط المنتهية الصلاحية
4. **التقارير المتقدمة**: تقارير تحليلية مفصلة

## الدعم الفني

للمساعدة والدعم الفني، يرجى التواصل مع فريق التطوير أو مراجعة الوثائق التقنية.
