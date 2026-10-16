<?php

namespace Modules\Business\App\Http\Controllers;

use App\Models\Unit;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\Stock;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Modules\Business\App\Exports\ExportProduct;

class AcnooProductController extends Controller
{
    use HasUploader;

    public function index()
    {
        $products = Product::with('unit:id,unitName', 'brand:id,brandName', 'category:id,categoryName', 'stocks')->where('business_id', auth()->user()->business_id)->latest()->paginate(20);
        return view('business::products.index', compact('products'));
    }

    public function acnooFilter(Request $request)
    {
        $search = $request->input('search');
        $products = Product::with('unit:id,unitName', 'brand:id,brandName', 'category:id,categoryName', 'stocks')->where('business_id', auth()->user()->business_id)->when($search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('productName', 'like', '%' . $search . '%')
                  ->orWhere('productCode', 'like', '%' . $search . '%')
                  ->orWhere('productPurchasePrice', 'like', '%' . $search . '%')
                  ->orWhere('productSalePrice', 'like', '%' . $search . '%')
                  ->orWhere('productStock', 'like', '%' . $search . '%')
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('categoryName', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('brand', function ($q) use ($search) {
                        $q->where('brandName', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('unit', function ($q) use ($search) {
                        $q->where('unitName', 'like', '%' . $search . '%');
                    });

            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('business::products.datas', compact('products'))->render()
            ]);
        }

        return redirect(url()->previous());
    }


    public function create()
    {
        $categories = Category::where('business_id', auth()->user()->business_id)->whereStatus(1)->latest()->get();
        $brands = Brand::where('business_id', auth()->user()->business_id)->whereStatus(1)->latest()->get();
        $units = Unit::where('business_id', auth()->user()->business_id)->whereStatus(1)->latest()->get();
        $product_id = (Product::max('id') ?? 0) + 1;
        $code = str_pad($product_id , 4, '0', STR_PAD_LEFT);

        return view('business::products.create', compact('categories', 'brands', 'units', 'code'));
    }


    public function store(Request $request)
    {
         $request->validate([
            'productName' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'productCode' => 'required|string|max:255|unique:products,productCode',
            'productPicture' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'productDealerPrice' => 'nullable|numeric|min:0',
            'productPurchasePrice' => 'required|numeric|min:0',
            'productSalePrice' => 'required|numeric|min:0',
            'productWholeSalePrice' => 'nullable|numeric|min:0',
            'productStock' => 'nullable|integer|min:0',
            'size' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'weight' => 'nullable|string|max:255',
            'capacity' => 'nullable|string|max:255',
            'productManufacturer' => 'nullable|string|max:255',
        ]);

        $product_id = (Product::max('id') ?? 0) + 1;
        $code = str_pad($product_id , 4, '0', STR_PAD_LEFT);

        $product = Product::create($request->except(['productPicture', 'productCode', 'productDealerPrice', 'productWholeSalePrice', 'productStock']) + [
            'productCode' => $request->productCode ?? $code,
            'productPicture' => $request->productPicture ? $this->upload($request, 'productPicture') : NULL,
            'productSalePrice' => $request->productSalePrice,
            'productDealerPrice' => $request->productDealerPrice ?? $request->productSalePrice,
            'productWholeSalePrice' => $request->productWholeSalePrice ?? $request->productSalePrice,
            'productStock' => $request->productStock ?? 0,
            'business_id' => auth()->user()->business_id,
        ]);

        Stock::create([
            'business_id' => auth()->user()->business_id,
            'product_id' => $product->id,
            'batch_no' => $product->productCode,
            'productStock' => $request->productStock ?? 0,
            'exclusive_price' => $request->productPurchasePrice ?? 0,
            'productPurchasePrice' => $request->productPurchasePrice ?? 0,
            'productSalePrice' => $request->productSalePrice ?? 0,
            'productWholeSalePrice' => $request->productWholeSalePrice ?? $request->productSalePrice,
            'productDealerPrice' => $request->productDealerPrice ?? $request->productSalePrice,
        ]);

        return response()->json([
            'message' => __('Product saved successfully.'),
            'redirect' => route('business.products.index')
        ]);
    }


    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::where('business_id', auth()->user()->business_id)->whereStatus(1)->latest()->get();
        $brands = Brand::where('business_id', auth()->user()->business_id)->whereStatus(1)->latest()->get();
        $units = Unit::where('business_id', auth()->user()->business_id)->whereStatus(1)->latest()->get();

        return view('business::products.edit', compact('categories', 'brands', 'units', 'product'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'productName' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'productPicture' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'productDealerPrice' => 'nullable|numeric|min:0',
            'productPurchasePrice' => 'required|numeric|min:0',
            'productSalePrice' => 'required|numeric|min:0',
            'productWholeSalePrice' => 'nullable|numeric|min:0',
            'productStock' => 'nullable|integer|min:0',
            'size' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'weight' => 'nullable|string|max:255',
            'capacity' => 'nullable|string|max:255',
            'productManufacturer' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($id);

        $product->update($request->except(['productPicture', 'productDealerPrice', 'productWholeSalePrice', 'productStock']) + [
            'productPicture' => $request->productPicture ? $this->upload($request, 'productPicture', $product->productPicture) : $product->productPicture,
            'productSalePrice' => $request->productSalePrice,
            'productDealerPrice' => $request->productDealerPrice ?? $request->productSalePrice,
            'productWholeSalePrice' => $request->productWholeSalePrice ?? $request->productSalePrice,
            'productStock' => $request->productStock ?? 0,
            'business_id' => auth()->user()->business_id,
        ]);

        $stock = Stock::where('product_id', $product->id)->first();
        if ($stock) {
            $stock->update([
                'productStock' => $request->productStock ?? 0,
                'exclusive_price' => $request->productPurchasePrice ?? 0,
                'productPurchasePrice' => $request->productPurchasePrice ?? 0,
                'productSalePrice' => $request->productSalePrice ?? 0,
                'productWholeSalePrice' => $request->productWholeSalePrice ?? $request->productSalePrice,
                'productDealerPrice' => $request->productDealerPrice ?? $request->productSalePrice,
            ]);
        } else {
            Stock::create([
                'business_id' => auth()->user()->business_id,
                'product_id' => $product->id,
                'batch_no' => $product->productCode,
                'productStock' => $request->productStock ?? 0,
                'exclusive_price' => $request->productPurchasePrice ?? 0,
                'productPurchasePrice' => $request->productPurchasePrice ?? 0,
                'productSalePrice' => $request->productSalePrice ?? 0,
                'productWholeSalePrice' => $request->productWholeSalePrice ?? $request->productSalePrice,
                'productDealerPrice' => $request->productDealerPrice ?? $request->productSalePrice,
            ]);
        }

        return response()->json([
            'message' => __('Data saved successfully.'),
            'redirect' => route('business.products.index')
        ]);
    }


    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if(file_exists($product->productPicture)) {
            Storage::delete($product->productPicture);
        }
        $product->delete();

        return response()->json([
            'message' => __('Product deleted successfully'),
            'redirect' => route('business.products.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
       $products = Product::whereIn('id', $request->ids)->get();

        foreach($products as $product) {
            if(file_exists($product->productPicture)) {
                Storage::delete($product->productPicture);
            }
        }
        Product::whereIn('id', $request->ids)->delete();
        return response()->json([
            'message'   => __('Selected product deleted successfully'),
            'redirect'  => route('business.products.index')
        ]);
    }

    public function generatePDF(Request $request)
    {
        $products = Product::with('unit:id,unitName', 'brand:id,brandName', 'category:id,categoryName')->where('business_id', auth()->user()->business_id)->latest()->get();
        $pdf = Pdf::loadView('business::products.pdf', compact('products'));
        return $pdf->download('product.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ExportProduct, 'product.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportProduct, 'product.csv');
    }
}
