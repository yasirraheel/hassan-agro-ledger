import 'dart:convert';
import 'dart:io';
import 'package:csv/csv.dart';
import 'package:excel/excel.dart';
import 'package:excel2003/excel2003.dart';
import 'offline_projection.dart';

Future<List<List<dynamic>>> readProductSpreadsheet(File file) async {
  if (await file.length() > 20 * 1024 * 1024) {
    throw const OfflineException('Choose a spreadsheet smaller than 20 MB.');
  }
  final bytes = await file.readAsBytes();
  final extension = file.path.split('.').last.toLowerCase();
  dynamic normalize(dynamic value) => value is DateTime ? value.toIso8601String() : value;
  if (extension == 'csv') {
    final source = utf8.decode(bytes).replaceFirst('\ufeff', '');
    return const CsvToListConverter(
      shouldParseNumbers: false,
    ).convert(source, eol: source.contains('\r\n') ? '\r\n' : '\n');
  }
  if (extension == 'xls') {
    final reader = XlsReader.fromBytes(bytes)..open();
    if (reader.sheetCount == 0) throw const OfflineException('The spreadsheet has no worksheet.');
    final sheet = reader.sheet(0);
    return [
      for (var r = sheet.firstRow; r < sheet.lastRow; r++) [for (var c = 0; c < 21; c++) normalize(sheet.cell(r, c))],
    ];
  }
  if (extension != 'xlsx') throw const OfflineException('Choose an XLSX, XLS or CSV spreadsheet.');
  final workbook = Excel.decodeBytes(bytes);
  if (workbook.tables.isEmpty) throw const OfflineException('The spreadsheet has no worksheet.');
  return workbook.tables.values.first.rows
      .map(
        (row) => row.map((cell) {
          final value = cell?.value;
          return switch (value) {
            IntCellValue() => value.value,
            DoubleCellValue() => value.value,
            BoolCellValue() => value.value,
            DateCellValue() => value.asDateTimeLocal().toIso8601String(),
            DateTimeCellValue() => value.asDateTimeLocal().toIso8601String(),
            _ => value?.toString(),
          };
        }).toList(),
      )
      .toList();
}
