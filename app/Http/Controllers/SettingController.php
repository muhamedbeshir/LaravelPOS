<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    protected static array $middlewares = [
        'auth',
        'permission:view-settings' => ['only' => ['index', 'general', 'pos']],
        'permission:edit-settings' => ['only' => ['update']],
    ];

    public function index()
    {
        $settings = new \stdClass();

        // Inventory Settings
        $settings->allow_negative = (bool)Setting::get('allow_negative_inventory', false);
        $settings->subtract_inventory = (bool)Setting::get('subtract_inventory_on_zero', false);
        $settings->show_colors_options = (bool)Setting::get('show_colors_options', true);
        $settings->show_sizes_options = (bool)Setting::get('show_sizes_options', true);

        // General Settings
        $settings->store_name = Setting::get('store_name', '');
        $settings->store_phone = Setting::get('store_phone', '');
        $settings->store_address = Setting::get('store_address', '');
        $settings->tax_number = Setting::get('tax_number', '');
        $settings->currency_symbol = Setting::get('currency_symbol', 'ج.م');
        $settings->currency_position = Setting::get('currency_position', 'after');
        $settings->decimal_places = (int)Setting::get('decimal_places', 2);
        $settings->default_customer = Setting::get('default_customer', 1); // Assuming 1 is a valid default ID
        $settings->default_supplier = Setting::get('default_supplier', 1); // Assuming 1 is a valid default ID

        // Sales Settings
        $settings->show_profit_in_summary = (bool)Setting::get('show_profit_in_summary', false);
        $settings->show_profit_in_sales_table = (bool)Setting::get('show_profit_in_sales_table', false);
        $settings->show_expiry_dates = (bool)Setting::get('show_expiry_dates', false);
        $settings->default_price_type = Setting::get('default_price_type', ''); // Default to empty or a valid PriceType code
        $settings->allow_selling_at_different_prices = (bool)Setting::get('allow_selling_at_different_prices', true);
        $settings->allow_price_edit_during_sale = (bool)Setting::get('allow_price_edit_during_sale', true);
        $settings->show_units_modal_on_product_barcode = (bool)Setting::get('show_units_modal_on_product_barcode', true);

        // Purchases Settings
        $settings->show_profit_in_purchases = (bool)Setting::get('show_profit_in_purchases', false);
        $settings->show_expiry_in_purchases = (bool)Setting::get('show_expiry_in_purchases', false);
        $settings->update_all_units_cost = (bool)Setting::get('update_all_units_cost', true);

        // Printing Settings
        $settings->receipt_header = Setting::get('receipt_header', '');
        $settings->receipt_footer = Setting::get('receipt_footer', 'شكراً لتعاملكم معنا');
        $settings->receipt_size = Setting::get('receipt_size', '80mm');
        $settings->show_logo_in_receipt = (bool)Setting::get('show_logo_in_receipt', true);
        $settings->show_tax_in_invoice = (bool)Setting::get('show_tax_in_invoice', false);
        $settings->show_customer_info = (bool)Setting::get('show_customer_info', true);
        $settings->auto_print_after_save = (bool)Setting::get('auto_print_after_save', false);
        $settings->barcode_type = Setting::get('barcode_type', 'CODE128');
        $settings->barcode_label_size = Setting::get('barcode_label_size', 'medium');
        $settings->show_price_in_barcode = (bool)Setting::get('show_price_in_barcode', true);
        
        // Logo and Images Settings
        $settings->header_logo = Setting::get('header_logo', '');
        $settings->footer_logo = Setting::get('footer_logo', '');
        $settings->header_text_below_logo = Setting::get('header_text_below_logo', '');
        $settings->footer_text_above_logo = Setting::get('footer_text_above_logo', '');
        $settings->show_header_logo = (bool)Setting::get('show_header_logo', true);
        $settings->show_footer_logo = (bool)Setting::get('show_footer_logo', true);
        
        // Store Information for Printing
        $settings->print_store_name = Setting::get('print_store_name', '');
        $settings->print_store_address = Setting::get('print_store_address', '');
        $settings->print_store_phone = Setting::get('print_store_phone', '');
        $settings->show_store_info = (bool)Setting::get('show_store_info', true);
        $settings->store_info_at_bottom = (bool)Setting::get('store_info_at_bottom', true);

        // Barcode Settings
        $settings->barcode_label_width = (int)Setting::get('barcode_label_width', 38);
        $settings->barcode_label_height = (int)Setting::get('barcode_label_height', 25);
        $settings->barcode_dpi = (int)Setting::get('barcode_dpi', 300);
        $settings->barcode_show_product_name = (bool)Setting::get('barcode_show_product_name', true);
        $settings->barcode_show_price = (bool)Setting::get('barcode_show_price', false);
        $settings->barcode_show_store_name = (bool)Setting::get('barcode_show_store_name', false);
        $settings->barcode_show_barcode_number = (bool)Setting::get('barcode_show_barcode_number', true);
        $settings->barcode_price_type = Setting::get('barcode_price_type', '1');
        $settings->barcode_font_size = (int)Setting::get('barcode_font_size', 10);
        $settings->barcode_height = (int)Setting::get('barcode_height', 50);
        $settings->barcode_width_factor = (int)Setting::get('barcode_width_factor', 2);
        $settings->barcode_labels_per_row = (int)Setting::get('barcode_labels_per_row', 3);
        $settings->barcode_margin_horizontal = (float)Setting::get('barcode_margin_horizontal', 2);
        $settings->barcode_margin_vertical = (float)Setting::get('barcode_margin_vertical', 2);
        
        // تحويل نوع الباركود إلى القيم الصحيحة
        $barcodeType = Setting::get('barcode_type', 'C128');
        $validTypes = ['C128', 'C39', 'EAN13', 'UPCA'];
        if ($barcodeType === 'CODE128') {
            $barcodeType = 'C128';
            Setting::set('barcode_type', 'C128'); // تحديث في قاعدة البيانات
        }
        if (!in_array($barcodeType, $validTypes)) {
            $barcodeType = 'C128';
            Setting::set('barcode_type', 'C128'); // تحديث في قاعدة البيانات
        }
        $settings->barcode_type = $barcodeType;

        // Notifications Settings
        $settings->low_stock_notification = (bool)Setting::get('low_stock_notification', true);
        $settings->low_stock_threshold = (int)Setting::get('low_stock_threshold', 10);
        $settings->out_of_stock_notification = (bool)Setting::get('out_of_stock_notification', true);
        $settings->expiry_date_notification = (bool)Setting::get('expiry_date_notification', true);
        $settings->expiry_notification_days = (int)Setting::get('expiry_notification_days', 30);
        $settings->new_sale_notification = (bool)Setting::get('new_sale_notification', false);
        $settings->daily_sales_report = (bool)Setting::get('daily_sales_report', false);
        $settings->notification_in_app = (bool)Setting::get('notification_in_app', true);
        $settings->notification_email = (bool)Setting::get('notification_email', false);
        $settings->notification_email_address = Setting::get('notification_email_address', '');
        
        return view('settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        // Debug the request
        \Log::info('Settings form submitted', [
            'request_data' => $request->all(), 
        ]);

        // Get the active tab
        $activeTab = $request->input('tab', 'inventory');
        $input = $request->all();

        // Define validation rules based on the active tab
        $rules = ['tab' => 'required|string'];

        if ($activeTab === 'inventory') {
            $rules = array_merge($rules, [
                'allow_negative' => 'nullable|boolean',
                'subtract_inventory' => 'nullable|boolean',
                'show_colors_options' => 'nullable|boolean',
                'show_sizes_options' => 'nullable|boolean',
            ]);
            // Convert checkbox values
        $input['allow_negative'] = $request->has('allow_negative');
        $input['subtract_inventory'] = $request->has('subtract_inventory');
        $input['show_colors_options'] = $request->has('show_colors_options');
        $input['show_sizes_options'] = $request->has('show_sizes_options');
        } elseif ($activeTab === 'general') {
            $rules = array_merge($rules, [
                'store_name' => 'nullable|string|max:255',
                'store_phone' => 'nullable|string|max:50',
                'store_address' => 'nullable|string|max:500',
                'tax_number' => 'nullable|string|max:100',
                'currency_symbol' => 'nullable|string|max:10',
                'currency_position' => 'nullable|string|in:before,after',
                'decimal_places' => 'nullable|integer|min:0|max:3',
                'default_customer' => 'nullable|exists:customers,id',
                'default_supplier' => 'nullable|exists:suppliers,id',
            ]);
        } elseif ($activeTab === 'sales') {
            $rules = array_merge($rules, [
                'show_profit_in_summary' => 'nullable|boolean',
                'show_profit_in_sales_table' => 'nullable|boolean',
                'show_expiry_dates' => 'nullable|boolean',
                'default_price_type' => 'nullable|string|exists:price_types,code',
                'allow_selling_at_different_prices' => 'nullable|boolean',
                'allow_price_edit_during_sale' => 'nullable|boolean',
                'show_units_modal_on_product_barcode' => 'nullable|boolean',
            ]);
            // Convert checkbox values
        $input['show_profit_in_summary'] = $request->has('show_profit_in_summary');
            $input['show_profit_in_sales_table'] = $request->has('show_profit_in_sales_table');
            $input['show_expiry_dates'] = $request->has('show_expiry_dates');
            $input['allow_selling_at_different_prices'] = $request->has('allow_selling_at_different_prices');
            $input['allow_price_edit_during_sale'] = $request->has('allow_price_edit_during_sale');
            $input['show_units_modal_on_product_barcode'] = $request->has('show_units_modal_on_product_barcode');
        } elseif ($activeTab === 'purchases') {
            $rules = array_merge($rules, [
                'show_profit_in_purchases' => 'nullable|boolean',
                'show_expiry_in_purchases' => 'nullable|boolean',
                'update_all_units_cost' => 'nullable|boolean',
            ]);
            // Convert checkbox values
            $input['show_profit_in_purchases'] = $request->has('show_profit_in_purchases');
            $input['show_expiry_in_purchases'] = $request->has('show_expiry_in_purchases');
            $input['update_all_units_cost'] = $request->has('update_all_units_cost');
        } elseif ($activeTab === 'employees') {
            $rules = array_merge($rules, [
                'count_salaries_as_expenses' => 'nullable|boolean',
                'salary_display_frequency' => 'nullable|string|in:monthly,weekly',
            ]);
            $input['count_salaries_as_expenses'] = $request->has('count_salaries_as_expenses');
            $input['salary_display_frequency'] = $request->input('salary_display_frequency');

            Setting::set('count_salaries_as_expenses', $input['count_salaries_as_expenses'] ?? true);
            Setting::set('salary_display_frequency', $input['salary_display_frequency']);
        } elseif ($activeTab === 'printing') {
            $rules = array_merge($rules, [
                'receipt_header' => 'nullable|string|max:1000',
                'receipt_footer' => 'nullable|string|max:1000',
                'receipt_size' => 'nullable|string|in:58mm,80mm,a4',
                'show_logo_in_receipt' => 'nullable|boolean',
                'show_tax_in_invoice' => 'nullable|boolean',
                'show_customer_info' => 'nullable|boolean',
                'auto_print_after_save' => 'nullable|boolean',
                'barcode_type' => 'nullable|string|max:50',
                'barcode_label_size' => 'nullable|string|in:small,medium,large',
                'show_price_in_barcode' => 'nullable|boolean',
                // Logo and Images
                'header_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'footer_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'header_text_below_logo' => 'nullable|string|max:500',
                'footer_text_above_logo' => 'nullable|string|max:500',
                'show_header_logo' => 'nullable|boolean',
                'show_footer_logo' => 'nullable|boolean',
                'remove_header_logo' => 'nullable|boolean',
                'remove_footer_logo' => 'nullable|boolean',
                // Store Information for Printing
                'print_store_name' => 'nullable|string|max:255',
                'print_store_address' => 'nullable|string|max:500',
                'print_store_phone' => 'nullable|string|max:50',
                'show_store_info' => 'nullable|boolean',
                'store_info_at_bottom' => 'nullable|boolean',
            ]);
            
            // Add print_layout to the rules
            $rules['print_layout'] = 'nullable|string|in:layout_1,layout_2';
            
            // Convert checkbox values
            $input['show_logo_in_receipt'] = $request->has('show_logo_in_receipt');
            $input['show_tax_in_invoice'] = $request->has('show_tax_in_invoice');
            $input['show_customer_info'] = $request->has('show_customer_info');
            $input['auto_print_after_save'] = $request->has('auto_print_after_save');
            $input['show_price_in_barcode'] = $request->has('show_price_in_barcode');
            $input['show_header_logo'] = $request->has('show_header_logo');
            $input['show_footer_logo'] = $request->has('show_footer_logo');
            $input['remove_header_logo'] = $request->has('remove_header_logo');
            $input['remove_footer_logo'] = $request->has('remove_footer_logo');
            $input['show_store_info'] = $request->has('show_store_info');
            $input['store_info_at_bottom'] = $request->has('store_info_at_bottom');
        } elseif ($activeTab === 'barcode') {
            $rules = array_merge($rules, [
                'barcode_label_width' => 'nullable|integer|min:10|max:200',
                'barcode_label_height' => 'nullable|integer|min:10|max:200',
                'barcode_dpi' => 'nullable|integer|min:72|max:600',
                'barcode_show_product_name' => 'nullable|boolean',
                'barcode_show_price' => 'nullable|boolean',
                'barcode_show_store_name' => 'nullable|boolean',
                'barcode_show_barcode_number' => 'nullable|boolean',
                'barcode_price_type' => 'nullable|exists:price_types,id',
                'barcode_font_size' => 'nullable|integer|min:6|max:20',
                'barcode_height' => 'nullable|integer|min:20|max:100',
                'barcode_width_factor' => 'nullable|integer|min:1|max:5',
                'barcode_labels_per_row' => 'nullable|integer|min:1|max:10',
                'barcode_margin_horizontal' => 'nullable|numeric|min:0|max:10',
                'barcode_margin_vertical' => 'nullable|numeric|min:0|max:10',
            ]);
            // Convert checkbox values
            $input['barcode_show_product_name'] = $request->has('barcode_show_product_name');
            $input['barcode_show_price'] = $request->has('barcode_show_price');
            $input['barcode_show_store_name'] = $request->has('barcode_show_store_name');
            $input['barcode_show_barcode_number'] = $request->has('barcode_show_barcode_number');
        } elseif ($activeTab === 'notifications') {
            $rules = array_merge($rules, [
                'low_stock_notification' => 'nullable|boolean',
                'low_stock_threshold' => 'nullable|integer|min:1',
                'out_of_stock_notification' => 'nullable|boolean',
                'expiry_date_notification' => 'nullable|boolean',
                'expiry_notification_days' => 'nullable|integer|min:1',
                'new_sale_notification' => 'nullable|boolean',
                'daily_sales_report' => 'nullable|boolean',
                'notification_in_app' => 'nullable|boolean',
                'notification_email' => 'nullable|boolean',
                'notification_email_address' => 'nullable|email|max:255',
            ]);
            // Convert checkbox values
            $input['low_stock_notification'] = $request->has('low_stock_notification');
            $input['out_of_stock_notification'] = $request->has('out_of_stock_notification');
            $input['expiry_date_notification'] = $request->has('expiry_date_notification');
            $input['new_sale_notification'] = $request->has('new_sale_notification');
            $input['daily_sales_report'] = $request->has('daily_sales_report');
            $input['notification_in_app'] = $request->has('notification_in_app');
            $input['notification_email'] = $request->has('notification_email');
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            \Log::error('Settings validation failed', ['tab' => $activeTab, 'errors' => $validator->errors()->toArray()]);
            return redirect()->route('settings.index', ['tab' => $activeTab])
                ->withErrors($validator)
                ->withInput();
        }
        
        \Log::info("Processing settings for tab: {$activeTab}", ['input_data' => $input]);
        
        // Save settings based on the active tab
        if ($activeTab === 'inventory') {
            $allowNegativeInventory = $input['allow_negative'];
            $subtractInventory = $allowNegativeInventory ? $input['subtract_inventory'] : false;
            Setting::set('allow_negative_inventory', $allowNegativeInventory);
            Setting::set('subtract_inventory_on_zero', $subtractInventory);
            Setting::set('show_colors_options', $input['show_colors_options']);
            Setting::set('show_sizes_options', $input['show_sizes_options']);
        } elseif ($activeTab === 'general') {
            Setting::set('store_name', $request->input('store_name'));
            Setting::set('store_phone', $request->input('store_phone'));
            Setting::set('store_address', $request->input('store_address'));
            Setting::set('tax_number', $request->input('tax_number'));
            Setting::set('currency_symbol', $request->input('currency_symbol'));
            Setting::set('currency_position', $request->input('currency_position'));
            Setting::set('decimal_places', $request->input('decimal_places'));
            Setting::set('default_customer', $request->input('default_customer'));
            Setting::set('default_supplier', $request->input('default_supplier'));
        } elseif ($activeTab === 'sales') {
            Setting::set('show_profit_in_summary', $input['show_profit_in_summary']);
            Setting::set('show_profit_in_sales_table', $input['show_profit_in_sales_table']);
            Setting::set('show_expiry_dates', $input['show_expiry_dates']);
            Setting::set('default_price_type', $request->input('default_price_type'));
            Setting::set('allow_selling_at_different_prices', $input['allow_selling_at_different_prices']);
            Setting::set('allow_price_edit_during_sale', $input['allow_price_edit_during_sale']);
            Setting::set('show_units_modal_on_product_barcode', $input['show_units_modal_on_product_barcode']);
        } elseif ($activeTab === 'purchases') {
            Setting::set('show_profit_in_purchases', $input['show_profit_in_purchases']);
            Setting::set('show_expiry_in_purchases', $input['show_expiry_in_purchases']);
            Setting::set('update_all_units_cost', $input['update_all_units_cost']);
        } elseif ($activeTab === 'employees') {
            Setting::set('count_salaries_as_expenses', $input['count_salaries_as_expenses'] ?? true);
            Setting::set('salary_display_frequency', $input['salary_display_frequency']);
        } elseif ($activeTab === 'printing') {
            // Handle image uploads
            if ($request->hasFile('header_logo')) {
                $headerLogoPath = $request->file('header_logo')->store('logos', 'public');
                Setting::set('header_logo', $headerLogoPath);
            }
            
            if ($request->hasFile('footer_logo')) {
                $footerLogoPath = $request->file('footer_logo')->store('logos', 'public');
                Setting::set('footer_logo', $footerLogoPath);
            }
            
            // Handle logo removal
            if ($input['remove_header_logo']) {
                $currentHeaderLogo = Setting::get('header_logo');
                if ($currentHeaderLogo && \Storage::disk('public')->exists($currentHeaderLogo)) {
                    \Storage::disk('public')->delete($currentHeaderLogo);
                }
                Setting::set('header_logo', '');
            }
            
            if ($input['remove_footer_logo']) {
                $currentFooterLogo = Setting::get('footer_logo');
                if ($currentFooterLogo && \Storage::disk('public')->exists($currentFooterLogo)) {
                    \Storage::disk('public')->delete($currentFooterLogo);
                }
                Setting::set('footer_logo', '');
            }
            
            // Save text fields
            Setting::set('header_text_below_logo', $request->input('header_text_below_logo'));
            Setting::set('footer_text_above_logo', $request->input('footer_text_above_logo'));
            Setting::set('show_header_logo', $input['show_header_logo']);
            Setting::set('show_footer_logo', $input['show_footer_logo']);
            
            // Store Information for Printing
            Setting::set('print_store_name', $request->input('print_store_name'));
            Setting::set('print_store_address', $request->input('print_store_address'));
            Setting::set('print_store_phone', $request->input('print_store_phone'));
            Setting::set('show_store_info', $input['show_store_info']);
            Setting::set('store_info_at_bottom', $input['store_info_at_bottom']);
            
            // Receipt Settings
            Setting::set('receipt_header', $request->input('receipt_header'));
            Setting::set('receipt_footer', $request->input('receipt_footer'));
            Setting::set('receipt_size', $request->input('receipt_size'));
            Setting::set('show_logo_in_receipt', $input['show_logo_in_receipt']);
            Setting::set('show_tax_in_invoice', $input['show_tax_in_invoice']);
            Setting::set('show_customer_info', $input['show_customer_info']);
            Setting::set('auto_print_after_save', $input['auto_print_after_save']);
            Setting::set('barcode_type', $request->input('barcode_type'));
            Setting::set('barcode_label_size', $request->input('barcode_label_size'));
            Setting::set('show_price_in_barcode', $input['show_price_in_barcode']);
            
            // Save print_layout setting
            if ($request->has('print_layout')) {
                Setting::set('print_layout', $request->input('print_layout'));
            }
        } elseif ($activeTab === 'barcode') {
            // Barcode Size Settings
            Setting::set('barcode_label_width', $request->input('barcode_label_width', 38));
            Setting::set('barcode_label_height', $request->input('barcode_label_height', 25));
            Setting::set('barcode_dpi', $request->input('barcode_dpi', 300));
            
            // Barcode Display Settings
            Setting::set('barcode_show_product_name', $input['barcode_show_product_name']);
            Setting::set('barcode_show_price', $input['barcode_show_price']);
            Setting::set('barcode_show_store_name', $input['barcode_show_store_name']);
            Setting::set('barcode_show_barcode_number', $input['barcode_show_barcode_number']);
            
            // Barcode Price Type (only save if show price is enabled)
            if ($input['barcode_show_price']) {
                Setting::set('barcode_price_type', $request->input('barcode_price_type', '1'));
            }
            
            // Barcode Appearance Settings
            Setting::set('barcode_font_size', $request->input('barcode_font_size', 10));
            Setting::set('barcode_height', $request->input('barcode_height', 50));
            Setting::set('barcode_width_factor', $request->input('barcode_width_factor', 2));
            Setting::set('barcode_labels_per_row', $request->input('barcode_labels_per_row', 3));
            Setting::set('barcode_margin_horizontal', $request->input('barcode_margin_horizontal', 2));
            Setting::set('barcode_margin_vertical', $request->input('barcode_margin_vertical', 2));
        } elseif ($activeTab === 'notifications') {
            Setting::set('low_stock_notification', $input['low_stock_notification']);
            if ($input['low_stock_notification']) {
                Setting::set('low_stock_threshold', $request->input('low_stock_threshold', 10));
            }
            Setting::set('out_of_stock_notification', $input['out_of_stock_notification']);
            Setting::set('expiry_date_notification', $input['expiry_date_notification']);
            if ($input['expiry_date_notification']) {
                Setting::set('expiry_notification_days', $request->input('expiry_notification_days', 30));
            }
            Setting::set('new_sale_notification', $input['new_sale_notification']);
            Setting::set('daily_sales_report', $input['daily_sales_report']);
            Setting::set('notification_in_app', $input['notification_in_app']);
            Setting::set('notification_email', $input['notification_email']);
            if ($input['notification_email']) {
                Setting::set('notification_email_address', $request->input('notification_email_address'));
            }
        }

        // Redirect back to the correct tab
        return back()->with([
            'success' => 'تم تحديث الإعدادات بنجاح!',
            'settings_updated' => true,
            'tab' => $request->input('tab', 'inventory')
        ]);
    }

    public function verifyDeletePassword(Request $request)
    {
        // This functionality has been removed from settings
        // But keeping this endpoint for backward compatibility
        return response()->json([
            'valid' => true,
            'message' => 'كلمة المرور صحيحة'
        ]);
    }

    /**
     * Get inventory settings
     */
    public function getInventorySettings()
    {
        $settings = [
            'allow_negative_inventory' => (bool)Setting::get('allow_negative_inventory', false),
            'subtract_inventory_on_zero' => (bool)Setting::get('subtract_inventory_on_zero', false),
            'show_profit_in_summary' => (bool)Setting::get('show_profit_in_summary', true)
        ];
        
        return response()->json([
            'settings' => $settings
        ]);
    }

    public function inventory()
    {
        return response()->json([
            'settings' => [
                'allow_negative_inventory' => \App\Models\Setting::get('allow_negative_inventory', false),
                'subtract_inventory_on_zero' => \App\Models\Setting::get('subtract_inventory_on_zero', false),
                'show_profit_in_summary' => \App\Models\Setting::get('show_profit_in_summary', true),
            ]
        ]);
    }
} 