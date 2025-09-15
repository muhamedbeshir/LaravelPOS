<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::with('parentUnit', 'childUnits')
                    ->orderBy('is_base_unit', 'desc')
                    ->orderBy('name')
                    ->get();
        
        return view('units.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::where('is_active', true)->get();
        return view('units.create', compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // تعديل القيم قبل التحقق
            $data = $request->all();
            $data['is_base_unit'] = $request->boolean('is_base_unit', false);
            $data['is_active'] = true;
            
            // إنشاء كود فريد للوحدة
            $data['code'] = $this->generateUniqueCode($data['name']);
            
            if ($data['is_base_unit']) {
                $data['parent_unit_id'] = null;
                $data['conversion_factor'] = 1;
            }

            // التحقق من صحة البيانات
            $rules = [
                'name' => 'required|string|max:255|unique:units',
                'code' => 'required|string|max:50|unique:units',
                'is_base_unit' => 'boolean',
                'is_active' => 'boolean'
            ];

            // إضافة قواعد التحقق للوحدات الفرعية
            if (!$data['is_base_unit']) {
                $rules['parent_unit_id'] = 'required|exists:units,id';
                $rules['conversion_factor'] = 'required|numeric|min:0.01';
            }

            $messages = [
                'name.required' => 'يرجى إدخال اسم الوحدة',
                'name.unique' => 'اسم الوحدة مستخدم بالفعل',
                'code.unique' => 'كود الوحدة مستخدم بالفعل',
                'parent_unit_id.required' => 'يرجى اختيار الوحدة الأم',
                'parent_unit_id.exists' => 'الوحدة الأم غير موجودة',
                'conversion_factor.required' => 'يرجى إدخال معامل التحويل',
                'conversion_factor.numeric' => 'معامل التحويل يجب أن يكون رقماً',
                'conversion_factor.min' => 'معامل التحويل يجب أن يكون أكبر من الصفر'
            ];

            $validator = Validator::make($data, $rules, $messages);

            if ($validator->fails()) {
                if ($request->ajax() || $request->has('ajax') || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => 'فشل التحقق من البيانات',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // التحقق من الوحدة الأم إذا كانت وحدة فرعية
            if (!$data['is_base_unit']) {
                $parentUnit = Unit::findOrFail($data['parent_unit_id']);
                
                if (!$parentUnit->is_active) {
                    throw new \Exception('الوحدة الأم غير نشطة');
                }

                // التأكد من أن معامل التحويل رقم موجب
                if (!isset($data['conversion_factor']) || $data['conversion_factor'] <= 0) {
                    throw new \Exception('يجب إدخال معامل تحويل صحيح أكبر من الصفر');
                }
            }

            // إنشاء الوحدة
            $unit = Unit::create($data);

            if (!$unit) {
                throw new \Exception('فشل في إنشاء الوحدة');
            }

            DB::commit();
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->has('ajax') || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إضافة الوحدة بنجاح',
                    'unit' => $unit
                ]);
            }
            
            return redirect()->route('units.index')
                ->with('success', 'تم إضافة الوحدة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('خطأ في إنشاء وحدة: ' . $e->getMessage());
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->has('ajax') || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حفظ الوحدة: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حفظ الوحدة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        $units = Unit::where('id', '!=', $unit->id)
                    ->where('is_active', true)
                    ->get();
        return view('units.edit', compact('unit', 'units'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        try {
            DB::beginTransaction();

            // تعديل القيم قبل التحقق
            $data = $request->all();
            $data['is_base_unit'] = $request->boolean('is_base_unit');
            
            if ($data['is_base_unit']) {
                $data['parent_unit_id'] = null;
                $data['conversion_factor'] = 1;
            }

            // التحقق من صحة البيانات
            $rules = [
                'name' => 'required|string|max:255|unique:units,name,' . $unit->id,
                'is_base_unit' => 'boolean'
            ];

            // إضافة قواعد التحقق للوحدات الفرعية
            if (!$data['is_base_unit']) {
                $rules['parent_unit_id'] = 'required|exists:units,id';
                $rules['conversion_factor'] = 'required|numeric|min:0.01';
            }

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // التحقق من الوحدة الأم إذا كانت وحدة فرعية
            if (!$data['is_base_unit']) {
                if ($unit->childUnits()->exists()) {
                    throw new \Exception('لا يمكن تحويل الوحدة إلى وحدة فرعية لأنها تحتوي على وحدات فرعية');
                }

                $parentUnit = Unit::findOrFail($data['parent_unit_id']);
                
                if (!$parentUnit->is_active) {
                    throw new \Exception('الوحدة الأم غير نشطة');
                }

                // التحقق من عدم وجود دورة في العلاقات
                if ($this->wouldCreateCycle($unit, $data['parent_unit_id'])) {
                    throw new \Exception('لا يمكن إنشاء علاقة دائرية بين الوحدات');
                }

                // التأكد من أن معامل التحويل رقم موجب
                if (!isset($data['conversion_factor']) || $data['conversion_factor'] <= 0) {
                    throw new \Exception('يجب إدخال معامل تحويل صحيح أكبر من الصفر');
                }
            }

            $unit->update($data);

            DB::commit();
            return redirect()->route('units.index')
                ->with('success', 'تم تحديث الوحدة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث الوحدة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        try {
            DB::beginTransaction();

            if ($unit->childUnits()->exists()) {
                throw new \Exception('لا يمكن حذف الوحدة لأنها مرتبطة بوحدات أخرى');
            }

            $unit->delete();

            DB::commit();
            return redirect()->route('units.index')
                ->with('success', 'تم حذف الوحدة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف الوحدة: ' . $e->getMessage());
        }
    }

    private function wouldCreateCycle(Unit $unit, $newParentId)
    {
        $parent = Unit::find($newParentId);
        while ($parent) {
            if ($parent->id === $unit->id) {
                return true;
            }
            $parent = $parent->parentUnit;
        }
        return false;
    }

    public function toggleActive(Unit $unit)
    {
        try {
            DB::beginTransaction();

            if ($unit->is_active && $unit->childUnits()->where('is_active', true)->exists()) {
                throw new \Exception('لا يمكن تعطيل الوحدة لأنها تحتوي على وحدات فرعية نشطة');
            }

            $unit->is_active = !$unit->is_active;
            $unit->save();

            DB::commit();
            return redirect()->route('units.index')
                ->with('success', 'تم تحديث حالة الوحدة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث حالة الوحدة: ' . $e->getMessage());
        }
    }

    public function export()
    {
        try {
            // إنشاء ملف Excel جديد
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // تعيين اتجاه الورقة من اليمين لليسار
            $sheet->setRightToLeft(true);

            // تنسيق العناوين
            $sheet->getStyle('A1:G1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            // تعيين ارتفاع الصف الأول
            $sheet->getRowDimension(1)->setRowHeight(30);

            // إضافة العناوين
            $headers = [
                'الاسم',
                'النوع',
                'الوحدة الأم',
                'معامل التحويل المباشر',
                'معامل التحويل الكلي',
                'الحالة',
                'تاريخ الإنشاء'
            ];
            $sheet->fromArray([$headers], NULL, 'A1');

            // جلب البيانات
            $units = Unit::with('parentUnit')->orderBy('is_base_unit', 'desc')->orderBy('name')->get();
            
            $row = 2;
            foreach ($units as $unit) {
                $data = [
                    $unit->name,
                    $unit->is_base_unit ? 'وحدة أساسية' : 'وحدة فرعية',
                    $unit->parentUnit ? $unit->parentUnit->name : '-',
                    $unit->is_base_unit ? '-' : $unit->getDirectConversionText(),
                    $unit->is_base_unit ? '-' : $unit->conversion_text,
                    $unit->is_active ? 'نشط' : 'غير نشط',
                    $unit->created_at->format('Y-m-d H:i')
                ];
                
                $sheet->fromArray([$data], NULL, 'A' . $row);
                
                // تنسيق الصف
                $sheet->getStyle('A'.$row.':G'.$row)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $row++;
            }

            // تعديل عرض الأعمدة لتناسب المحتوى
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // إنشاء ملف Excel
            $writer = new Xlsx($spreadsheet);
            
            // تحديد اسم الملف
            $fileName = 'units_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // تحديد headers لتنزيل الملف
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');

            // حفظ الملف مباشرة للتحميل
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تصدير الوحدات: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique code for a unit based on its name
     */
    private function generateUniqueCode($name)
    {
        // Take first 3 characters of name, convert to uppercase
        $nameCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->arabicToEnglish($name))), 0, 3);
        
        // Add a random number
        $randomNumber = mt_rand(100, 999);
        
        $code = $nameCode . $randomNumber;
        
        // Check if code exists
        while (Unit::where('code', $code)->exists()) {
            $randomNumber = mt_rand(100, 999);
            $code = $nameCode . $randomNumber;
        }
        
        return $code;
    }
    
    /**
     * Helper method to convert Arabic characters to English
     */
    private function arabicToEnglish($string)
    {
        $arabic = ['أ', 'ا', 'إ', 'آ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ة'];
        $english = ['a', 'a', 'e', 'a', 'b', 't', 'th', 'j', 'h', 'kh', 'd', 'th', 'r', 'z', 's', 'sh', 's', 'd', 't', 'th', 'aa', 'gh', 'f', 'k', 'k', 'l', 'm', 'n', 'h', 'w', 'y', 'a'];
        
        return str_replace($arabic, $english, $string);
    }
}
