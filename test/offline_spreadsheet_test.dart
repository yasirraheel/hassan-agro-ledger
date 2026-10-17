import 'dart:io';
import 'package:excel/excel.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_pos/offline/offline_spreadsheet.dart';

void main() {
  test('XLSX and quoted CSV preserve product codes, numbers and text', () async {
    final folder = await Directory.systemTemp.createTemp('offline-import-');
    addTearDown(() => folder.delete(recursive: true));
    final excel = Excel.createExcel();
    excel[excel.getDefaultSheet()!].appendRow([
      TextCellValue('Product'),
      TextCellValue('Code'),
      TextCellValue('Price'),
    ]);
    excel[excel.getDefaultSheet()!].appendRow([
      TextCellValue('Seed, premium'),
      TextCellValue('00123'),
      DoubleCellValue(2.5),
    ]);
    final file = File('${folder.path}/test.xlsx');
    await file.writeAsBytes(excel.encode()!);
    final rows = await readProductSpreadsheet(file);
    expect(rows[1], ['Seed, premium', '00123', 2.5]);
    final csv = File('${folder.path}/test.csv');
    await csv.writeAsString('Product,Code,Price\n"Seed, premium",00123,2.5\n');
    final csvRows = await readProductSpreadsheet(csv);
    expect(csvRows[1][0], 'Seed, premium');
    expect(csvRows[1][1], '00123');
  });
}
