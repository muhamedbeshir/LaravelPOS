# توثيق واجهة برمجة التطبيقات (API) لنظام نقطة البيع

هذا المستند يوثق واجهة برمجة التطبيقات (API) الخاصة بنظام نقطة البيع، والتي يمكن استخدامها من قبل واجهات أمامية أخرى مثل تطبيقات الموبايل والويب.

## المسار الأساسي

جميع نقاط النهاية تبدأ بالمسار الأساسي التالي:

```
/api
```

جميع المسارات المتعلقة بالمبيعات تبدأ بـ:

```
/api/sales
```

## التنسيق العام للاستجابة

تتبع جميع الاستجابات التنسيق التالي:

-   النجاح:

```json
{
    "success": true,
    "data or other fields": "..."
}
```

-   الخطأ:

```json
{
    "success": false,
    "message": "رسالة الخطأ",
    "error": "تفاصيل الخطأ (اختياري)"
}
```

## المنتجات

### البحث عن المنتجات

يسمح بالبحث عن المنتجات باستخدام النص أو الباركود.

-   **طريقة الطلب:** GET
-   **المسار:** `/products/search`
-   **المعلمات:**

    -   `search` (اختياري): مصطلح البحث النصي
    -   `barcode` (اختياري): الباركود الخاص بالمنتج

-   **الاستجابة الناجحة (منتج واحد):**

```json
{
    "success": true,
    "multiple": false,
    "product": {
        "id": 1,
        "name": "اسم المنتج",
        "barcode": "123456789",
        "stock_quantity": 10,
        "category": {
            "id": 1,
            "name": "اسم الفئة"
        }
    }
}
```

-   **الاستجابة الناجحة (منتجات متعددة):**

```json
{
    "success": true,
    "multiple": true,
    "products": [
        {
            "id": 1,
            "name": "اسم المنتج 1",
            "barcode": "123456789",
            "stock_quantity": 10,
            "category": {
                "id": 1,
                "name": "اسم الفئة"
            }
        }
        // المزيد من المنتجات...
    ]
}
```

### الحصول على قائمة المنتجات

يسترجع قائمة المنتجات مع إمكانية التصفية حسب الفئة.

-   **طريقة الطلب:** GET
-   **المسار:** `/products`
-   **المعلمات:**

    -   `category_id` (اختياري): معرف الفئة للتصفية
    -   `search` (اختياري): مصطلح البحث

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "products": [
        {
            "id": 1,
            "name": "اسم المنتج",
            "barcode": "123456789",
            "stock_quantity": 10,
            "category": {
                "id": 1,
                "name": "اسم الفئة"
            }
        }
        // المزيد من المنتجات...
    ]
}
```

### الحصول على تفاصيل منتج

يسترجع تفاصيل منتج محدد بمعرفه.

-   **طريقة الطلب:** GET
-   **المسار:** `/product/{id}`
-   **المعلمات:**

    -   `id`: معرف المنتج

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "product": {
        "id": 1,
        "name": "اسم المنتج",
        "barcode": "123456789",
        "stock_quantity": 10,
        "category": {
            "id": 1,
            "name": "اسم الفئة"
        },
        "units": [
            {
                "id": 1,
                "name": "وحدة 1",
                "price": 10.0,
                "conversion_factor": 1
            }
            // المزيد من الوحدات...
        ]
    }
}
```

### الحصول على وحدات المنتج وأسعارها

يسترجع وحدات منتج محدد مع أسعارها والمخزون المتوفر.

-   **طريقة الطلب:** GET
-   **المسار:** `/get-product-units/{id}`
-   **المعلمات:**

    -   `id`: معرف المنتج
    -   `price_type` (اختياري): نوع السعر (retail, wholesale, distributor) (الافتراضي: retail)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "units": [
        {
            "id": 1,
            "name": "وحدة 1",
            "price": 10.0,
            "cost": 8.0,
            "stock": 50,
            "conversion_factor": 1,
            "conversion_info": "1 وحدة 1 = 1 الوحدة الرئيسية"
        }
        // المزيد من الوحدات...
    ]
}
```

## الفئات (التصنيفات)

### الحصول على قائمة الفئات

يسترجع قائمة الفئات المتاحة.

-   **طريقة الطلب:** GET
-   **المسار:** `/categories`
-   **المعلمات:**

    -   `active` (اختياري): تصفية حسب الحالة النشطة (true/false)
    -   `search` (اختياري): البحث في اسم الفئة
    -   `per_page` (اختياري): عدد النتائج في الصفحة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "categories": {
        "current_page": 1,
        "data": [
        {
            "id": 1,
            "name": "اسم الفئة",
                "description": "وصف الفئة",
            "color": "#FF5733",
                "active": true,
                "image": "uploads/categories/category1.jpg",
                "created_at": "2023-01-01 12:00:00"
        }
        // المزيد من الفئات...
        ],
        "first_page_url": "http://example.com/api/categories?page=1",
        "from": 1,
        "last_page": 3,
        "last_page_url": "http://example.com/api/categories?page=3",
        "next_page_url": "http://example.com/api/categories?page=2",
        "path": "http://example.com/api/categories",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 40
    }
}
```

### الحصول على تفاصيل فئة

يسترجع تفاصيل فئة محددة بمعرفها.

-   **طريقة الطلب:** GET
-   **المسار:** `/categories/{id}`
-   **المعلمات:**

    -   `id`: معرف الفئة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "category": {
        "id": 1,
        "name": "اسم الفئة",
        "description": "وصف الفئة",
        "color": "#FF5733",
        "active": true,
        "image": "uploads/categories/category1.jpg",
        "created_at": "2023-01-01 12:00:00",
        "products_count": 25
    }
}
```

### إنشاء فئة جديدة

يقوم بإنشاء فئة جديدة.

-   **طريقة الطلب:** POST
-   **المسار:** `/categories`
-   **الجسم (طلب متعدد الأجزاء):**

    -   `name`: اسم الفئة (مطلوب)
    -   `description`: وصف الفئة (اختياري)
    -   `color`: لون الفئة (اختياري)
    -   `active`: حالة نشاط الفئة (اختياري، افتراضي: true)
    -   `image`: صورة الفئة (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم إنشاء الفئة بنجاح",
    "category": {
        "id": 1,
        "name": "اسم الفئة",
        "description": "وصف الفئة",
        "color": "#FF5733",
        "active": true,
        "image": "uploads/categories/category1.jpg",
        "created_at": "2023-01-01 12:00:00"
    }
}
```

### تحديث فئة

يقوم بتحديث بيانات فئة موجودة.

-   **طريقة الطلب:** PUT
-   **المسار:** `/categories/{id}`
-   **المعلمات:**
    -   `id`: معرف الفئة
-   **الجسم (طلب متعدد الأجزاء):**

    -   `name`: اسم الفئة (مطلوب)
    -   `description`: وصف الفئة (اختياري)
    -   `color`: لون الفئة (اختياري)
    -   `active`: حالة نشاط الفئة (اختياري)
    -   `image`: صورة الفئة (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تحديث الفئة بنجاح",
    "category": {
        "id": 1,
        "name": "اسم الفئة المحدث",
        "description": "وصف الفئة المحدث",
        "color": "#3366FF",
        "active": true,
        "image": "uploads/categories/category1_updated.jpg",
        "created_at": "2023-01-01 12:00:00",
        "updated_at": "2023-01-15 10:30:00"
    }
}
```

### حذف فئة

يقوم بحذف فئة.

-   **طريقة الطلب:** DELETE
-   **المسار:** `/categories/{id}`
-   **المعلمات:**

    -   `id`: معرف الفئة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم حذف الفئة بنجاح"
}
```

### تغيير حالة نشاط الفئة

يقوم بتغيير حالة نشاط الفئة (تفعيل/تعطيل).

-   **طريقة الطلب:** PATCH
-   **المسار:** `/categories/{id}/toggle-status`
-   **المعلمات:**

    -   `id`: معرف الفئة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تغيير حالة الفئة بنجاح",
    "active": false
}
```

## العملاء

### الحصول على قائمة العملاء

يسترجع قائمة العملاء.

-   **طريقة الطلب:** GET
-   **المسار:** `/customers`

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "customers": [
        {
            "id": 1,
            "name": "اسم العميل",
            "phone": "0123456789",
            "address": "عنوان العميل",
            "credit_balance": 0.0
        }
        // المزيد من العملاء...
    ]
}
```

### إنشاء عميل جديد

يقوم بإنشاء عميل جديد.

-   **طريقة الطلب:** POST
-   **المسار:** `/customers`
-   **الجسم (JSON):**

```json
{
    "name": "اسم العميل",
    "phone": "0123456789",
    "address": "عنوان العميل"
}
```

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "customer": {
        "id": 1,
        "name": "اسم العميل",
        "phone": "0123456789",
        "address": "عنوان العميل",
        "credit_balance": 0.0
    }
}
```

## الموظفون

### الحصول على قائمة الموظفين

يسترجع قائمة الموظفين مع إمكانية التصفية.

-   **طريقة الطلب:** GET
-   **المسار:** `/employees`
-   **المعلمات:**

    -   `active` (اختياري): حالة نشاط الموظف (true/false)
    -   `job_title` (اختياري): المسمى الوظيفي للموظف
    -   `is_delivery` (اختياري): موظف توصيل (true/false)
    -   `search` (اختياري): البحث في اسم أو هاتف أو عنوان الموظف
    -   `per_page` (اختياري): عدد النتائج في الصفحة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "employees": {
        "current_page": 1,
        "data": [
        {
            "id": 1,
            "name": "اسم الموظف",
            "phone": "0123456789",
                "address": "عنوان الموظف",
                "job_title": "كاشير",
                "salary": 3000,
                "active": true,
                "is_delivery": false,
                "image": "uploads/employees/employee1.jpg",
                "created_at": "2023-01-01 12:00:00"
        }
        // المزيد من الموظفين...
        ],
        "first_page_url": "http://example.com/api/employees?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://example.com/api/employees?page=5",
        "next_page_url": "http://example.com/api/employees?page=2",
        "path": "http://example.com/api/employees",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

### الحصول على تفاصيل موظف

يسترجع تفاصيل موظف محدد بمعرفه.

-   **طريقة الطلب:** GET
-   **المسار:** `/employees/{id}`
-   **المعلمات:**

    -   `id`: معرف الموظف

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "employee": {
        "id": 1,
        "name": "اسم الموظف",
        "phone": "0123456789",
        "address": "عنوان الموظف",
        "job_title": "كاشير",
        "salary": 3000,
        "active": true,
        "is_delivery": false,
        "image": "uploads/employees/employee1.jpg",
        "created_at": "2023-01-01 12:00:00",
        "attendance_summary": {
            "present_days": 22,
            "absent_days": 3,
            "late_days": 5,
            "total_hours": 176
        },
        "salary_summary": {
            "total_paid": 9000,
            "last_payment_date": "2023-03-01",
            "pending_months": ["2023-04"]
        }
    }
}
```

### إنشاء موظف جديد

يقوم بإنشاء موظف جديد.

-   **طريقة الطلب:** POST
-   **المسار:** `/employees`
-   **الجسم (طلب متعدد الأجزاء):**

    -   `name`: اسم الموظف (مطلوب)
    -   `phone`: رقم هاتف الموظف (اختياري)
    -   `address`: عنوان الموظف (اختياري)
    -   `job_title`: المسمى الوظيفي (مطلوب)
    -   `salary`: الراتب (مطلوب)
    -   `active`: حالة نشاط الموظف (اختياري، افتراضي: true)
    -   `is_delivery`: موظف توصيل (اختياري، افتراضي: false)
    -   `image`: صورة الموظف (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم إنشاء الموظف بنجاح",
    "employee": {
        "id": 1,
        "name": "اسم الموظف",
        "phone": "0123456789",
        "address": "عنوان الموظف",
        "job_title": "كاشير",
        "salary": 3000,
        "active": true,
        "is_delivery": false,
        "image": "uploads/employees/employee1.jpg",
        "created_at": "2023-01-01 12:00:00"
    }
}
```

### تحديث بيانات موظف

يقوم بتحديث بيانات موظف موجود.

-   **طريقة الطلب:** PUT
-   **المسار:** `/employees/{id}`
-   **المعلمات:**
    -   `id`: معرف الموظف
-   **الجسم (طلب متعدد الأجزاء):**

    -   `name`: اسم الموظف (مطلوب)
    -   `phone`: رقم هاتف الموظف (اختياري)
    -   `address`: عنوان الموظف (اختياري)
    -   `job_title`: المسمى الوظيفي (مطلوب)
    -   `salary`: الراتب (مطلوب)
    -   `active`: حالة نشاط الموظف (اختياري)
    -   `is_delivery`: موظف توصيل (اختياري)
    -   `image`: صورة الموظف (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تحديث بيانات الموظف بنجاح",
    "employee": {
        "id": 1,
        "name": "اسم الموظف المحدث",
        "phone": "0123456789",
        "address": "عنوان الموظف المحدث",
        "job_title": "مدير",
        "salary": 3500,
        "active": true,
        "is_delivery": false,
        "image": "uploads/employees/employee1_updated.jpg",
        "created_at": "2023-01-01 12:00:00",
        "updated_at": "2023-01-15 10:30:00"
    }
}
```

### تغيير حالة نشاط الموظف

يقوم بتغيير حالة نشاط الموظف (تفعيل/تعطيل).

-   **طريقة الطلب:** PUT
-   **المسار:** `/employees/{id}/toggle-status`
-   **المعلمات:**

    -   `id`: معرف الموظف

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تغيير حالة الموظف بنجاح",
    "active": false
}
```

### الحصول على قائمة المسميات الوظيفية

يسترجع قائمة المسميات الوظيفية المتاحة.

-   **طريقة الطلب:** GET
-   **المسار:** `/employees/job-titles`

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "job_titles": [
        "مدير",
        "كاشير",
        "مندوب توصيل",
        "أمين مخزن"
        // المزيد من المسميات الوظيفية...
    ]
}
```

### تسجيل حضور موظف (تسجيل الدخول)

يقوم بتسجيل حضور موظف (تسجيل الدخول).

-   **طريقة الطلب:** POST
-   **المسار:** `/employees/{id}/check-in`
-   **المعلمات:**
    -   `id`: معرف الموظف
-   **الجسم (JSON):**

    -   `note`: ملاحظة (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تسجيل الحضور بنجاح",
    "attendance": {
        "id": 1,
        "employee_id": 1,
        "check_in_time": "2023-04-01 08:05:00",
        "status": "present",
        "note": "ملاحظة إضافية"
    }
}
```

### تسجيل انصراف موظف (تسجيل الخروج)

يقوم بتسجيل انصراف موظف (تسجيل الخروج).

-   **طريقة الطلب:** POST
-   **المسار:** `/employees/{id}/check-out`
-   **المعلمات:**
    -   `id`: معرف الموظف
-   **الجسم (JSON):**

    -   `note`: ملاحظة (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تسجيل الانصراف بنجاح",
    "attendance": {
        "id": 1,
        "employee_id": 1,
        "check_in_time": "2023-04-01 08:05:00",
        "check_out_time": "2023-04-01 17:00:00",
        "hours_worked": 8.92,
        "status": "present",
        "note": "ملاحظة إضافية"
    }
}
```

### صرف راتب موظف

يقوم بصرف راتب موظف.

-   **طريقة الطلب:** POST
-   **المسار:** `/employees/{id}/pay-salary`
-   **المعلمات:**
    -   `id`: معرف الموظف
-   **الجسم (JSON):**

    -   `amount`: المبلغ المدفوع (مطلوب)
    -   `month`: الشهر (مطلوب، بتنسيق YYYY-MM)
    -   `note`: ملاحظة (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم صرف الراتب بنجاح",
    "salary_payment": {
        "id": 1,
        "employee_id": 1,
        "amount": 3000,
        "month": "2023-04",
        "payment_date": "2023-04-30 14:30:00",
        "note": "راتب شهر أبريل 2023"
    }
}
```

### الحصول على سجل الحضور والانصراف لموظف

يسترجع سجل الحضور والانصراف لموظف محدد.

-   **طريقة الطلب:** GET
-   **المسار:** `/employees/{id}/attendance`
-   **المعلمات:**

    -   `id`: معرف الموظف
    -   `from_date`: تاريخ البداية (اختياري)
    -   `to_date`: تاريخ النهاية (اختياري)
    -   `status`: الحالة (اختياري: present, late, absent)
    -   `per_page`: عدد النتائج في الصفحة (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "attendance": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "employee_id": 1,
                "check_in_time": "2023-04-01 08:05:00",
                "check_out_time": "2023-04-01 17:00:00",
                "hours_worked": 8.92,
                "status": "present",
                "note": "ملاحظة"
            }
            // المزيد من سجلات الحضور...
        ],
        "first_page_url": "http://example.com/api/employees/1/attendance?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://example.com/api/employees/1/attendance?page=5",
        "next_page_url": "http://example.com/api/employees/1/attendance?page=2",
        "path": "http://example.com/api/employees/1/attendance",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

### الحصول على سجل الرواتب لموظف

يسترجع سجل الرواتب المدفوعة لموظف محدد.

-   **طريقة الطلب:** GET
-   **المسار:** `/employees/{id}/salary-history`
-   **المعلمات:**

    -   `id`: معرف الموظف
    -   `from_date`: تاريخ البداية (اختياري)
    -   `to_date`: تاريخ النهاية (اختياري)
    -   `per_page`: عدد النتائج في الصفحة (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "salary_payments": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "employee_id": 1,
                "amount": 3000,
                "month": "2023-04",
                "payment_date": "2023-04-30 14:30:00",
                "note": "راتب شهر أبريل 2023"
            }
            // المزيد من سجلات الرواتب...
        ],
        "first_page_url": "http://example.com/api/employees/1/salary-history?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://example.com/api/employees/1/salary-history?page=5",
        "next_page_url": "http://example.com/api/employees/1/salary-history?page=2",
        "path": "http://example.com/api/employees/1/salary-history",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

## الفواتير

### الحصول على قائمة الفواتير

يسترجع قائمة الفواتير مع إمكانية التصفية.

-   **طريقة الطلب:** GET
-   **المسار:** `/invoices`
-   **المعلمات:**

    -   `customer_id` (اختياري): معرف العميل للتصفية
    -   `invoice_type` (اختياري): نوع الفاتورة (cash, credit)
    -   `order_type` (اختياري): نوع الطلب (takeaway, delivery)
    -   `status` (اختياري): حالة الفاتورة (pending, completed, canceled)
    -   `date_from` (اختياري): تاريخ البداية للتصفية (YYYY-MM-DD)
    -   `date_to` (اختياري): تاريخ النهاية للتصفية (YYYY-MM-DD)
    -   `per_page` (اختياري): عدد النتائج في الصفحة (الافتراضي: 15)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "invoices": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "invoice_number": "202301010001",
                "invoice_type": "cash",
                "order_type": "takeaway",
                "customer": {
                    "id": 1,
                    "name": "اسم العميل"
                },
                "subtotal": 100.0,
                "total_discount": 10.0,
                "total": 90.0,
                "paid_amount": 90.0,
                "remaining": 0.0,
                "status": "completed",
                "created_at": "2023-01-01 12:00:00"
            }
            // المزيد من الفواتير...
        ],
        "first_page_url": "http://example.com/api/sales/invoices?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://example.com/api/sales/invoices?page=5",
        "next_page_url": "http://example.com/api/sales/invoices?page=2",
        "path": "http://example.com/api/sales/invoices",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

### الحصول على تفاصيل فاتورة

يسترجع تفاصيل فاتورة محددة بمعرفها.

-   **طريقة الطلب:** GET
-   **المسار:** `/invoices/{id}`
-   **المعلمات:**

    -   `id`: معرف الفاتورة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "invoice": {
        "id": 1,
        "invoice_number": "202301010001",
        "invoice_type": "cash",
        "order_type": "takeaway",
        "customer": {
            "id": 1,
            "name": "اسم العميل",
            "phone": "0123456789",
            "address": "عنوان العميل"
        },
        "subtotal": 100.0,
        "total_discount": 10.0,
        "total": 90.0,
        "paid_amount": 90.0,
        "remaining": 0.0,
        "status": "completed",
        "created_at": "2023-01-01 12:00:00",
        "items": [
            {
                "id": 1,
                "product": {
                    "id": 1,
                    "name": "اسم المنتج",
                    "barcode": "123456789"
                },
                "unit": {
                    "id": 1,
                    "name": "وحدة"
                },
                "quantity": 2,
                "unit_price": 50.0,
                "discount_percentage": 10,
                "discount_value": 0,
                "total_discount": 10.0,
                "subtotal": 100.0,
                "total": 90.0
            }
            // المزيد من العناصر...
        ]
    }
}
```

### إنشاء فاتورة جديدة

يقوم بإنشاء فاتورة جديدة.

-   **طريقة الطلب:** POST
-   **المسار:** `/invoices`
-   **الجسم (JSON):**

```json
{
    "invoice": {
        "invoice_type": "cash",
        "order_type": "takeaway",
        "customer_id": 1,
        "paid_amount": 90.0,
        "discount_value": 0,
        "discount_percentage": 0,
        "price_type": "retail",
        "delivery_employee_id": null,
        "items": [
            {
                "product_id": 1,
                "unit_id": 1,
                "quantity": 2,
                "unit_price": 50.0,
                "discount_value": 0,
                "discount_percentage": 10
            }
            // المزيد من العناصر...
        ]
    }
}
```

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم إنشاء الفاتورة بنجاح",
    "invoice": {
        "id": 1,
        "invoice_number": "202301010001",
        "invoice_type": "cash",
        "order_type": "takeaway",
        "customer_id": 1,
        "subtotal": 100.0,
        "discount_value": 0,
        "discount_percentage": 0,
        "total_discount": 10.0,
        "total": 90.0,
        "paid_amount": 90.0,
        "remaining": 0.0,
        "price_type": "retail",
        "delivery_employee_id": null,
        "status": "completed",
        "created_at": "2023-01-01 12:00:00",
        "items": [
            {
                "id": 1,
                "invoice_id": 1,
                "product_id": 1,
                "unit_id": 1,
                "quantity": 2,
                "unit_price": 50.0,
                "discount_percentage": 10,
                "discount_value": 0,
                "total_discount": 10.0,
                "subtotal": 100.0,
                "total": 90.0
            }
            // المزيد من العناصر...
        ],
        "customer": {
            "id": 1,
            "name": "اسم العميل",
            "phone": "0123456789",
            "address": "عنوان العميل"
        }
    }
}
```

## الوحدات

### الحصول على قائمة الوحدات

يسترجع قائمة الوحدات المتاحة.

-   **طريقة الطلب:** GET
-   **المسار:** `/units`
-   **المعلمات:**

    -   `search` (اختياري): البحث في اسم الوحدة
    -   `per_page` (اختياري): عدد النتائج في الصفحة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "units": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "قطعة",
                "abbreviation": "ق",
                "is_base": true,
                "created_at": "2023-01-01 12:00:00"
            },
            {
                "id": 2,
                "name": "صندوق",
                "abbreviation": "ص",
                "is_base": false,
                "created_at": "2023-01-01 12:00:00"
            }
            // المزيد من الوحدات...
        ],
        "first_page_url": "http://example.com/api/units?page=1",
        "from": 1,
        "last_page": 2,
        "last_page_url": "http://example.com/api/units?page=2",
        "next_page_url": "http://example.com/api/units?page=2",
        "path": "http://example.com/api/units",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 20
    }
}
```

### الحصول على تفاصيل وحدة

يسترجع تفاصيل وحدة محددة بمعرفها.

-   **طريقة الطلب:** GET
-   **المسار:** `/units/{id}`
-   **المعلمات:**

    -   `id`: معرف الوحدة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "unit": {
        "id": 1,
        "name": "قطعة",
        "abbreviation": "ق",
        "is_base": true,
        "created_at": "2023-01-01 12:00:00",
        "products_count": 15
    }
}
```

### إنشاء وحدة جديدة

يقوم بإنشاء وحدة جديدة.

-   **طريقة الطلب:** POST
-   **المسار:** `/units`
-   **الجسم (JSON):**

    -   `name`: اسم الوحدة (مطلوب)
    -   `abbreviation`: اختصار الوحدة (مطلوب)
    -   `is_base`: وحدة أساسية (اختياري، افتراضي: false)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم إنشاء الوحدة بنجاح",
    "unit": {
        "id": 1,
        "name": "قطعة",
        "abbreviation": "ق",
        "is_base": true,
        "created_at": "2023-01-01 12:00:00"
    }
}
```

### تحديث وحدة

يقوم بتحديث بيانات وحدة موجودة.

-   **طريقة الطلب:** PUT
-   **المسار:** `/units/{id}`
-   **المعلمات:**
    -   `id`: معرف الوحدة
-   **الجسم (JSON):**

    -   `name`: اسم الوحدة (مطلوب)
    -   `abbreviation`: اختصار الوحدة (مطلوب)
    -   `is_base`: وحدة أساسية (اختياري)

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تحديث الوحدة بنجاح",
    "unit": {
        "id": 1,
        "name": "قطعة محدثة",
        "abbreviation": "ق م",
        "is_base": true,
        "created_at": "2023-01-01 12:00:00",
        "updated_at": "2023-01-15 10:30:00"
    }
}
```

### حذف وحدة

يقوم بحذف وحدة.

-   **طريقة الطلب:** DELETE
-   **المسار:** `/units/{id}`
-   **المعلمات:**

    -   `id`: معرف الوحدة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم حذف الوحدة بنجاح"
}
```

## أمثلة الاستخدام

### استخدام نقطة البيع مع تطبيق موبايل

1. البحث عن منتج بالباركود:

```javascript
fetch("/api/sales/products/search?barcode=123456789")
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            if (!data.multiple) {
                // تم العثور على منتج واحد، عرض تفاصيله
                displayProduct(data.product);
            } else {
                // تم العثور على منتجات متعددة، عرض قائمة للاختيار
                displayProductList(data.products);
            }
        } else {
            // عرض رسالة الخطأ
            showError(data.message);
        }
    });
```

2. إنشاء فاتورة جديدة:

```javascript
const invoiceData = {
    invoice: {
        invoice_type: "cash",
        order_type: "takeaway",
        customer_id: 1,
        paid_amount: 90.0,
        discount_value: 0,
        discount_percentage: 0,
        price_type: "retail",
        items: [
            {
                product_id: 1,
                unit_id: 1,
                quantity: 2,
                unit_price: 50.0,
                discount_value: 0,
                discount_percentage: 10,
            },
        ],
    },
};

fetch("/api/sales/invoices", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content"),
    },
    body: JSON.stringify(invoiceData),
})
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            // تم إنشاء الفاتورة بنجاح
            showSuccess(data.message);
            // يمكن فتح صفحة الطباعة
            if (shouldPrint) {
                window.open(
                    `/sales/invoices/${data.invoice.id}/print`,
                    "_blank"
                );
            }
        } else {
            // عرض رسالة الخطأ
            showError(data.message);
        }
    });
```

## تأمين API

يجب تأمين الوصول إلى واجهة برمجة التطبيقات (API) باستخدام أحد الطرق التالية:

1. **توثيق Laravel Sanctum**: مناسب للتطبيقات الأمامية التي تستخدم نفس النطاق.
2. **توكن API مخصص**: مناسب للتطبيقات الخارجية.
3. **OAuth2**: مناسب لتطبيقات الطرف الثالث.

للاستفادة من هذه الميزات، يجب إضافة middleware التوثيق المناسب إلى مسارات API.

## رموز الحالة HTTP

-   `200 OK`: الطلب ناجح
-   `201 Created`: تم إنشاء الكائن بنجاح
-   `400 Bad Request`: طلب غير صالح
-   `401 Unauthorized`: غير مصرح به (التوثيق مطلوب)
-   `403 Forbidden`: غير مسموح بالوصول
-   `404 Not Found`: المورد غير موجود
-   `422 Unprocessable Entity`: المدخلات غير صالحة
-   `500 Internal Server Error`: خطأ في الخادم

## المشتريات

### الحصول على قائمة المشتريات

يسترجع قائمة المشتريات مع إمكانية التصفية.

-   **طريقة الطلب:** GET
-   **المسار:** `/purchases`
-   **المعلمات:**

    -   `supplier_id` (اختياري): معرف المورد للتصفية
    -   `status` (اختياري): حالة المشتريات (pending, completed, canceled)
    -   `date_from` (اختياري): تاريخ البداية للتصفية (YYYY-MM-DD)
    -   `date_to` (اختياري): تاريخ النهاية للتصفية (YYYY-MM-DD)
    -   `per_page` (اختياري): عدد النتائج في الصفحة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "purchases": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "purchase_number": "PO-202304-0001",
                "supplier": {
                    "id": 1,
                    "name": "اسم المورد"
                },
                "total_amount": 5000,
                "paid_amount": 3000,
                "remaining_amount": 2000,
                "status": "pending",
                "purchase_date": "2023-04-01",
                "created_at": "2023-04-01 12:00:00"
            }
            // المزيد من المشتريات...
        ],
        "first_page_url": "http://example.com/api/purchases?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://example.com/api/purchases?page=5",
        "next_page_url": "http://example.com/api/purchases?page=2",
        "path": "http://example.com/api/purchases",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

### الحصول على تفاصيل مشتريات

يسترجع تفاصيل مشتريات محددة بمعرفها.

-   **طريقة الطلب:** GET
-   **المسار:** `/purchases/{id}`
-   **المعلمات:**

    -   `id`: معرف المشتريات

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "purchase": {
        "id": 1,
        "purchase_number": "PO-202304-0001",
        "supplier": {
            "id": 1,
            "name": "اسم المورد",
            "phone": "0123456789",
            "address": "عنوان المورد"
        },
        "total_amount": 5000,
        "paid_amount": 3000,
        "remaining_amount": 2000,
        "status": "pending",
        "notes": "ملاحظات عامة على المشتريات",
        "purchase_date": "2023-04-01",
        "created_at": "2023-04-01 12:00:00",
        "items": [
            {
                "id": 1,
                "product": {
                    "id": 1,
                    "name": "اسم المنتج",
                    "barcode": "123456789"
                },
                "unit": {
                    "id": 1,
                    "name": "وحدة"
                },
                "quantity": 100,
                "unit_cost": 50,
                "total_cost": 5000
            }
            // المزيد من العناصر...
        ],
        "payments": [
            {
                "id": 1,
                "amount": 3000,
                "payment_date": "2023-04-01",
                "payment_method": "cash",
                "notes": "دفعة أولى"
            }
            // المزيد من الدفعات...
        ]
    }
}
```

### إنشاء مشتريات جديدة

يقوم بإنشاء مشتريات جديدة.

-   **طريقة الطلب:** POST
-   **المسار:** `/purchases`
-   **الجسم (JSON):**

```json
{
    "supplier_id": 1,
    "purchase_date": "2023-04-01",
    "paid_amount": 3000,
    "status": "pending",
    "notes": "ملاحظات عامة على المشتريات",
    "items": [
        {
            "product_id": 1,
            "unit_id": 1,
            "quantity": 100,
            "unit_cost": 50
        }
        // المزيد من العناصر...
    ]
}
```

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم إنشاء المشتريات بنجاح",
    "purchase": {
        "id": 1,
        "purchase_number": "PO-202304-0001",
        "supplier_id": 1,
        "total_amount": 5000,
        "paid_amount": 3000,
        "remaining_amount": 2000,
        "status": "pending",
        "notes": "ملاحظات عامة على المشتريات",
        "purchase_date": "2023-04-01",
        "created_at": "2023-04-01 12:00:00"
    }
}
```

### تحديث مشتريات

يقوم بتحديث بيانات مشتريات موجودة.

-   **طريقة الطلب:** PUT
-   **المسار:** `/purchases/{id}`
-   **المعلمات:**
    -   `id`: معرف المشتريات
-   **الجسم (JSON):**

```json
{
    "supplier_id": 1,
    "purchase_date": "2023-04-01",
    "paid_amount": 4000,
    "status": "completed",
    "notes": "ملاحظات محدثة على المشتريات",
    "items": [
        {
            "product_id": 1,
            "unit_id": 1,
            "quantity": 100,
            "unit_cost": 50
        }
        // المزيد من العناصر...
    ]
}
```

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تحديث المشتريات بنجاح",
    "purchase": {
        "id": 1,
        "purchase_number": "PO-202304-0001",
        "supplier_id": 1,
        "total_amount": 5000,
        "paid_amount": 4000,
        "remaining_amount": 1000,
        "status": "completed",
        "notes": "ملاحظات محدثة على المشتريات",
        "purchase_date": "2023-04-01",
        "created_at": "2023-04-01 12:00:00",
        "updated_at": "2023-04-02 10:30:00"
    }
}
```

### حذف مشتريات

يقوم بحذف مشتريات.

-   **طريقة الطلب:** DELETE
-   **المسار:** `/purchases/{id}`
-   **المعلمات:**

    -   `id`: معرف المشتريات

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم حذف المشتريات بنجاح"
}
```

### الحصول على مشتريات مورد

يسترجع قائمة المشتريات لمورد محدد.

-   **طريقة الطلب:** GET
-   **المسار:** `/purchases/suppliers/{id}/purchases`
-   **المعلمات:**

    -   `id`: معرف المورد
    -   `status` (اختياري): حالة المشتريات (pending, completed, canceled)
    -   `date_from` (اختياري): تاريخ البداية للتصفية (YYYY-MM-DD)
    -   `date_to` (اختياري): تاريخ النهاية للتصفية (YYYY-MM-DD)
    -   `per_page` (اختياري): عدد النتائج في الصفحة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "purchases": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "purchase_number": "PO-202304-0001",
                "total_amount": 5000,
                "paid_amount": 3000,
                "remaining_amount": 2000,
                "status": "pending",
                "purchase_date": "2023-04-01",
                "created_at": "2023-04-01 12:00:00"
            }
            // المزيد من المشتريات...
        ],
        "first_page_url": "http://example.com/api/purchases/suppliers/1/purchases?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://example.com/api/purchases/suppliers/1/purchases?page=5",
        "next_page_url": "http://example.com/api/purchases/suppliers/1/purchases?page=2",
        "path": "http://example.com/api/purchases/suppliers/1/purchases",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

## الموردون

### الحصول على قائمة الموردين

يسترجع قائمة الموردين مع إمكانية التصفية.

-   **طريقة الطلب:** GET
-   **المسار:** `/suppliers`
-   **المعلمات:**

    -   `search` (اختياري): البحث في اسم أو هاتف أو عنوان المورد
    -   `status` (اختياري): حالة المورد (active, inactive)
    -   `has_balance` (اختياري): يحتوي على رصيد متبقي (true/false)
    -   `per_page` (اختياري): عدد النتائج في الصفحة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "suppliers": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "اسم المورد",
                "phone": "0123456789",
                "address": "عنوان المورد",
                "contact_person": "اسم الشخص المسؤول",
                "email": "supplier@example.com",
                "balance": 2000,
                "active": true,
                "created_at": "2023-01-01 12:00:00"
            }
            // المزيد من الموردين...
        ],
        "first_page_url": "http://example.com/api/suppliers?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://example.com/api/suppliers?page=5",
        "next_page_url": "http://example.com/api/suppliers?page=2",
        "path": "http://example.com/api/suppliers",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

### الحصول على تفاصيل مورد

يسترجع تفاصيل مورد محدد بمعرفه.

-   **طريقة الطلب:** GET
-   **المسار:** `/suppliers/{id}`
-   **المعلمات:**

    -   `id`: معرف المورد

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "supplier": {
        "id": 1,
        "name": "اسم المورد",
        "phone": "0123456789",
        "address": "عنوان المورد",
        "contact_person": "اسم الشخص المسؤول",
        "email": "supplier@example.com",
        "balance": 2000,
        "active": true,
        "created_at": "2023-01-01 12:00:00",
        "purchase_summary": {
            "total_purchases": 10,
            "total_amount": 50000,
            "paid_amount": 48000,
            "remaining_amount": 2000
        }
    }
}
```

### إنشاء مورد جديد

يقوم بإنشاء مورد جديد.

-   **طريقة الطلب:** POST
-   **المسار:** `/suppliers`
-   **الجسم (JSON):**

```json
{
    "name": "اسم المورد",
    "phone": "0123456789",
    "address": "عنوان المورد",
    "contact_person": "اسم الشخص المسؤول",
    "email": "supplier@example.com",
    "active": true
}
```

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم إنشاء المورد بنجاح",
    "supplier": {
        "id": 1,
        "name": "اسم المورد",
        "phone": "0123456789",
        "address": "عنوان المورد",
        "contact_person": "اسم الشخص المسؤول",
        "email": "supplier@example.com",
        "balance": 0,
        "active": true,
        "created_at": "2023-01-01 12:00:00"
    }
}
```

### تحديث مورد

يقوم بتحديث بيانات مورد موجود.

-   **طريقة الطلب:** PUT
-   **المسار:** `/suppliers/{id}`
-   **المعلمات:**
    -   `id`: معرف المورد
-   **الجسم (JSON):**

```json
{
    "name": "اسم المورد المحدث",
    "phone": "0123456789",
    "address": "عنوان المورد المحدث",
    "contact_person": "اسم الشخص المسؤول المحدث",
    "email": "updated_supplier@example.com",
    "active": true
}
```

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تحديث المورد بنجاح",
    "supplier": {
        "id": 1,
        "name": "اسم المورد المحدث",
        "phone": "0123456789",
        "address": "عنوان المورد المحدث",
        "contact_person": "اسم الشخص المسؤول المحدث",
        "email": "updated_supplier@example.com",
        "balance": 2000,
        "active": true,
        "created_at": "2023-01-01 12:00:00",
        "updated_at": "2023-01-15 10:30:00"
    }
}
```

### حذف مورد

يقوم بحذف مورد.

-   **طريقة الطلب:** DELETE
-   **المسار:** `/suppliers/{id}`
-   **المعلمات:**

    -   `id`: معرف المورد

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم حذف المورد بنجاح"
}
```

### تسجيل دفعة للمورد

يقوم بتسجيل دفعة للمورد.

-   **طريقة الطلب:** POST
-   **المسار:** `/suppliers/{id}/payments`
-   **المعلمات:**
    -   `id`: معرف المورد
-   **الجسم (JSON):**

```json
{
    "amount": 1000,
    "payment_date": "2023-04-01",
    "payment_method": "cash",
    "notes": "دفعة جزئية"
}
```

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "message": "تم تسجيل الدفعة بنجاح",
    "payment": {
        "id": 1,
        "supplier_id": 1,
        "amount": 1000,
        "payment_date": "2023-04-01",
        "payment_method": "cash",
        "notes": "دفعة جزئية",
        "created_at": "2023-04-01 12:00:00"
    },
    "new_balance": 1000
}
```

### الحصول على سجل الدفعات للمورد

يسترجع سجل الدفعات لمورد محدد.

-   **طريقة الطلب:** GET
-   **المسار:** `/suppliers/{id}/payment-history`
-   **المعلمات:**

    -   `id`: معرف المورد
    -   `date_from` (اختياري): تاريخ البداية للتصفية (YYYY-MM-DD)
    -   `date_to` (اختياري): تاريخ النهاية للتصفية (YYYY-MM-DD)
    -   `per_page` (اختياري): عدد النتائج في الصفحة

-   **الاستجابة الناجحة:**

```json
{
    "success": true,
    "payments": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "supplier_id": 1,
                "amount": 1000,
                "payment_date": "2023-04-01",
                "payment_method": "cash",
                "notes": "دفعة جزئية",
                "created_at": "2023-04-01 12:00:00"
            }
            // المزيد من الدفعات...
        ],
        "first_page_url": "http://example.com/api/suppliers/1/payment-history?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://example.com/api/suppliers/1/payment-history?page=5",
        "next_page_url": "http://example.com/api/suppliers/1/payment-history?page=2",
        "path": "http://example.com/api/suppliers/1/payment-history",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```
