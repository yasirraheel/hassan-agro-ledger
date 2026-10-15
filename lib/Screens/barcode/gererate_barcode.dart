import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_typeahead/flutter_typeahead.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:iconly/iconly.dart';
import 'package:mobile_pos/constant.dart';
import 'package:pdf/pdf.dart';
import 'package:printing/printing.dart';
import 'package:pdf/widgets.dart' as pw;

import '../../Provider/product_provider.dart';
import '../Products/Model/product_model.dart';

class BarcodeGeneratorScreen extends StatefulWidget {
  const BarcodeGeneratorScreen({Key? key}) : super(key: key);

  @override
  _BarcodeGeneratorScreenState createState() => _BarcodeGeneratorScreenState();
}

class _BarcodeGeneratorScreenState extends State<BarcodeGeneratorScreen> {
  List<ProductModel> products = [];
  List<SelectedProduct> selectedProducts = [];
  bool showCode = true;
  bool showPrice = true;
  bool showName = true;

  void _addProduct(ProductModel product) {
    setState(() {
      final existingProduct = selectedProducts.firstWhere(
        (p) => p.product.productCode == product.productCode,
        orElse: () => SelectedProduct(product: product, quantity: 0),
      );

      if (existingProduct.quantity > 0) {
        existingProduct.quantity++;
        _showSnackBar('${product.productName} quantity increased.');
      } else {
        selectedProducts.add(SelectedProduct(product: product, quantity: 1));
        // _showSnackBar('${product.productName} added to the list.');
      }
    });
  }

  void _showSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _preview() async {
    final pdf = pw.Document();
    List<pw.Widget> barcodeWidgets = [];

    for (var selectedProduct in selectedProducts) {
      for (int i = 0; i < selectedProduct.quantity; i++) {
        barcodeWidgets.add(pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.center,
          mainAxisAlignment: pw.MainAxisAlignment.center,
          children: [
            if (showName) pw.Text('${selectedProduct.product.productName}', style: const pw.TextStyle(fontSize: 10)),
            pw.SizedBox(height: 2),
            pw.BarcodeWidget(
                drawText: showCode ? true : false,
                data: selectedProduct.product.productCode!,
                barcode: pw.Barcode.code128(),
                width: 80,
                height: 30,
                textPadding: 4,
                textStyle: const pw.TextStyle(fontSize: 8)),
            if (showPrice) pw.Text('Price: ${selectedProduct.product.productSalePrice}', style: const pw.TextStyle(fontSize: 10)),
          ],
        ));
      }
    }

    pdf.addPage(
      pw.MultiPage(
        build: (pw.Context context) {
          return [
            pw.GridView(
              childAspectRatio: 0.68,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              crossAxisCount: 4, // Adjust the number of columns as needed
              children: barcodeWidgets.map((widget) => pw.Container(child: widget)).toList(),
            ),
          ];
        },
      ),
    );

    try {
      await Printing.layoutPdf(onLayout: (PdfPageFormat format) async => await pdf.save());
    } catch (e) {
      print("Error generating PDF: $e");
    }
  }

  void _toggleCheckbox(bool value, Function(bool) updateFunction) {
    setState(() {
      updateFunction(value);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        centerTitle: true,
        title: Text(
          'Barcode Generator',
          style: GoogleFonts.poppins(
            color: Colors.black,
          ),
        ),
        backgroundColor: Colors.white,
      ),
      body: Consumer(
        builder: (context, ref, __) {
          final productData = ref.watch(productProvider);
          return productData.when(
            data: (snapshot) {
              products = snapshot; // Assuming snapshot is a List<ProductModel>
              return Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  children: [
                    //-----------------search_bar
                    TypeAheadField<ProductModel>(
                      builder: (context, controller, focusNode) {
                        return TextField(
                          controller: controller,
                          focusNode: focusNode,
                          autofocus: false,
                          decoration: kInputDecoration.copyWith(
                            fillColor: kWhite,
                            border: const OutlineInputBorder(
                              borderSide: BorderSide(
                                color: kMainColor,
                              ),
                            ),
                            hintText: 'Search product',
                            suffixIcon: Container(
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(8),
                                color: kMainColor,
                              ),
                              child: const Icon(
                                Icons.search,
                                color: Colors.white,
                              ),
                            ),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4),
                          ),
                        );
                      },
                      suggestionsCallback: (pattern) {
                        return products.where((product) => product.productName!.toLowerCase().startsWith(pattern.toLowerCase())).toList();
                      },
                      itemBuilder: (context, ProductModel suggestion) {
                        return Container(
                          color: Colors.white, // Set the background color to white
                          child: ListTile(
                            title: Text(suggestion.productName!),
                            subtitle: Text('Code: ${suggestion.productCode?.toString() ?? '0'}'),
                            trailing: Text('Price: ${suggestion.productSalePrice?.toString() ?? '0'}'),
                          ),
                        );
                      },
                      onSelected: (ProductModel value) {
                        _addProduct(value);
                      },
                    ),
                    const SizedBox(height: 14),
                    //-----------------check_box
                    Row(
                      children: [
                        Checkbox(
                          activeColor: kMainColor,
                          value: showCode,
                          visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
                          onChanged: (bool? value) => _toggleCheckbox(value!, (val) => showCode = val),
                        ),
                        const Flexible(
                          child: Text(
                            'Show Code',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Checkbox(
                          activeColor: kMainColor,
                          value: showPrice,
                          visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
                          onChanged: (bool? value) => _toggleCheckbox(value!, (val) => showPrice = val),
                        ),
                        const Flexible(
                          child: Text(
                            'Show Price',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Checkbox(
                          activeColor: kMainColor,
                          value: showName,
                          visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
                          onChanged: (bool? value) => _toggleCheckbox(value!, (val) => showName = val),
                        ),
                        const Flexible(
                          child: Text(
                            'Show Name',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    //-----------------data_table
                    selectedProducts.isNotEmpty
                        ? SingleChildScrollView(
                            scrollDirection: Axis.horizontal,
                            child: DataTable(
                              headingRowColor: WidgetStateProperty.all(Colors.red.shade50),
                              showBottomBorder: true,
                              horizontalMargin: 8,
                              columns: const [
                                DataColumn(label: Text('Name')),
                                DataColumn(label: Text('Stock')),
                                DataColumn(label: Text('Quantity')),
                                DataColumn(label: Text('Actions')),
                              ],
                              rows: selectedProducts.map((selectedProduct) {
                                final controller = TextEditingController(text: selectedProduct.quantity.toString());
                                return DataRow(
                                  cells: [
                                    DataCell(
                                      Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          Text(
                                            selectedProduct.product.productName!,
                                            style: gTextStyle.copyWith(color: kTitleColor, fontSize: 14),
                                          ),
                                          Text(
                                            selectedProduct.product.productCode!,
                                            style: gTextStyle.copyWith(color: kGreyTextColor, fontSize: 12),
                                          ),
                                        ],
                                      ),
                                    ),
                                    DataCell(
                                      Text(selectedProduct.product.productStock?.toString() ?? '0'),
                                    ),
                                    DataCell(
                                      SizedBox(
                                        height: 38,
                                        child: TextField(
                                          controller: controller,
                                          keyboardType: TextInputType.number,
                                          textAlign: TextAlign.center,
                                          onSubmitted: (value) {
                                            setState(() {
                                              selectedProduct.quantity = int.tryParse(value) ?? 0;
                                            });
                                          },
                                          decoration: const InputDecoration(
                                            border: OutlineInputBorder(),
                                            contentPadding: EdgeInsets.symmetric(vertical: 8.0),
                                          ),
                                        ),
                                      ),
                                    ),
                                    DataCell(
                                      IconButton(
                                        icon: const Icon(Icons.delete),
                                        onPressed: () {
                                          setState(() {
                                            selectedProducts.remove(selectedProduct); // Remove the whole product
                                          });
                                        },
                                      ),
                                    ),
                                  ],
                                );
                              }).toList(),
                            ))
                        : Center(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const SizedBox(height: 50),
                                const Icon(
                                  IconlyLight.document,
                                  color: kMainColor,
                                  size: 70,
                                ),
                                Text(
                                  'No Item selected',
                                  style: gTextStyle.copyWith(
                                    color: Colors.black,
                                    fontSize: 18,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                    const Spacer(),

                    //-----------------submit_button
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8.0),
                        ),
                        backgroundColor: kMainColor,
                        minimumSize: const Size(double.maxFinite, 48),
                        textStyle: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      onPressed: selectedProducts.isNotEmpty
                          ? _preview
                          : () {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('No products selected')),
                              );
                            },
                      label: Text(
                        'Preview PDF',
                        style: gTextStyle.copyWith(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              );
            },
            error: (e, stack) => Text(e.toString()),
            loading: () => const Center(child: CircularProgressIndicator()),
          );
        },
      ),
    );
  }
}

class SelectedProduct {
  final ProductModel product;
  int quantity;

  SelectedProduct({required this.product, required this.quantity});
}
